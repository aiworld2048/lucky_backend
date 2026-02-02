<?php

namespace App\Http\Controllers\Api\V1\gplus\Webhook;

use App\Http\Controllers\Controller;
use App\Services\WagerListService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class WagerListController extends Controller
{
    /**
     * Get wager list from seamless API
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            // Validate required parameters
            $request->validate([
                'start' => 'required|integer',
                'end' => 'required|integer',
                'offset' => 'nullable|integer|min:0',
                'size' => 'nullable|integer|min:1|max:5000',
            ]);

            $start = (int) $request->query('start');
            $end = (int) $request->query('end');
            $offset = $request->has('offset') ? (int) $request->query('offset') : null;
            $size = $request->has('size') ? (int) $request->query('size') : null;

            // Validate time range (end must be ≤ 5 minutes from start)
            $timeDiff = ($end - $start) / 1000; // Convert milliseconds to seconds
            if ($timeDiff > 300) { // 5 minutes = 300 seconds
                return response()->json([
                    'code' => 400,
                    'message' => 'Time range must be ≤ 5 minutes. End time cannot be more than 5 minutes from start time.',
                ], 400);
            }

            // Validate that end is after start
            if ($end <= $start) {
                return response()->json([
                    'code' => 400,
                    'message' => 'End time must be greater than start time.',
                ], 400);
            }

            // Call service to get wager list
            $result = WagerListService::getWagerList($start, $end, $offset, $size);

            return response()->json($result);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'code' => 422,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('WagerListController Error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'code' => 500,
                'message' => 'An error occurred while fetching wager list: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get single wager by ID or code from seamless API
     * 
     * @param Request $request
     * @param string|int $idOrCode
     * @return JsonResponse
     */
    public function show(Request $request, string|int $idOrCode): JsonResponse
    {
        try {
            // Call service to get single wager
            $result = WagerListService::getWager($idOrCode);

            return response()->json($result);
        } catch (\Exception $e) {
            Log::error('WagerListController Show Error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'id_or_code' => $idOrCode,
            ]);

            return response()->json([
                'code' => 500,
                'message' => 'An error occurred while fetching wager details: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get game history by wager code from seamless API
     * 
     * @param Request $request
     * @param string $wagerCode
     * @return JsonResponse
     */
    public function gameHistory(Request $request, string $wagerCode): JsonResponse
    {
        try {
            // Call service to get game history
            $result = WagerListService::getGameHistory($wagerCode);

            return response()->json($result);
        } catch (\Exception $e) {
            Log::error('WagerListController GameHistory Error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'wager_code' => $wagerCode,
            ]);

            return response()->json([
                'code' => 500,
                'message' => 'An error occurred while fetching game history: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get wallet balance from seamless API
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function walletBalance(Request $request): JsonResponse
    {
        try {
            // Call service to get wallet balance
            $result = WagerListService::getWalletBalance();

            return response()->json($result);
        } catch (\Exception $e) {
            Log::error('WagerListController WalletBalance Error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'code' => 500,
                'message' => 'An error occurred while fetching wallet balance: ' . $e->getMessage(),
            ], 500);
        }
    }
}

