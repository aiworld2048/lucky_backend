<?php

namespace App\Http\Controllers\Api\V1\gplus\Webhook;

use App\Enums\SeamlessWalletCode;
use App\Enums\TransactionName;
use App\Http\Controllers\Controller;
use App\Models\PlaceBet;
use App\Models\Transaction as WalletTransaction;
use App\Models\TransactionLog;
use App\Models\User;
use App\Services\ApiResponseService;
use App\Services\WalletService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DepositController extends Controller
{
    use SeamlessWebhookTrait;

    /**
     * @var array Actions considered as deposits.
     */
    private array $depositActions = ['WIN', 'SETTLED', 'JACKPOT', 'BONUS', 'PROMO', 'LEADERBOARD', 'FREEBET', 'PRESERVE_REFUND', 'CANCEL'];

    /**
     * @var array Allowed wager statuses.
     */
    private array $allowedWagerStatuses = ['SETTLED', 'UNSETTLED', 'PENDING', 'CANCELLED', 'VOID'];

    /**
     * Handle incoming deposit requests.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function deposit(Request $request)
    {
        try {
            $request->validate([
                'batch_requests' => 'required|array',
                'operator_code' => 'required|string',
                'currency' => 'required|string',
                'sign' => 'required|string',
                'request_time' => 'required|integer',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return ApiResponseService::error(
                SeamlessWalletCode::InternalServerError,
                'Validation failed',
                $e->errors()
            );
        }

        $results = $this->processTransactions($request);

        TransactionLog::create([
            'type' => 'deposit',
            'batch_request' => $request->all(),
            'response_data' => $results,
            'status' => collect($results)->every(fn ($r) => $r['code'] === SeamlessWalletCode::Success->value) ? 'success' : 'partial_success_or_failure',
        ]);

        return ApiResponseService::success($results);
    }

    /**
     * Process deposit transactions.
     *
     * @throws Exception
     */
    private function processTransactions(Request $request): array
    {
        $isValidSign = $this->verifySignature($request, 'deposit');
        $isValidCurrency = $this->isValidCurrency($request->currency);

        $results = [];
        $walletService = app(WalletService::class);
        $admin = User::adminUser();

        if (! $admin) {
            throw new Exception('Admin user not configured properly.');
        }

        foreach ($request->batch_requests as $batchRequest) {
            $memberAccount = $batchRequest['member_account'] ?? null;
            $productCode = $batchRequest['product_code'] ?? null;

            // Validate signature and currency at batch level
            if (! $isValidSign) {
                $results[] = $this->buildErrorResponse(
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
                $results[] = $this->buildErrorResponse(
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
                    $results[] = $this->buildErrorResponse(
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

                foreach ($batchRequest['transactions'] ?? [] as $transactionRequest) {
                    $result = $this->processDepositTransaction(
                        $batchRequest,
                        $request,
                        $transactionRequest,
                        $user,
                        $walletService,
                        $admin,
                        $currentBalance
                    );

                    $results[] = $result['response'];
                    $currentBalance = $result['balance'];
                }

                // Clear duplicate cache after batch
                $this->clearDuplicateCache();
            } catch (\Throwable $e) {
                $results[] = $this->buildErrorResponse(
                    $memberAccount,
                    $productCode,
                    0.0,
                    SeamlessWalletCode::InternalServerError,
                    'An unexpected error occurred during batch processing.',
                    $request->currency
                );
            }
        }

        return $results;
    }

    /**
     * Process a single deposit transaction.
     */
    private function processDepositTransaction(
        array $batchRequest,
        Request $fullRequest,
        array $transactionRequest,
        User $user,
        WalletService $walletService,
        User $admin,
        float $currentBalance
    ): array {
        $transactionId = $transactionRequest['id'] ?? null;
        $action = strtoupper($transactionRequest['action'] ?? '');
        $wagerCode = $transactionRequest['wager_code'] ?? $transactionRequest['round_id'] ?? null;
        $amount = round(floatval($transactionRequest['amount'] ?? 0), 4);
        $gameCode = $transactionRequest['game_code'] ?? null;
        $memberAccount = $batchRequest['member_account'] ?? null;
        $productCode = $batchRequest['product_code'] ?? null;

        // Resolve game type
        $transactionGameType = $this->resolveGameType($batchRequest['game_type'] ?? null, $gameCode);

        if (empty($transactionGameType)) {
            $this->logPlaceBet($batchRequest, $fullRequest, $transactionRequest, 'failed', $fullRequest->request_time, 'Missing game_type', $user);
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

        // Check for duplicate (uses cache from batch check)
        if ($this->isDuplicateTransaction($transactionId)) {
            $this->logPlaceBet($batchRequest, $fullRequest, $transactionRequest, 'duplicate', $fullRequest->request_time, 'Duplicate transaction', $user);
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

        // Validate action and wager status
        if (! $this->isValidActionForDeposit($action) || ! $this->isValidWagerStatus($transactionRequest['wager_status'] ?? null)) {
            $this->logPlaceBet($batchRequest, $fullRequest, $transactionRequest, 'failed', $fullRequest->request_time, 'Invalid action type or wager status for deposit', $user);
            return [
                'response' => $this->buildErrorResponse(
                    $memberAccount,
                    $productCode,
                    $currentBalance,
                    SeamlessWalletCode::BetNotExist,
                    'Invalid action type or wager status for deposit',
                    $fullRequest->currency
                ),
                'balance' => $currentBalance,
            ];
        }

        // Handle CANCEL action
        if ($action === 'CANCEL') {
            $originalBet = PlaceBet::where('wager_code', $wagerCode)
                ->where('member_account', $memberAccount)
                ->first();

            if (! $originalBet) {
                $this->logPlaceBet($batchRequest, $fullRequest, $transactionRequest, 'failed', $fullRequest->request_time, 'Original bet not found for cancellation', $user);
                return [
                    'response' => $this->buildErrorResponse(
                        $memberAccount,
                        $productCode,
                        $currentBalance,
                        SeamlessWalletCode::BetNotExist,
                        'Original bet not found for cancellation',
                        $fullRequest->currency
                    ),
                    'balance' => $currentBalance,
                ];
            }
        }

        // Process the deposit transaction
        DB::beginTransaction();
        try {
            $user->refresh();
            $userWithWallet = $this->getUserWithLockedWallet($user);

            if (! $userWithWallet || ! $userWithWallet->wallet) {
                throw new Exception('User or wallet not found during transaction locking.');
            }

            $beforeTransactionBalance = $userWithWallet->wallet->balanceFloat;
            $convertedAmount = $this->toDecimalPlaces($amount * $this->getCurrencyValue($fullRequest->currency));

            $walletService->deposit($userWithWallet, $convertedAmount, TransactionName::Deposit, [
                'seamless_transaction_id' => $transactionId,
                'action' => $action,
                'wager_code' => $wagerCode,
                'product_code' => $productCode,
                'game_type' => $transactionGameType,
                'from_admin' => $admin->id,
            ]);

            $afterTransactionBalance = $userWithWallet->wallet->balanceFloat;

            $response = $this->buildSuccessResponse(
                $memberAccount,
                $productCode,
                $beforeTransactionBalance,
                $afterTransactionBalance,
                $fullRequest->currency
            );

            $this->logPlaceBet(
                $batchRequest,
                $fullRequest,
                $transactionRequest,
                'completed',
                $fullRequest->request_time,
                null,
                $userWithWallet,
                $beforeTransactionBalance,
                $afterTransactionBalance
            );

            DB::commit();

            return [
                'response' => $response,
                'balance' => $afterTransactionBalance,
            ];
        } catch (Exception $e) {
            DB::rollBack();
            $code = SeamlessWalletCode::InternalServerError;
            if (str_contains($e->getMessage(), 'amount must be positive')) {
                $code = SeamlessWalletCode::InsufficientBalance;
            }

            $this->logPlaceBet(
                $batchRequest,
                $fullRequest,
                $transactionRequest,
                'failed',
                $fullRequest->request_time,
                $e->getMessage(),
                $userWithWallet ?? $user,
                $beforeTransactionBalance ?? null,
                null
            );

            return [
                'response' => $this->buildErrorResponse(
                    $memberAccount,
                    $productCode,
                    $currentBalance,
                    $code,
                    $e->getMessage(),
                    $fullRequest->currency
                ),
                'balance' => $currentBalance,
            ];
        }
    }

    /**
     * Check if the action is valid for deposit endpoint.
     */
    private function isValidActionForDeposit(string $action): bool
    {
        return in_array($action, $this->depositActions);
    }

    /**
     * Check if the wager status is valid.
     */
    private function isValidWagerStatus(?string $wagerStatus): bool
    {
        if (is_null($wagerStatus)) {
            return true;
        }

        return in_array($wagerStatus, $this->allowedWagerStatuses);
    }
}
