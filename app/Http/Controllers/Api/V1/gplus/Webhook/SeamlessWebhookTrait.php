<?php

namespace App\Http\Controllers\Api\V1\gplus\Webhook;

use App\Enums\SeamlessWalletCode;
use App\Models\GameList;
use App\Models\PlaceBet;
use App\Models\Transaction as WalletTransaction;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;

trait SeamlessWebhookTrait
{
    /**
     * @var array Allowed currencies for transactions.
     */
    protected array $allowedCurrencies = ['MMK', 'IDR', 'IDR2', 'KRW2', 'MMK2', 'VND2', 'LAK2', 'KHR2'];

    /**
     * @var array Currencies requiring special conversion (e.g., 1:1000).
     */
    protected array $specialCurrencies = ['IDR2', 'KRW2', 'MMK2', 'VND2', 'LAK2', 'KHR2'];

    /**
     * @var array Cache for GameList lookups to avoid repeated queries.
     */
    private static array $gameListCache = [];

    /**
     * @var array Cache for duplicate transaction checks within a batch.
     */
    private array $duplicateCheckCache = [];

    /**
     * Verify the signature for the request.
     */
    protected function verifySignature(Request $request, string $action): bool
    {
        $secretKey = Config::get('seamless_key.secret_key');
        $expectedSign = md5(
            $request->operator_code.
            $request->request_time.
            $action.
            $secretKey
        );

        return strtolower($request->sign) === strtolower($expectedSign);
    }

    /**
     * Check if currency is valid.
     */
    protected function isValidCurrency(string $currency): bool
    {
        return in_array($currency, $this->allowedCurrencies);
    }

    /**
     * Get user by member account with wallet eager loaded.
     */
    protected function getUserByMemberAccount(string $memberAccount): ?User
    {
        return User::with('wallet')->where('user_name', $memberAccount)->first();
    }

    /**
     * Validate user and wallet exist.
     */
    protected function validateUserAndWallet(?User $user, string $memberAccount): ?array
    {
        if (! $user) {
            return [
                'code' => SeamlessWalletCode::MemberNotExist,
                'message' => 'Member not found',
            ];
        }

        if (! $user->wallet) {
            return [
                'code' => SeamlessWalletCode::MemberNotExist,
                'message' => 'Member wallet missing',
            ];
        }

        return null;
    }

    /**
     * Resolve game type from batch request or game code.
     * Uses caching to avoid repeated database queries.
     */
    protected function resolveGameType(?string $batchGameType, ?string $gameCode): ?string
    {
        if (! empty($batchGameType)) {
            return $batchGameType;
        }

        if ($gameCode) {
            $cacheKey = "game_type_{$gameCode}";
            if (! isset(self::$gameListCache[$cacheKey])) {
                self::$gameListCache[$cacheKey] = GameList::where('game_code', $gameCode)->value('game_type');
            }
            return self::$gameListCache[$cacheKey];
        }

        return null;
    }

    /**
     * Get GameList data by product_code (cached).
     */
    protected function getGameListByProductCode($productCode): ?array
    {
        $cacheKey = "product_{$productCode}";
        if (! isset(self::$gameListCache[$cacheKey])) {
            $game = GameList::where('product_code', $productCode)->first(['provider', 'game_type']);
            self::$gameListCache[$cacheKey] = $game ? [
                'provider' => $game->provider,
                'game_type' => $game->game_type,
            ] : null;
        }
        return self::$gameListCache[$cacheKey];
    }

    /**
     * Get GameList data by game_code (cached).
     */
    protected function getGameListByGameCode(?string $gameCode): ?array
    {
        if (! $gameCode) {
            return null;
        }

        $cacheKey = "game_{$gameCode}";
        if (! isset(self::$gameListCache[$cacheKey])) {
            $game = GameList::where('game_code', $gameCode)->first(['game_name', 'game_type', 'provider']);
            self::$gameListCache[$cacheKey] = $game ? [
                'game_name' => $game->game_name,
                'game_type' => $game->game_type,
                'provider' => $game->provider,
            ] : null;
        }
        return self::$gameListCache[$cacheKey];
    }

