<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WagerListService
{
    /**
     * Get wager list from seamless API
     * 
     * @param int $start Start time in timestamp milliseconds
     * @param int $end End time in timestamp milliseconds (must be ≤ 5 minutes from start)
     * @param int|null $offset Starting record number (optional)
     * @param int|null $size Number of records to fetch (optional, default=5000)
     * @return array
     */
    public static function getWagerList(int $start, int $end, ?int $offset = null, ?int $size = null): array
    {
        $operator_code = config('seamless_key.agent_code');
        $secret_key = config('seamless_key.secret_key');
        $api_url = rtrim(config('seamless_key.api_url'), '/');
        
        // Get current timestamp in seconds, then convert to milliseconds if needed
        $date = new \DateTime('now', new \DateTimeZone('Asia/Shanghai'));
        $request_time = $date->getTimestamp();
        
        // Generate signature: md5(request_time + secret_key + "getwagers" + operator_code)
        $sign_str = $request_time . $secret_key . 'getwagers' . $operator_code;
        $sign = md5($sign_str);

        // Debug logging for signature generation
        Log::info('WagerListService Signature Debug', [
            'request_time' => $request_time,
            'secret_key' => $secret_key,
            'operator_code' => $operator_code,
            'sign_str' => $sign_str,
            'sign' => $sign,
            'start' => $start,
            'end' => $end,
        ]);

        // Build parameters
        $params = [
            'operator_code' => $operator_code,
            'start' => $start,
            'end' => $end,
            'sign' => $sign,
            'request_time' => $request_time,
        ];

        // Add optional parameters
        if ($offset !== null) {
            $params['offset'] = $offset;
        }
        
        if ($size !== null) {
            $params['size'] = $size;
        }

        // Make API request
        $response = Http::get("{$api_url}/api/operators/wagers", $params);

        // Log response for debugging
        Log::info('WagerListService API Response', [
            'status' => $response->status(),
            'url' => "{$api_url}/api/operators/wagers",
            'response' => $response->json(),
        ]);

        return $response->json();
    }

    /**
     * Get single wager by ID or code from seamless API
     * 
     * @param string|int $idOrCode Wager ID or code
     * @return array
     */
    public static function getWager(string|int $idOrCode): array
    {
        $operator_code = config('seamless_key.agent_code');
        $secret_key = config('seamless_key.secret_key');
        $api_url = rtrim(config('seamless_key.api_url'), '/');
        
        // Get current timestamp in seconds
        $date = new \DateTime('now', new \DateTimeZone('Asia/Shanghai'));
        $request_time = $date->getTimestamp();
        
        // Generate signature: md5(request_time + secret_key + "getwager" + operator_code)
        $sign_str = $request_time . $secret_key . 'getwager' . $operator_code;
        $sign = md5($sign_str);

        // Debug logging for signature generation
        Log::info('WagerListService GetWager Signature Debug', [
            'request_time' => $request_time,
            'secret_key' => $secret_key,
            'operator_code' => $operator_code,
            'sign_str' => $sign_str,
            'sign' => $sign,
            'id_or_code' => $idOrCode,
        ]);

        // Build parameters
        $params = [
            'operator_code' => $operator_code,
            'sign' => $sign,
            'request_time' => $request_time,
        ];

        // Make API request - ID/code is in the URL path
        $response = Http::get("{$api_url}/api/operators/wagers/{$idOrCode}", $params);

        // Log response for debugging
        Log::info('WagerListService GetWager API Response', [
            'status' => $response->status(),
            'url' => "{$api_url}/api/operators/wagers/{$idOrCode}",
            'response' => $response->json(),
        ]);

        return $response->json();
    }

    /**
     * Get game history by wager code from seamless API
     * 
     * @param string $wagerCode Wager code
     * @return array
     */
    public static function getGameHistory(string $wagerCode): array
    {
        $operator_code = config('seamless_key.agent_code');
        $secret_key = config('seamless_key.secret_key');
        $api_url = rtrim(config('seamless_key.api_url'), '/');
        
        // Get current timestamp in seconds
        $date = new \DateTime('now', new \DateTimeZone('Asia/Shanghai'));
        $request_time = $date->getTimestamp();
        
        // Generate signature: md5(request_time + secret_key + "gamehistory" + operator_code)
        $sign_str = $request_time . $secret_key . 'gamehistory' . $operator_code;
        $sign = md5($sign_str);

        // Debug logging for signature generation
        Log::info('WagerListService GetGameHistory Signature Debug', [
            'request_time' => $request_time,
            'secret_key' => $secret_key,
            'operator_code' => $operator_code,
            'sign_str' => $sign_str,
            'sign' => $sign,
            'wager_code' => $wagerCode,
        ]);

        // Build parameters
        $params = [
            'operator_code' => $operator_code,
            'sign' => $sign,
            'request_time' => $request_time,
        ];

        // Make API request - wager_code is in the URL path
        $response = Http::get("{$api_url}/api/operators/{$wagerCode}/game-history", $params);

        // Log response for debugging
        Log::info('WagerListService GetGameHistory API Response', [
            'status' => $response->status(),
            'url' => "{$api_url}/api/operators/{$wagerCode}/game-history",
            'response' => $response->json(),
        ]);

        return $response->json();
    }

    /**
     * Get wallet balance from seamless API
     * 
     * @return array
     */
    public static function getWalletBalance(): array
    {
        $operator_code = config('seamless_key.agent_code');
        $secret_key = config('seamless_key.secret_key');
        $api_url = rtrim(config('seamless_key.api_url'), '/');
        
        // Get current timestamp in milliseconds
        $date = new \DateTime('now', new \DateTimeZone('Asia/Shanghai'));
        $request_time = $date->getTimestamp() * 1000; // Convert to milliseconds
        
        // Generate signature: md5(request_time + secret_key + "getwalletcurrencies" + operator_code)
        $sign_str = $request_time . $secret_key . 'getwalletcurrencies' . $operator_code;
        $sign = md5($sign_str);

        // Debug logging for signature generation
        Log::info('WagerListService GetWalletBalance Signature Debug', [
            'request_time' => $request_time,
            'secret_key' => $secret_key,
            'operator_code' => $operator_code,
            'sign_str' => $sign_str,
            'sign' => $sign,
        ]);

        // Build parameters
        $params = [
            'operator_code' => $operator_code,
            'sign' => $sign,
            'request_time' => $request_time,
        ];

        // Make API request
        $response = Http::get("{$api_url}/api/operators/wallet-balance", $params);

        // Log response for debugging
        Log::info('WagerListService GetWalletBalance API Response', [
            'status' => $response->status(),
            'url' => "{$api_url}/api/operators/wallet-balance",
            'response' => $response->json(),
        ]);

        return $response->json();
    }
}

