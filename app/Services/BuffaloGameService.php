<?php

namespace App\Services;

use App\Models\User;
use App\Models\FugoProvider;
use App\Models\FugoGameList;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Config;

class BuffaloGameService
{
    /**
     * Site configuration for TriBet
     * Note: These are now loaded from config/buffalo.php
     * Kept as constants for backward compatibility
     * IMPORTANT: site_url is used for token generation, lobby_url is for redirects
     */
    private const SITE_NAME = 'AZM999';
    private const SITE_PREFIX = 'az9';
    private const SITE_URL = 'https://master.azm999.com'; // Used for token generation - must match provider config
    private const SITE_LOBBY_URL = 'https://online.azm999.com'; // Used for lobby redirects

    /**
     * Resolve site configuration for the provided prefix.
     * IMPORTANT: For 'az9' prefix, site_url MUST be 'https://master.azm999.com' for token generation
     */
    private static function getResolvedSiteConfig(?string $sitePrefix = null): array
    {
        $default = [
            'name' => Config::get('buffalo.site.name', self::SITE_NAME),
            'prefix' => Config::get('buffalo.site.prefix', self::SITE_PREFIX),
            'site_url' => Config::get('buffalo.site.url', self::SITE_URL),
            'lobby_url' => Config::get(
                'buffalo.site.lobby_url',
                Config::get('app.url', self::SITE_LOBBY_URL)
            ) ?: self::SITE_LOBBY_URL,
        ];

        $targetPrefix = $sitePrefix ?? Config::get('buffalo.default_site', $default['prefix']);
        $sites = Config::get('buffalo.sites', []);

        if ($targetPrefix) {
            foreach ($sites as $key => $config) {
                if (strtolower((string) $key) === strtolower((string) $targetPrefix)) {
                    $resolvedConfig = array_merge($default, [
                        'name' => $config['name'] ?? ($config['site_name'] ?? $default['name']),
                        'prefix' => $config['prefix'] ?? $key,
                        'site_url' => $config['site_url'] ?? $default['site_url'],
                        'lobby_url' => $config['lobby_url'] ?? $default['lobby_url'],
                    ], $config);
                    
                    // CRITICAL FIX: Force correct site_url for 'az9' prefix (must match provider config)
                    if (strtolower($targetPrefix) === 'az9') {
                        $originalSiteUrl = $resolvedConfig['site_url'] ?? 'not set';
                        $resolvedConfig['site_url'] = 'https://master.azm999.com';
                        if ($originalSiteUrl !== 'https://master.azm999.com') {
                            Log::warning('Buffalo Game Service - Overriding site_url for az9 prefix', [
                                'original_site_url' => $originalSiteUrl,
                                'corrected_site_url' => 'https://master.azm999.com',
                                'reason' => 'Token generation must match provider config'
                            ]);
                        }
                    }
                    
                    return $resolvedConfig;
                }
            }
        }

        if ($sitePrefix && strtolower($sitePrefix) !== strtolower($default['prefix'])) {
            $default['prefix'] = $sitePrefix;
        }
        
        // CRITICAL FIX: Force correct site_url for 'az9' prefix (must match provider config)
        if (strtolower($default['prefix']) === 'az9') {
            $originalSiteUrl = $default['site_url'] ?? 'not set';
            $default['site_url'] = 'https://master.azm999.com';
            if ($originalSiteUrl !== 'https://master.azm999.com') {
                Log::warning('Buffalo Game Service - Overriding site_url for az9 prefix (default)', [
                    'original_site_url' => $originalSiteUrl,
                    'corrected_site_url' => 'https://master.azm999.com',
                    'reason' => 'Token generation must match provider config'
                ]);
            }
        }

        return $default;
    }

