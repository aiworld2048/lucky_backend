<?php

namespace App\Http\Controllers\Api\V1\gplus\Webhook;

use App\Enums\SeamlessWalletCode;
use App\Enums\TransactionName;
use App\Http\Controllers\Controller;
use App\Services\ApiResponseService;
use App\Services\WalletService;
use App\Models\TransactionLog;
use App\Models\User;
use Bavix\Wallet\Exceptions\InsufficientFunds;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WithdrawController extends Controller
{
    use SeamlessWebhookTrait;

    /**
     * @var array Actions considered as debits/withdrawals.
     */
    private array $debitActions = ['BET', 'ADJUST_DEBIT', 'WITHDRAW', 'FEE'];

    /**
     * @var WalletService
     */
    protected WalletService $walletService;

    /**
     * WithdrawController constructor.
     */
    public function __construct(WalletService $walletService)
    {
        $this->walletService = $walletService;
    }

    /**
     * Handle incoming withdraw/bet requests.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function withdraw(Request $request)
    {
        try {
            $request->validate([
                'operator_code' => 'required|string',
                'batch_requests' => 'required|array',
                'sign' => 'required|string',
                'request_time' => 'required|integer',
                'currency' => 'required|string',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return ApiResponseService::error(
                SeamlessWalletCode::InternalServerError,
                'Validation failed',
                $e->errors()
            );
        }

        $results = $this->processWithdrawTransactions($request);

        TransactionLog::create([
            'type' => 'withdraw',
            'batch_request' => $request->all(),
            'response_data' => $results,
            'status' => collect($results)->every(fn ($r) => $r['code'] === SeamlessWalletCode::Success->value) ? 'success' : 'partial_success_or_failure',
        ]);

        return ApiResponseService::success($results);
    }

    /**
     * Process withdrawal transactions.
     */
    private function processWithdrawTransactions(Request $request): array
    {
        $isValidSign = $this->verifySignature($request, 'withdraw');
        $isValidCurrency = $this->isValidCurrency($request->currency);

        $responseData = [];

        foreach ($request->batch_requests as $batchRequest) {
            $memberAccount = $batchRequest['member_account'] ?? null;
            $productCode = $batchRequest['product_code'] ?? null;

            // Validate signature and currency at batch level
            if (! $isValidSign) {
                $responseData[] = $this->buildErrorResponse(
                    $memberAccount,
                    $productCode,
                    0.0,
                    SeamlessWalletCode::InvalidSignature,
                    'Invalid signature',
                    $request->currency
                );
                continue;
            }

            if (! $isValidCurrency) {
                $responseData[] = $this->buildErrorResponse(
                    $memberAccount,
                    $productCode,
                    0.0,
                    SeamlessWalletCode::InternalServerError,
                    'Invalid Currency',
                    $request->currency
                );
                continue;
            }

            try {
                $user = $this->getUserByMemberAccount($memberAccount);
                $validationError = $this->validateUserAndWallet($user, $memberAccount);

                if ($validationError) {
                    $responseData[] = $this->buildErrorResponse(
                        $memberAccount,
                        $productCode,
                        0.0,
                        $validationError['code'],
                        $validationError['message'],
                        $request->currency
                    );
                    continue;
                }

                $currentBalance = $user->wallet->balanceFloat;

                // Batch check for duplicates before processing individual transactions
                $transactionIds = array_filter(array_column($batchRequest['transactions'] ?? [], 'id'));
                if (!empty($transactionIds)) {
                    $this->batchCheckDuplicates($transactionIds);
                }

                // Pre-load game types for all transactions to cache them
                $gameCodes = array_filter(array_column($batchRequest['transactions'] ?? [], 'game_code'));
                foreach ($gameCodes as $gameCode) {
                    $this->resolveGameType(null, $gameCode);
                }

                foreach ($batchRequest['transactions'] ?? [] as $tx) {
                    $result = $this->processWithdrawTransaction(
                        $batchRequest,
                        $request,
                        $tx,
                        $user,
                        $currentBalance
                    );

                    $responseData[] = $result['response'];
                    $currentBalance = $result['balance'];
                }

                // Clear duplicate cache after batch
                $this->clearDuplicateCache();
            } catch (\Throwable $e) {
                $responseData[] = $this->buildErrorResponse(
                    $memberAccount,
                    $productCode,
                    0.0,
                    SeamlessWalletCode::InternalServerError,
                    'An unexpected error occurred during batch processing: '.$e->getMessage(),
                    $request->currency
                );
            }
        }

        return $responseData;
    }

    /**
     * Process a single withdrawal transaction.
     */
    private function processWithdrawTransaction(
        array $batchRequest,
        Request $fullRequest,
        array $transactionRequest,
        User $user,
        float $currentBalance
    ): array {
        $transactionId = $transactionRequest['id'] ?? null;
        $action = strtoupper($transactionRequest['action'] ?? '');
        $amount = floatval($transactionRequest['amount'] ?? 0);
        $wagerCode = $transactionRequest['wager_code'] ?? $transactionRequest['round_id'] ?? null;
        $gameCode = $transactionRequest['game_code'] ?? null;
        $memberAccount = $batchRequest['member_account'] ?? null;
        $productCode = $batchRequest['product_code'] ?? null;

        // Resolve game type
        $transactionGameType = $this->resolveGameType($batchRequest['game_type'] ?? null, $gameCode);

        if (empty($transactionGameType)) {
            $this->logPlaceBet($batchRequest, $fullRequest, $transactionRequest, 'failed', $fullRequest->request_time, 'Missing game_type', $user, $currentBalance, $currentBalance);
            return [
                'response' => $this->buildErrorResponse(
                    $memberAccount,
                    $productCode,
                    $currentBalance,
                    SeamlessWalletCode::InternalServerError,
                    'Missing game_type',
                    $fullRequest->currency
                ),
                'balance' => $currentBalance,
            ];
        }

        // Validate transaction data
        if (! $transactionId || empty($action)) {
            $this->logPlaceBet($batchRequest, $fullRequest, $transactionRequest, 'failed', $fullRequest->request_time, 'Missing transaction data (id or action)', $user, $currentBalance, $currentBalance);
            return [
                'response' => $this->buildErrorResponse(
                    $memberAccount,
                    $productCode,
                    $currentBalance,
                    SeamlessWalletCode::InternalServerError,
                    'Missing transaction data (id or action)',
                    $fullRequest->currency
                ),
                'balance' => $currentBalance,
            ];
        }

        // Convert amount (always positive for withdrawal)
        $convertedAmount = abs($this->toDecimalPlaces($amount * $this->getCurrencyValue($fullRequest->currency)));

        // Check for duplicate (uses cache from batch check)
        if ($this->isDuplicateTransaction($transactionId)) {
            $this->logPlaceBet($batchRequest, $fullRequest, $transactionRequest, 'duplicate', $fullRequest->request_time, 'Duplicate transaction', $user, $currentBalance, $currentBalance);
            return [
                'response' => $this->buildErrorResponse(
                    $memberAccount,
                    $productCode,
                    $currentBalance,
                    SeamlessWalletCode::DuplicateTransaction,
                    'Duplicate transaction',
                    $fullRequest->currency
                ),
                'balance' => $currentBalance,
            ];
        }

        // Validate action
        if (! in_array($action, $this->debitActions)) {
            $this->logPlaceBet($batchRequest, $fullRequest, $transactionRequest, 'failed', $fullRequest->request_time, 'Unsupported action type for this endpoint: '.$action, $user, $currentBalance, $currentBalance);
            return [
                'response' => $this->buildErrorResponse(
                    $memberAccount,
                    $productCode,
                    $currentBalance,
                    SeamlessWalletCode::InternalServerError,
                    'Unsupported action type: '.$action,
                    $fullRequest->currency
                ),
                'balance' => $currentBalance,
            ];
        }

        // Prepare meta data
        $meta = [
            'seamless_transaction_id' => $transactionId,
            'action_type' => $action,
            'product_code' => $productCode,
            'wager_code' => $wagerCode,
            'round_id' => $transactionRequest['round_id'] ?? null,
            'game_code' => $gameCode,
            'game_type' => $transactionGameType,
            'channel_code' => $transactionRequest['channel_code'] ?? null,
            'raw_payload' => $transactionRequest,
        ];

        // Process the withdrawal transaction
        DB::beginTransaction();
        $transactionCode = SeamlessWalletCode::InternalServerError->value;
        $transactionMessage = 'Failed to process transaction';
        $beforeTransactionBalance = null;

        try {
            $user->refresh();
            $userWithWallet = $this->getUserWithLockedWallet($user);
            $beforeTransactionBalance = $userWithWallet->wallet->balanceFloat;

            // Check for insufficient balance before withdrawal
            if ($beforeTransactionBalance < $convertedAmount) {
                $transactionCode = SeamlessWalletCode::InsufficientBalance->value;
                $transactionMessage = 'Insufficient balance';
                $this->logPlaceBet(
                    $batchRequest,
                    $fullRequest,
                    $transactionRequest,
                    'failed',
                    $fullRequest->request_time,
                    $transactionMessage,
                    $userWithWallet,
                    $beforeTransactionBalance,
                    $beforeTransactionBalance
                );
                DB::rollBack();

                return [
                    'response' => [
                        'member_account' => $memberAccount,
                        'product_code' => (int) $productCode,
                        'before_balance' => $this->formatBalance($beforeTransactionBalance, $fullRequest->currency),
                        'balance' => $this->formatBalance($beforeTransactionBalance, $fullRequest->currency),
                        'code' => $transactionCode,
                        'message' => $transactionMessage,
                    ],
                    'balance' => $currentBalance,
                ];
            }

            // Perform the withdrawal
            $this->walletService->withdraw($userWithWallet, $convertedAmount, TransactionName::Withdraw, $meta);
            $newBalance = $userWithWallet->wallet->balanceFloat;

            $transactionCode = SeamlessWalletCode::Success->value;
            $transactionMessage = 'Transaction processed successfully';
            $this->logPlaceBet(
                $batchRequest,
                $fullRequest,
                $transactionRequest,
                'completed',
                $fullRequest->request_time,
                $transactionMessage,
                $userWithWallet,
                $beforeTransactionBalance,
                $newBalance
            );

            DB::commit();

            return [
                'response' => $this->buildSuccessResponse(
                    $memberAccount,
                    $productCode,
                    $beforeTransactionBalance,
                    $newBalance,
                    $fullRequest->currency
                ),
                'balance' => $newBalance,
            ];
        } catch (InsufficientFunds $e) {
            DB::rollBack();
            $transactionCode = SeamlessWalletCode::InsufficientBalance->value;
            $transactionMessage = 'Insufficient balance: '.$e->getMessage();
            $this->logPlaceBet(
                $batchRequest,
                $fullRequest,
                $transactionRequest,
                'failed',
                $fullRequest->request_time,
                $transactionMessage,
                $userWithWallet ?? $user,
                $beforeTransactionBalance ?? $user->balanceFloat,
                $currentBalance
            );
        } catch (Exception $e) {
            DB::rollBack();
            $transactionCode = SeamlessWalletCode::InternalServerError->value;
            $transactionMessage = 'Failed to process transaction: '.$e->getMessage();
            $this->logPlaceBet(
                $batchRequest,
                $fullRequest,
                $transactionRequest,
                'failed',
                $fullRequest->request_time,
                $transactionMessage,
                $userWithWallet ?? $user,
                $beforeTransactionBalance ?? $user->balanceFloat,
                $currentBalance
            );
        }

        return [
            'response' => [
                'member_account' => $memberAccount,
                'product_code' => (int) $productCode,
                'before_balance' => $this->formatBalance($beforeTransactionBalance ?? $user->balanceFloat, $fullRequest->currency),
                'balance' => $this->formatBalance($currentBalance, $fullRequest->currency),
                'code' => $transactionCode,
                'message' => $transactionMessage,
            ],
            'balance' => $currentBalance,
        ];
    }
}