    /**
     * Batch check for duplicate transactions.
     * This reduces N queries to 1 query per batch for PlaceBet.
     * Note: WalletTransaction JSON queries are checked individually as JSON queries don't batch well.
     */
    protected function batchCheckDuplicates(array $transactionIds): array
    {
        if (empty($transactionIds)) {
            return [];
        }

        // Filter out nulls and already checked IDs
        $idsToCheck = array_values(array_filter($transactionIds, fn($id) => $id && !isset($this->duplicateCheckCache[$id])));
        
        if (empty($idsToCheck)) {
            return [];
        }

        // Batch query PlaceBet - single query for all transaction IDs
        $placeBetDuplicates = PlaceBet::whereIn('transaction_id', $idsToCheck)
            ->pluck('transaction_id')
            ->toArray();

        // Cache PlaceBet duplicates
        foreach ($placeBetDuplicates as $duplicateId) {
            $this->duplicateCheckCache[$duplicateId] = true;
        }

        // Batch query WalletTransaction using the dedicated seamless_transaction_id column
        // This is much more efficient than JSON queries
        $walletDuplicates = WalletTransaction::whereIn('seamless_transaction_id', $idsToCheck)
            ->whereNotNull('seamless_transaction_id')
            ->pluck('seamless_transaction_id')
            ->toArray();

        // Cache WalletTransaction duplicates
        foreach ($walletDuplicates as $duplicateId) {
            $this->duplicateCheckCache[$duplicateId] = true;
        }

        // Mark non-duplicates in cache
        foreach ($idsToCheck as $id) {
            if (!isset($this->duplicateCheckCache[$id])) {
                $this->duplicateCheckCache[$id] = false;
            }
        }

        $allDuplicates = array_unique(array_merge($placeBetDuplicates, $walletDuplicates));

        return $allDuplicates;
    }

    /**
     * Clear duplicate check cache (call at start of each batch request).
     */
    protected function clearDuplicateCache(): void
    {
        $this->duplicateCheckCache = [];
    }

    /**
     * Check if transaction is duplicate.
     * Uses cache if available from batch checking.
     */
    protected function isDuplicateTransaction(?string $transactionId): bool
    {
        if (! $transactionId) {
            return false;
        }

        // Check cache first
        if (isset($this->duplicateCheckCache[$transactionId])) {
            return $this->duplicateCheckCache[$transactionId];
        }

        // Fallback to individual check if not in cache
        // Use the dedicated seamless_transaction_id column for better performance
        $isDuplicate = PlaceBet::where('transaction_id', $transactionId)->exists() ||
               WalletTransaction::where('seamless_transaction_id', $transactionId)->exists();

        // Cache the result
        $this->duplicateCheckCache[$transactionId] = $isDuplicate;

        return $isDuplicate;
    }

    /**
     * Get currency conversion value for internal processing.
     */
    protected function getCurrencyValue(string $currency): int|float
    {
        return match ($currency) {
            'IDR2' => 100,
            'KRW2' => 10,
            'MMK2' => 1000,
            'VND2' => 1000,
            'LAK2' => 10,
            'KHR2' => 100,
            default => 1,
        };
    }

    /**
     * Convert float to specified decimal places.
     */
    protected function toDecimalPlaces(float $value, int $precision = 4): float
    {
        return round($value, $precision);
    }

    /**
     * Format balance based on currency and its scaling.
     */
    protected function formatBalance(float $balance, string $currency): float
    {
        $divisor = $this->getCurrencyValue($currency);
        $precision = in_array($currency, $this->specialCurrencies) ? 4 : 2;

        return round($balance / $divisor, $precision);
    }

    /**
     * Build a consistent error response.
     */
    protected function buildErrorResponse(
        string $memberAccount,
        string|int $productCode,
        float $balance,
        SeamlessWalletCode $code,
        string $message,
        string $currency
    ): array {
        $formattedBalance = $this->formatBalance($balance, $currency);

        return [
            'member_account' => $memberAccount,
            'product_code' => (int) $productCode,
            'before_balance' => $formattedBalance,
            'balance' => $formattedBalance,
            'code' => $code->value,
            'message' => $message,
        ];
    }

    /**
     * Build a success response.
     */
    protected function buildSuccessResponse(
        string $memberAccount,
        string|int $productCode,
        float $beforeBalance,
        float $afterBalance,
        string $currency
    ): array {
        return [
            'member_account' => $memberAccount,
            'product_code' => (int) $productCode,
            'before_balance' => $this->formatBalance($beforeBalance, $currency),
            'balance' => $this->formatBalance($afterBalance, $currency),
            'code' => SeamlessWalletCode::Success->value,
            'message' => '',
        ];
    }

    /**
     * Convert milliseconds timestamp to seconds.
     */
    protected function millisecondsToSeconds(?int $milliseconds): ?int
    {
        return $milliseconds ? (int) floor($milliseconds / 1000) : null;
    }