    /**
     * Generate UID (32 characters) for Buffalo API
     * Format: prefix(3) + base64_encoded_username(variable) + padding to 32 chars
     */
    public static function generateUid(string $userName, ?string $sitePrefix = null): string
    {
        $siteConfig = self::getResolvedSiteConfig($sitePrefix);

        // Encode username to base64 (URL-safe)
        $encoded = rtrim(strtr(base64_encode($userName), '+/', '-_'), '=');
        
        // Create a 32-character UID: prefix + encoded username + hash padding
        $prefix = $siteConfig['prefix']; // e.g. "AZM999"
        $remaining = 32 - strlen($prefix);
        
        // If encoded username is longer than available space, use hash instead
        if (strlen($encoded) > $remaining - 10) {
            $hash = md5($userName . $siteConfig['site_url']);
            return $prefix . substr($hash, 0, $remaining);
        }
        
        // Pad with hash to reach 32 characters total
        $padding = substr(md5($userName . $siteConfig['site_url']), 0, $remaining - strlen($encoded));
        return $prefix . $encoded . $padding;
    }

    /**
     * Generate token (64 characters) for Buffalo API
     * Note: Buffalo provider doesn't use secret keys
     */
    public static function generateToken(string $uid): string
    {
        // Generate a 64-character token using SHA256
        return hash('sha256', $uid . self::SITE_URL . time());
    }

    /**
     * Generate persistent token for user (stored in database)
     */
    public static function generatePersistentToken(string $userName, ?string $sitePrefix = null): string
    {
        $siteConfig = self::getResolvedSiteConfig($sitePrefix);

        // Generate persistent token using SHA256
        $uniqueString = $userName . $siteConfig['site_url'] . 'buffalo-persistent-token';
        return hash('sha256', $uniqueString);
    }

