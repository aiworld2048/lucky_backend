<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\WagerListService;
use Illuminate\Http\Request;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Log;

class WagerListController extends Controller
{
    /**
     * Display the wager list form
     */
    public function index(): View
    {
        return view('admin.wager_list.index');
    }

    /**
     * Fetch wager list from API
     */
    public function fetch(Request $request)
    {
        try {
            // Validate request
            $validated = $request->validate([
                'start_date' => 'required|date',
                'start_time' => 'required|date_format:H:i',
                'end_date' => 'required|date',
                'end_time' => 'required|date_format:H:i',
                'offset' => 'nullable|integer|min:0',
                'size' => 'nullable|integer|min:1|max:5000',
            ]);

            // Combine date and time, then convert to milliseconds
            $startDateTime = $validated['start_date'] . ' ' . $validated['start_time'];
            $endDateTime = $validated['end_date'] . ' ' . $validated['end_time'];

            $startTimestamp = strtotime($startDateTime) * 1000; // Convert to milliseconds
            $endTimestamp = strtotime($endDateTime) * 1000; // Convert to milliseconds

            // Validate time range (end must be ≤ 5 minutes from start)
            $timeDiff = ($endTimestamp - $startTimestamp) / 1000; // Convert to seconds
            if ($timeDiff > 300) { // 5 minutes = 300 seconds
                return redirect()->back()
                    ->withInput()
                    ->with('error', 'Time range must be ≤ 5 minutes. End time cannot be more than 5 minutes from start time.');
            }

            // Validate that end is after start
            if ($endTimestamp <= $startTimestamp) {
                return redirect()->back()
                    ->withInput()
                    ->with('error', 'End time must be greater than start time.');
            }

            // Get optional parameters
            $offset = $request->input('offset', 0);
            $size = $request->input('size', 1000);

            // Call service to get wager list
            $result = WagerListService::getWagerList(
                $startTimestamp,
                $endTimestamp,
                $offset ? (int) $offset : null,
                $size ? (int) $size : null
            );

            // Check if API returned an error
            if (isset($result['code']) && $result['code'] !== 200) {
                return redirect()->back()
                    ->withInput()
                    ->with('error', $result['message'] ?? 'Failed to fetch wager list from API.');
            }

            // Extract wagers and pagination from response
            $wagers = $result['wagers'] ?? [];
            $pagination = $result['pagination'] ?? null;

            return view('admin.wager_list.index', [
                'wagers' => $wagers,
                'pagination' => $pagination,
                'start_date' => $validated['start_date'],
                'start_time' => $validated['start_time'],
                'end_date' => $validated['end_date'],
                'end_time' => $validated['end_time'],
                'start_timestamp' => $startTimestamp,
                'end_timestamp' => $endTimestamp,
                'offset' => $offset,
                'size' => $size,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()
                ->withInput()
                ->withErrors($e->errors());
        } catch (\Exception $e) {
            Log::error('WagerListController Error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->back()
                ->withInput()
                ->with('error', 'An error occurred while fetching wager list: ' . $e->getMessage());
        }
    }

    /**
     * Show single wager details
     */
    public function show(string $idOrCode): View
    {
        try {
            // Call service to get single wager
            $result = WagerListService::getWager($idOrCode);

            // Check if API returned an error
            if (isset($result['code']) && $result['code'] !== 200) {
                return redirect()->route('admin.wager-list.index')
                    ->with('error', $result['message'] ?? 'Failed to fetch wager details from API.');
            }

            // Extract wager from response
            $wager = $result['wager'] ?? null;

            if (!$wager) {
                return redirect()->route('admin.wager-list.index')
                    ->with('error', 'Wager not found.');
            }

            return view('admin.wager_list.show', compact('wager', 'idOrCode'));
        } catch (\Exception $e) {
            Log::error('WagerListController Show Error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'id_or_code' => $idOrCode,
            ]);

            return redirect()->route('admin.wager-list.index')
                ->with('error', 'An error occurred while fetching wager details: ' . $e->getMessage());
        }
    }

    /**
     * Show game history for a wager
     */
    public function gameHistory(string $wagerCode): View
    {
        try {
            // Call service to get game history
            $result = WagerListService::getGameHistory($wagerCode);

            // Check if API returned an error
            if (isset($result['code']) && $result['code'] !== 200) {
                return redirect()->route('admin.wager-list.index')
                    ->with('error', $result['message'] ?? 'Failed to fetch game history from API.');
            }

            // Extract content from response
            $content = $result['content'] ?? null;

            if (!$content) {
                return redirect()->route('admin.wager-list.index')
                    ->with('error', 'Game history not found for this wager.');
            }

            return view('admin.wager_list.game_history', compact('content', 'wagerCode'));
        } catch (\Exception $e) {
            Log::error('WagerListController GameHistory Error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'wager_code' => $wagerCode,
            ]);

            return redirect()->route('admin.wager-list.index')
                ->with('error', 'An error occurred while fetching game history: ' . $e->getMessage());
        }
    }

    /**
     * Show wallet balance
     */
    public function walletBalance(): View
    {
        try {
            // Call service to get wallet balance
            $result = WagerListService::getWalletBalance();

            // Check if API returned an error
            if (isset($result['code']) && $result['code'] !== 0) {
                return redirect()->route('admin.wager-list.index')
                    ->with('error', $result['message'] ?? 'Failed to fetch wallet balance from API.');
            }

            // Extract data from response
            $data = $result['data'] ?? null;

            if (!$data) {
                return redirect()->route('admin.wager-list.index')
                    ->with('error', 'Wallet balance data not found.');
            }

            return view('admin.wager_list.wallet_balance', [
                'data' => $data,
                'code' => $result['code'] ?? 0,
                'message' => $result['message'] ?? 'Success',
            ]);
        } catch (\Exception $e) {
            Log::error('WagerListController WalletBalance Error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->route('admin.wager-list.index')
                ->with('error', 'An error occurred while fetching wallet balance: ' . $e->getMessage());
        }
    }
}