    /**
     * Get PlaceBet data for logging.
     * Optimized to use cached GameList data and passed User object.
     */
    protected function getPlaceBetData(
        array $batchRequest,
        Request $fullRequest,
        array $transactionRequest,
        ?int $requestTime,
        ?User $user = null,
        ?float $beforeBalance = null,
        ?float $afterBalance = null
    ): array {
        $requestTimeInSeconds = $this->millisecondsToSeconds($requestTime);
        $settleAtTime = $transactionRequest['settle_at'] ?? $transactionRequest['settled_at'] ?? null;
        $settleAtInSeconds = $this->millisecondsToSeconds($settleAtTime);
        $createdAtProviderTime = $transactionRequest['created_at'] ?? null;
        $createdAtProviderInSeconds = $this->millisecondsToSeconds($createdAtProviderTime);

        // Use cached GameList data
        $productData = $this->getGameListByProductCode($batchRequest['product_code'] ?? null);
        $providerName = $productData['provider'] ?? $batchRequest['product_code'] ?? null;

        $gameData = $this->getGameListByGameCode($transactionRequest['game_code'] ?? null);
        $gameName = $gameData['game_name'] ?? $transactionRequest['game_code'] ?? null;

        // Use passed user object instead of querying
        $playerId = $user?->id;
        $playerAgentId = $user?->agent_id;

        $playerRegPlayerRefCode = $user?->reg_player_ref_code ?? ($user?->agent?->referral_code);


        return [
            'transaction_id' => $transactionRequest['id'] ?? '',
            'member_account' => $batchRequest['member_account'] ?? '',
            'player_id' => $playerId,
            'player_agent_id' => $playerAgentId,
            'player_reg_player_ref_code' => $playerRegPlayerRefCode,
            'product_code' => $batchRequest['product_code'] ?? 0,
            'provider_name' => $providerName ?? $batchRequest['product_code'] ?? null,
            'game_type' => $batchRequest['game_type'] ?? '',
            'operator_code' => $fullRequest->operator_code,
            'request_time' => $requestTimeInSeconds ? now()->setTimestamp($requestTimeInSeconds) : null,
            'sign' => $fullRequest->sign,
            'currency' => $fullRequest->currency,
            'action' => $transactionRequest['action'] ?? '',
            'amount' => $transactionRequest['amount'] ?? 0,
            'valid_bet_amount' => $transactionRequest['valid_bet_amount'] ?? null,
            'bet_amount' => $transactionRequest['bet_amount'] ?? null,
            'prize_amount' => $transactionRequest['prize_amount'] ?? null,
            'tip_amount' => $transactionRequest['tip_amount'] ?? null,
            'wager_code' => $transactionRequest['wager_code'] ?? null,
            'wager_status' => $transactionRequest['wager_status'] ?? null,
            'round_id' => $transactionRequest['round_id'] ?? null,
            'payload' => isset($transactionRequest['payload']) ? json_encode($transactionRequest['payload']) : null,
            'settle_at' => $settleAtInSeconds ? now()->setTimestamp($settleAtInSeconds) : null,
            'created_at_provider' => $createdAtProviderInSeconds ? now()->setTimestamp($createdAtProviderInSeconds) : null,
            'game_code' => $transactionRequest['game_code'] ?? null,
            'game_name' => $gameName ?? $transactionRequest['game_code'] ?? null,
            'channel_code' => $transactionRequest['channel_code'] ?? null,
            'before_balance' => $beforeBalance,
            'balance' => $afterBalance,
        ];
    }

    /**
     * Log transaction attempt in the place_bets table.
     * Optimized to accept User object to avoid repeated queries.
     */
    protected function logPlaceBet(
        array $batchRequest,
        Request $fullRequest,
        array $transactionRequest,
        string $status,
        ?int $requestTime,
        ?string $errorMessage = null,
        ?User $user = null,
        ?float $beforeBalance = null,
        ?float $afterBalance = null
    ): void {
        $data = $this->getPlaceBetData($batchRequest, $fullRequest, $transactionRequest, $requestTime, $user, $beforeBalance, $afterBalance);
        $data['status'] = $status;

        if ($errorMessage !== null) {
            $data['error_message'] = $errorMessage;
        }

        try {
            PlaceBet::create($data);
        } catch (QueryException $e) {
            // MySQL: 23000, PostgreSQL: 23505 for unique constraint violation
            if (! in_array($e->getCode(), ['23000', '23505'])) {
                throw $e;
            }
        }
    }

    /**
     * Get user with locked wallet for transaction safety.
     */
    protected function getUserWithLockedWallet(User $user): ?User
    {
        return User::with(['wallet' => function ($query) {
            $query->lockForUpdate();
        }])->find($user->id);
    }
}

