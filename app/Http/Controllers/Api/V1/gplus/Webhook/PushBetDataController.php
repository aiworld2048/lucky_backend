<?php

namespace App\Http\Controllers\Api\V1\gplus\Webhook;

use App\Enums\SeamlessWalletCode;
use App\Http\Controllers\Controller;
use App\Models\PushBet;
use App\Models\User;
use App\Services\ApiResponseService;
use Illuminate\Http\Request;

class PushBetDataController extends Controller
{
    use SeamlessWebhookTrait;

    /**
     * Handle push bet data requests.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function pushBetData(Request $request)
    {
        try {
            $request->validate([
                'operator_code' => 'required|string',
                'wagers' => 'required|array',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return ApiResponseService::error(
                SeamlessWalletCode::InternalServerError,
                'Validation failed',
                $e->errors()
            );
        }

        // Verify signature if provided
        if (! empty($request->sign)) {
            if (! $this->verifySignature($request, 'pushbetdata')) {
                return response()->json([
                    'code' => SeamlessWalletCode::InvalidSignature->value,
                    'message' => 'Invalid signature',
                ]);
            }
        }

        // Process each wager
        foreach ($request->wagers as $wager) {
            $result = $this->processWager($wager, $request);

            // Return error immediately if member not found
            if ($result !== null) {
                return response()->json($result);
            }
        }

        return response()->json([
            'code' => SeamlessWalletCode::Success->value,
            'message' => '',
        ]);
    }

    /**
     * Process a single wager.
     *
     * @return array|null Returns error response array if error, null if success
     */
    private function processWager(array $wager, Request $request): ?array
    {
        $memberAccount = $wager['member_account'] ?? null;
        $user = $this->getUserByMemberAccount($memberAccount ?? '');

        if (! $user) {
            return [
                'code' => SeamlessWalletCode::MemberNotExist->value,
                'message' => 'Member not found',
            ];
        }

        $wagerCode = $wager['wager_code'] ?? null;
        if (! $wagerCode) {
            return null; // Skip wagers without wager_code
        }

        $this->upsertPushBet($wager, $request);

        return null;
    }

    /**
     * Upsert push bet record.
     */
    private function upsertPushBet(array $wager, Request $request): void
    {
        $wagerCode = $wager['wager_code'] ?? null;
        $pushBet = PushBet::where('wager_code', $wagerCode)->first();

        $data = $this->preparePushBetData($wager, $request);

        if ($pushBet) {
            $pushBet->update($data);
        } else {
            PushBet::create($data);
        }
    }

    /**
     * Prepare push bet data for database.
     */
    private function preparePushBetData(array $wager, Request $request): array
    {
        $createdAtProvider = $this->getTimestamp($wager['created_at'] ?? null);
        $settledAt = $this->getTimestamp($wager['settled_at'] ?? null);

        return [
            'member_account' => $wager['member_account'] ?? '',
            'currency' => $wager['currency'] ?? '',
            'product_code' => $this->toInt($wager['product_code'] ?? 0),
            'game_code' => $wager['game_code'] ?? null,
            'game_type' => $wager['game_type'] ?? '',
            'wager_code' => $wager['wager_code'] ?? '',
            'wager_type' => $wager['wager_type'] ?? '',
            'wager_status' => $wager['wager_status'] ?? '',
            'bet_amount' => $this->toFloat($wager['bet_amount'] ?? 0),
            'valid_bet_amount' => $this->toFloat($wager['valid_bet_amount'] ?? 0),
            'prize_amount' => $this->toFloat($wager['prize_amount'] ?? 0),
            'tip_amount' => $this->toFloat($wager['tip_amount'] ?? 0),
            'created_at_provider' => $createdAtProvider,
            'settled_at' => $settledAt,
            'meta' => json_encode($wager),
        ];
    }

    /**
     * Convert value to integer.
     */
    private function toInt($value): int
    {
        return is_numeric($value) ? (int) $value : 0;
    }

    /**
     * Convert value to float.
     */
    private function toFloat($value): float
    {
        return is_numeric($value) ? (float) $value : 0.0;
    }

    /**
     * Get timestamp from milliseconds or seconds.
     */
    private function getTimestamp($timestamp): ?\Illuminate\Support\Carbon
    {
        if (! isset($timestamp) || ! is_numeric($timestamp)) {
            return null;
        }

        // If timestamp is in milliseconds (13 digits), convert to seconds
        $seconds = $timestamp > 9999999999 ? floor($timestamp / 1000) : $timestamp;

        return now()->setTimestamp($seconds);
    }
}