    /**
     * Verify token
     * Handles case-insensitive UID matching (game server may lowercase UID in URLs)
     */
    public static function verifyToken(string $uid, string $token): bool
    {
        try {
            // Extract username from UID (handles case-insensitive matching)
            $userName = self::extractUserNameFromUid($uid);
            
            if (!$userName) {
                Log::warning('TriBet Buffalo - Could not extract username from UID', [
                    'uid' => $uid,
                    'uid_length' => strlen($uid)
                ]);
                return false;
            }

            // Find user
            $user = User::where('user_name', $userName)->first();
            
            if (!$user) {
                Log::warning('AZM999 Buffalo - User not found for token verification', [
                    'userName' => $userName,
                    'uid' => $uid
                ]);
                return false;
            }

            // Extract site prefix from UID to use correct site config for token generation
            $sitePrefix = self::extractSitePrefixFromUid($uid);
            $siteConfig = self::getResolvedSiteConfig($sitePrefix);
            
            // Generate expected token (based on username and site config)
            $expectedToken = self::generatePersistentToken($userName, $sitePrefix);
            
            // Also generate expected UID for comparison
            $expectedUid = self::generateUid($userName, $sitePrefix);
            
            // Build token generation string for debugging
            $tokenGenerationString = $userName . $siteConfig['site_url'] . 'buffalo-persistent-token';

            $isValid = hash_equals($expectedToken, $token);
            
            // Log UID comparison for debugging
            $uidMatches = ($uid === $expectedUid || strtolower($uid) === strtolower($expectedUid));

            if ($isValid) {
                Log::info('TriBet Buffalo - Token verified successfully', [
                    'user' => $userName,
                    'uid_match' => $uidMatches,
                    'uid_received' => $uid,
                    'uid_expected' => $expectedUid,
                    'site_prefix' => $sitePrefix,
                ]);
            } else {
                Log::warning('AZM999 Buffalo - Token verification failed', [
                    'user' => $userName,
                    'site' => $siteConfig['name'] ?? 'Unknown',
                    'prefix' => $sitePrefix ?? 'default',
                    'expected_token' => $expectedToken,
                    'provided_token' => $token,
                    'token_generation_string' => $tokenGenerationString,
                    'uid_match' => $uidMatches,
                    'uid_received' => $uid,
                    'uid_expected' => $expectedUid,
                    'site_url' => $siteConfig['site_url'],
                ]);
            }

            return $isValid;

        } catch (\Exception $e) {
            Log::error('AZM999 Buffalo - Token verification error', [
                'error' => $e->getMessage(),
                'uid' => $uid,
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * Extract site prefix from UID
     * UID format: prefix(3) + encoded_username + padding
     */
    private static function extractSitePrefixFromUid(string $uid): ?string
    {
        // Try to match against known prefixes from config
        $sites = Config::get('buffalo.sites', []);
        $defaultPrefix = Config::get('buffalo.site.prefix', self::SITE_PREFIX);
        
        // Check all known prefixes (typically 3 characters)
        foreach ($sites as $key => $config) {
            $prefix = $config['prefix'] ?? $key;
            $prefixLength = strlen($prefix);
            
            if (strlen($uid) >= $prefixLength && substr($uid, 0, $prefixLength) === $prefix) {
                return $prefix;
            }
        }
        
        // Try default prefix
        if (strlen($uid) >= strlen($defaultPrefix) && substr($uid, 0, strlen($defaultPrefix)) === $defaultPrefix) {
            return $defaultPrefix;
        }
        
        // Fallback: extract first 3 characters as prefix
        if (strlen($uid) >= 3) {
            return substr($uid, 0, 3);
        }
        
        return $defaultPrefix;
    }

    /**
     * Extract username from UID
     */
    public static function extractUserNameFromUid(string $uid): ?string
    {
        // Get prefix from config or constant
        $prefix = Config::get('buffalo.site.prefix', self::SITE_PREFIX);
        $prefixLength = strlen($prefix);
        
        // Validate UID starts with prefix
        if (substr($uid, 0, $prefixLength) !== $prefix) {
            Log::warning('AZM999 Buffalo - UID does not start with expected prefix', [
                'uid' => $uid,
                'expected_prefix' => $prefix,
                'actual_prefix' => substr($uid, 0, $prefixLength)
            ]);
            // Try without prefix check (maybe prefix changed)
        }
        
        // Remove prefix
        $uidWithoutPrefix = substr($uid, $prefixLength);
        
        // Try to decode the base64 encoded part
        try {
            // Find the encoded username part (before the hash padding)
            // Start from full length and work backwards
            for ($len = strlen($uidWithoutPrefix); $len >= 4; $len--) {
                $encodedPart = substr($uidWithoutPrefix, 0, $len);
                
                // Add back padding if needed for base64
                $paddedEncoded = $encodedPart . str_repeat('=', (4 - strlen($encodedPart) % 4) % 4);
                
                // Try to decode (URL-safe base64)
                $decoded = base64_decode(strtr($paddedEncoded, '-_', '+/'), true);
                
                if ($decoded !== false && $decoded !== '') {
                    // Clean the decoded string - remove any non-printable characters
                    $cleaned = trim(preg_replace('/[^\x20-\x7E]/', '', $decoded));
                    
                    if (!empty($cleaned) && strlen($cleaned) >= 3) {
                        // Check if this username exists (use cleaned string)
                        $user = User::where('user_name', $cleaned)->first();
                        if ($user) {
                            Log::info('AZM999 Buffalo - Successfully extracted username from UID', [
                                'uid' => $uid,
                                'extracted_username' => $cleaned
                            ]);
                            return $cleaned;
                        }
                        
                        // Also try case-insensitive match
                        $user = User::whereRaw('LOWER(user_name) = ?', [strtolower($cleaned)])->first();
                        if ($user) {
                            Log::info('TriBet Buffalo - Successfully extracted username from UID (case-insensitive)', [
                                'uid' => $uid,
                                'extracted_username' => $user->user_name,
                                'matched_cleaned' => $cleaned
                            ]);
                            return $user->user_name;
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            Log::warning('TriBet Buffalo - Failed to decode UID', [
                'uid' => $uid,
                'error' => $e->getMessage()
            ]);
        }

        // Fallback: Search by generating UID for all users and comparing
        // This is more reliable but slower - use caching if needed
        // Also handle case-insensitive matching (game server might lowercase UID)
        try {
            Log::info('AZM999 Buffalo - Using fallback UID search', [
                'uid' => $uid,
                'uid_length' => strlen($uid)
            ]);
            
            // Try to optimize: only search users that might match
            // Check if UID length matches expected format
            if (strlen($uid) === 32) {
                $users = User::select('id', 'user_name')
                    ->whereNotNull('user_name')
                    ->where('user_name', '!=', '')
                    ->get();
                
                foreach ($users as $user) {
                    $generatedUid = self::generateUid($user->user_name);
                    // Case-sensitive match first (most common)
                    if ($generatedUid === $uid) {
                        Log::info('AZM999 Buffalo - Found username via fallback search (exact match)', [
                            'uid' => $uid,
                            'username' => $user->user_name
                        ]);
                        return $user->user_name;
                    }
                    // Case-insensitive match (game server might lowercase UID in URL)
                    if (strtolower($generatedUid) === strtolower($uid)) {
                        Log::info('TriBet Buffalo - Found username via fallback search (case-insensitive match)', [
                            'uid' => $uid,
                            'generated_uid' => $generatedUid,
                            'username' => $user->user_name,
                            'note' => 'UID case mismatch detected - game server may have lowercased UID'
                        ]);
                        return $user->user_name;
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('AZM999 Buffalo - Error in fallback UID search', [
                'uid' => $uid,
                'error' => $e->getMessage()
            ]);
        }

        Log::warning('AZM999 Buffalo - Could not extract username from UID', [
            'uid' => $uid,
            'uid_length' => strlen($uid),
            'prefix' => $prefix
        ]);

        return null;
    }

    /**
     * Get game URL for user
     */
    public static function getGameUrl(User $user, int $roomId = 2): string
    {
        $response = self::requestExternalLaunch($user, [
            'room_id' => $roomId,
        ]);

        return self::extractGameUrlFromResponse($response);
    }

    /**
     * Generate Buffalo authentication data
     * Returns UID and Token for frontend
     */
    // public static function generateBuffaloAuth(User $user): array
    // {
    //     $uid = self::generateUid($user->user_name);
    //     $token = self::generatePersistentToken($user->user_name);

    //     return [
    //         'uid' => $uid,
    //         'token' => $token,
    //         'user_name' => $user->user_name,
    //     ];
    // }

    public static function generateBuffaloAuth(User $user, ?string $sitePrefix = null): array
    {
        $uid = self::generateUid($user->user_name, $sitePrefix);
        $token = self::generatePersistentToken($user->user_name, $sitePrefix);

        return [
            'uid' => $uid,
            'token' => $token,
            'user_name' => $user->user_name,
        ];
    }

    /**
     * Generate Buffalo game URL with lobby URL
     */
    // public static function generateGameUrl(User $user, int $roomId, string $lobbyUrl): string
    // {
    //     $uid = self::generateUid($user->user_name);
    //     $token = self::generatePersistentToken($user->user_name);

    //     $data = [
    //         "gameId" => 23, // Buffalo game ID
    //         "roomId" => $roomId,
    //         "uid" => $uid,
    //         "token" => $token,
    //         "lobbyUrl" => $lobbyUrl,
    //     ];

    //     $baseUrl = 'http://prime7.wlkfkskakdf.com/';
    //     return $baseUrl . '?' . http_build_query($data);
    // }

    /**
     * Call provider's Game Login API to get game URL
     * 
     * @param User $user
     * @param int $roomId Room ID (1-4)
     * @param string $lobbyUrl Lobby redirect URL
     * @param int|null $gameId Game ID (23 for normal buffalo, 42 for scatter buffalo). Default: 23
     * @return string Game URL from provider
     * @throws \Exception If API call fails
     */
    public static function getGameUrlFromProvider(User $user, int $roomId = 1, string $lobbyUrl = '', ?int $gameId = null): string
    {
        $response = self::requestExternalLaunch($user, [
            'room_id' => $roomId,
            'game_id' => $gameId,
            'payload_override' => [
                'client_lobby_url' => $lobbyUrl,
            ],
        ]);

        return self::extractGameUrlFromResponse($response);
    }

    /**
     * Generate Buffalo game URL with lobby URL
     * This method now calls the provider's Game Login API
     * 
     * @param User $user
     * @param int $roomId Room ID (1-4)
     * @param string $lobbyUrl Lobby redirect URL
     * @param int|null $gameId Game ID (23 for normal, 42 for scatter). Default: 23
     * @return string Game URL from provider
     */
    public static function generateGameUrl(User $user, int $roomId = 1, string $lobbyUrl = '', ?int $gameId = null): string
    {
        return self::getGameUrlFromProvider($user, $roomId, $lobbyUrl, $gameId);
    }

    private static function extractGameUrlFromResponse(array $response): string
    {
        $gameUrl = $response['game_url']
            ?? $response['Url']
            ?? $response['url']
            ?? null;

        if (!$gameUrl) {
            throw new \RuntimeException('Game provider did not return a launch URL.');
        }

        return $gameUrl;
    }

    /**
     * Request a launch URL from the upstream Maxwin Myanmar endpoint.
     *
     * @throws \Exception
     */
    public static function requestExternalLaunch(User $user, array $options = []): array
    {
        $sitePrefix = $options['site_prefix'] ?? Config::get('buffalo.site.prefix', self::SITE_PREFIX);
        $siteConfig = self::getResolvedSiteConfig($sitePrefix);

        $typeId = (int) ($options['type_id'] ?? Config::get('buffalo.provider_launch.type_id', 1));
        $providerId = (int) ($options['provider_id'] ?? Config::get('buffalo.provider_launch.provider_id', 23));
        $gameId = (int) ($options['game_id'] ?? Config::get('buffalo.api.game_id', Config::get('buffalo.game_id', 23)));
        $roomId = (int) ($options['room_id'] ?? 1);

        $auth = self::generateBuffaloAuth($user, $siteConfig['prefix']);

        // Get lobby URL from options or use default from site config
        $lobbyUrl = $options['lobby_url'] ?? $siteConfig['lobby_url'] ?? self::SITE_LOBBY_URL;

        // Log token generation details for debugging
        $tokenGenerationString = $user->user_name . $siteConfig['site_url'] . 'buffalo-persistent-token';
        Log::info('Buffalo external launch - Token generation details', [
            'user_name' => $user->user_name,
            'site_url' => $siteConfig['site_url'],
            'site_prefix' => $siteConfig['prefix'],
            'token_generation_string' => $tokenGenerationString,
            'generated_token' => $auth['token'],
            'generated_uid' => $auth['uid'],
        ]);

        $payload = [
            'type_id' => $typeId,
            'provider_id' => $providerId,
            'game_id' => $gameId,
            'room_id' => $roomId,
            'uid' => $auth['uid'],
            'token' => $auth['token'],
            'site_prefix' => $siteConfig['prefix'],
            'lobby_url' => $lobbyUrl,
            'lobbyUrl' => $lobbyUrl, // Also send in camelCase for compatibility
        ];

        // Allow callers to merge in additional payload data (e.g., client_lobby_url)
        if (!empty($options['payload_override']) && is_array($options['payload_override'])) {
            $payload = array_merge($payload, $options['payload_override']);
        }

        $launchUrl = Config::get('buffalo.provider_launch.url');
        $timeout = Config::get('buffalo.provider_launch.timeout', Config::get('buffalo.api.timeout', 30));

        if (empty($launchUrl)) {
            throw new \RuntimeException('Buffalo provider launch URL is not configured.');
        }

        Log::info('Buffalo external launch - Requesting URL', [
            'endpoint' => $launchUrl,
            'user_id' => $user->id,
            'user_name' => $user->user_name,
            'payload' => [
                'type_id' => $typeId,
                'provider_id' => $providerId,
                'game_id' => $gameId,
                'room_id' => $roomId,
                'site_prefix' => $siteConfig['prefix'],
                'lobby_url' => $lobbyUrl,
                'uid_preview' => substr($auth['uid'], 0, 6) . '***',
                'token_preview' => substr($auth['token'], 0, 10) . '***',
            ],
        ]);

        $response = Http::timeout($timeout)
            ->withOptions(['verify' => false])
            ->acceptJson()
            ->post($launchUrl, $payload);

        if (!$response->successful()) {
            $responseBody = $response->body();
            $responseData = $response->json();
            
            Log::error('Buffalo external launch - HTTP error', [
                'status' => $response->status(),
                'body' => $responseBody,
                'endpoint' => $launchUrl,
                'user_id' => $user->id,
                'user_name' => $user->user_name,
                'payload_sent' => [
                    'type_id' => $typeId,
                    'provider_id' => $providerId,
                    'game_id' => $gameId,
                    'room_id' => $roomId,
                    'site_prefix' => $siteConfig['prefix'],
                    'lobby_url' => $lobbyUrl,
                    'uid_preview' => substr($auth['uid'], 0, 6) . '***',
                ],
                'provider_response' => $responseData,
            ]);

            $errorMessage = 'Failed to reach Buffalo provider launch endpoint.';
            if (isset($responseData['msg'])) {
                $errorMessage .= ' Provider message: ' . $responseData['msg'];
            } elseif (isset($responseData['message'])) {
                $errorMessage .= ' Provider message: ' . $responseData['message'];
            }

            throw new \RuntimeException($errorMessage);
        }

        $responseData = $response->json();

        if (!is_array($responseData)) {
            Log::error('Buffalo external launch - Invalid response format', [
                'response' => $response->body(),
                'endpoint' => $launchUrl,
            ]);

            throw new \RuntimeException('Buffalo provider returned an unexpected response.');
        }

        Log::info('Buffalo external launch - Success', [
            'user_id' => $user->id,
            'user_name' => $user->user_name,
            'room_id' => $roomId,
            'game_id' => $gameId,
            'site_prefix' => $siteConfig['prefix'],
            'provider_response_code' => $responseData['code'] ?? null,
        ]);

        return $responseData;
    }

    

    /**
     * Get room configuration from database or fallback to static config
     */
    public static function getRoomConfig(): array
    {
        // Try to get room config from FugoProvider database
        try {
            $providers = FugoProvider::where('provider', 'African Buffalo')
                ->select('roomId', 'gameId', 'name')
                ->distinct()
                ->orderBy('roomId')
                ->get();

            if ($providers->isNotEmpty()) {
                $roomConfig = [];
                $minBetMap = [
                    1 => 50,
                    2 => 500,
                    3 => 5000,
                    4 => 10000,
                ];
                $levelMap = [
                    1 => 'Low',
                    2 => 'Medium',
                    3 => 'High',
                    4 => 'VIP',
                ];

                foreach ($providers as $provider) {
                    $roomId = $provider->roomId;
                    if (!isset($roomConfig[$roomId])) {
                        $roomConfig[$roomId] = [
                            'min_bet' => $minBetMap[$roomId] ?? 50,
                            'name' => $provider->name ?? ($minBetMap[$roomId] ?? 50) . ' အခန်း',
                            'level' => $levelMap[$roomId] ?? 'Low',
                        ];
                    }
                }

                if (!empty($roomConfig)) {
                    return $roomConfig;
                }
            }
        } catch (\Exception $e) {
            Log::warning('Buffalo Game Service - Failed to get room config from database, using fallback', [
                'error' => $e->getMessage()
            ]);
        }

        // Fallback to static configuration
        return [
            1 => ['min_bet' => 50, 'name' => '50 အခန်း', 'level' => 'Low'],
            2 => ['min_bet' => 500, 'name' => '500 အခန်း', 'level' => 'Medium'],
            3 => ['min_bet' => 5000, 'name' => '5000 အခန်း', 'level' => 'High'],
            4 => ['min_bet' => 10000, 'name' => '10000 အခန်း', 'level' => 'VIP'],
        ];
    }

    /**
     * Get available rooms for user based on balance
     */
    public static function getAvailableRooms(User $user): array
    {
        $userBalance = $user->balanceFloat; // Use bavix wallet trait
        $rooms = self::getRoomConfig();
        $availableRooms = [];

        foreach ($rooms as $roomId => $config) {
            if ($userBalance >= $config['min_bet']) {
                $availableRooms[$roomId] = $config;
            }
        }

        return $availableRooms;
    }

    public static function getLobbyUrl(?string $sitePrefix = null): string
    {
        $site = self::getResolvedSiteConfig($sitePrefix);

        return (string) ($site['lobby_url'] ?? self::SITE_LOBBY_URL);
    }

    public static function getSiteInfo(): array
    {
        $site = self::getResolvedSiteConfig();

        return [
            'site_name' => $site['name'],
            'site_prefix' => $site['prefix'],
        ];
    }

    /**
     * Get game provider by gameId and roomId
     */
    public static function getGameProvider(?int $gameId, ?int $roomId): ?FugoProvider
    {
        if (!$gameId || !$roomId) {
            return null;
        }

        try {
            return FugoProvider::where('gameId', $gameId)
                ->where('roomId', $roomId)
                ->where('provider', 'African Buffalo')
                ->first();
        } catch (\Exception $e) {
            Log::error('Buffalo Game Service - Failed to get game provider', [
                'game_id' => $gameId,
                'room_id' => $roomId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Validate if gameId and roomId combination exists
     */
    public static function validateGameRoom(?int $gameId, ?int $roomId): bool
    {
        if (!$gameId || !$roomId) {
            return false;
        }

        try {
            return FugoProvider::where('gameId', $gameId)
                ->where('roomId', $roomId)
                ->where('provider', 'African Buffalo')
                ->exists();
        } catch (\Exception $e) {
            Log::error('Buffalo Game Service - Failed to validate game room', [
                'game_id' => $gameId,
                'room_id' => $roomId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Get all available games from FugoGameList
     */
    public static function getAvailableGames(?string $provider = 'African Buffalo'): array
    {
        try {
            $games = FugoGameList::where('provider', $provider ?? 'African Buffalo')
                ->with('fugoProvider')
                ->orderBy('gameId')
                ->orderBy('roomId')
                ->get();

            return $games->map(function ($game) {
                return [
                    'id' => $game->id,
                    'name' => $game->name,
                    'image' => $game->image,
                    'type' => $game->type,
                    'provider' => $game->provider,
                    'gameId' => $game->gameId,
                    'roomId' => $game->roomId,
                    'provider_info' => $game->fugoProvider ? [
                        'jackpot' => $game->fugoProvider->jackpot,
                        'rtp' => $game->fugoProvider->rtp,
                        'BuyFreeSpin' => $game->fugoProvider->BuyFreeSpin,
                        'transfer_wallet' => $game->fugoProvider->transfer_wallet,
                        'seamless' => $game->fugoProvider->seamless,
                    ] : null,
                ];
            })->toArray();
        } catch (\Exception $e) {
            Log::error('Buffalo Game Service - Failed to get available games', [
                'provider' => $provider,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Get games grouped by gameId
     */
    public static function getGamesByGameId(?int $gameId = null): array
    {
        try {
            $query = FugoGameList::where('provider', 'African Buffalo')
                ->with('fugoProvider');

            if ($gameId) {
                $query->where('gameId', $gameId);
            }

            $games = $query->orderBy('gameId')
                ->orderBy('roomId')
                ->get();

            return $games->map(function ($game) {
                return [
                    'id' => $game->id,
                    'name' => $game->name,
                    'image' => $game->image,
                    'type' => $game->type,
                    'provider' => $game->provider,
                    'gameId' => $game->gameId,
                    'roomId' => $game->roomId,
                ];
            })->toArray();
        } catch (\Exception $e) {
            Log::error('Buffalo Game Service - Failed to get games by gameId', [
                'game_id' => $gameId,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Get room configuration with game information from database
     */
    public static function getRoomConfigWithGames(): array
    {
        $roomConfig = self::getRoomConfig();
        
        try {
            $providers = FugoProvider::where('provider', 'African Buffalo')
                ->select('roomId', 'gameId', 'name', 'image')
                ->orderBy('roomId')
                ->orderBy('gameId')
                ->get();

            foreach ($providers as $provider) {
                $roomId = $provider->roomId;
                if (isset($roomConfig[$roomId])) {
                    if (!isset($roomConfig[$roomId]['games'])) {
                        $roomConfig[$roomId]['games'] = [];
                    }
                    $roomConfig[$roomId]['games'][] = [
                        'gameId' => $provider->gameId,
                        'name' => $provider->name,
                        'image' => $provider->image,
                    ];
                }
            }
        } catch (\Exception $e) {
            Log::warning('Buffalo Game Service - Failed to get room config with games', [
                'error' => $e->getMessage()
            ]);
        }

        return $roomConfig;
    }
}