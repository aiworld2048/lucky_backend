<?php

namespace Database\Seeders;

use App\Enums\TransactionName;
use App\Models\User;
use App\Services\WalletService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class UsersTableSeeder extends Seeder
{
    public function run(): void
    {
        try {
            // Try multiple possible file paths
            $possiblePaths = [
                base_path('users.sql'),
                base_path('database/seeders/users.sql'),
                storage_path('app/users.sql'),
            ];

            $sqlPath = null;
            foreach ($possiblePaths as $path) {
                if (File::exists($path) && is_readable($path)) {
                    $sqlPath = $path;
                    break;
                }
            }

            if (! $sqlPath) {
                $errorMsg = 'users.sql not found or not readable. Tried: '.implode(', ', $possiblePaths);
                $this->command?->error($errorMsg);
                Log::error($errorMsg);

                return;
            }

            $this->command?->info("Found users.sql at: {$sqlPath}");
            Log::info("UsersTableSeeder: Reading users.sql from {$sqlPath}");

            $sql = File::get($sqlPath);
            if (empty($sql)) {
                $errorMsg = 'users.sql file is empty';
                $this->command?->error($errorMsg);
                Log::error($errorMsg);

                return;
            }

            $this->command?->info('Parsing SQL file...');
            Log::info('UsersTableSeeder: Parsing SQL file, size: '.strlen($sql).' bytes');

            // Parse INSERT statements more robustly
            $matches = $this->parseInsertStatements($sql);

            if (empty($matches)) {
                $errorMsg = 'No user inserts found in users.sql. File might have different format.';
                $this->command?->error($errorMsg);
                Log::error($errorMsg);
                Log::debug('UsersTableSeeder: SQL file preview (first 500 chars): '.substr($sql, 0, 500));

                return;
            }

            $this->command?->info('Found '.count($matches).' user records to process');
            Log::info('UsersTableSeeder: Found '.count($matches).' user records');

            // First, collect and validate all user data
            $usersToProcess = [];
            foreach ($matches as $index => $match) {
                try {
                    $columns = $this->parseColumns($match[1]);
                    $values = $this->parseValuesRow($match[2]);

                    if (count($columns) !== count($values)) {
                        $this->command?->warn("Row ".($index + 1).": Column/value count mismatch (columns: ".count($columns).", values: ".count($values).")");
                        Log::warning("UsersTableSeeder: Row ".($index + 1)." column/value mismatch");
                        continue;
                    }

                    $data = array_combine($columns, $values);
                    $balance = $data['balance'] ?? null;

                    // Skip SubAgent users (type 50)
                    $userType = isset($data['type']) ? (int) $data['type'] : null;
                    if ($userType === 50) {
                        $this->command?->info("Row ".($index + 1).": Skipping SubAgent user (type 50): ".($data['user_name'] ?? 'unknown'));
                        Log::info("UsersTableSeeder: Skipping SubAgent user (type 50): ".($data['user_name'] ?? 'unknown'));
                        continue;
                    }

                    if (empty($data['user_name'])) {
                        $this->command?->warn("Row ".($index + 1).": Skipping user with empty user_name");
                        Log::warning("UsersTableSeeder: Row ".($index + 1)." has empty user_name");
                        continue;
                    }

                    // Store user data with original index for error reporting
                    $usersToProcess[] = [
                        'index' => $index + 1,
                        'data' => $data,
                        'balance' => $balance,
                    ];
                } catch (\Exception $e) {
                    $errorMsg = "Row ".($index + 1).": Error parsing user data - ".$e->getMessage();
                    $this->command?->error($errorMsg);
                    Log::error("UsersTableSeeder: {$errorMsg}");
                }
            }

            // Sort users by ID to ensure parent users (agents) are created before children (players)
            usort($usersToProcess, function ($a, $b) {
                $idA = isset($a['data']['id']) ? (int) $a['data']['id'] : PHP_INT_MAX;
                $idB = isset($b['data']['id']) ? (int) $b['data']['id'] : PHP_INT_MAX;
                return $idA <=> $idB;
            });

            $this->command?->info('Processing '.count($usersToProcess).' users using two-pass approach (insert first, then update agent_id)');
            Log::info('UsersTableSeeder: Processing '.count($usersToProcess).' users (two-pass approach)');

            $seeded = 0;
            $failed = 0;
        $walletService = new WalletService;
            $agentIdUpdates = []; // Store agent_id updates for second pass

            DB::beginTransaction();

            try {
                foreach ($usersToProcess as $userInfo) {
                    try {
                        $data = $userInfo['data'];
                        $balance = $userInfo['balance'];
                        $originalIndex = $userInfo['index'];

                        if (empty($data['password'])) {
                            $data['password'] = Hash::make('azm999');
                        }

                        // Store original agent_id for second pass update
                        $originalAgentId = $data['agent_id'] ?? null;
                        
                        // Set agent_id to NULL for first pass to avoid foreign key constraints
                        $data['agent_id'] = null;
                        
                        // Store the ID if provided
                        $userIdFromData = $data['id'] ?? null;
                        unset($data['balance'], $data['id']);

                        // Insert or update user with explicit ID handling
                        $userId = null;
                        $result = false;
                        
                        if ($userIdFromData) {
                            // Try to insert with specific ID, or update if exists
                            $existing = DB::table('users')->where('id', $userIdFromData)->first();
                            if ($existing) {
                                // Update existing user
                                $result = DB::table('users')->where('id', $userIdFromData)->update($data);
                                $userId = $userIdFromData;
                                Log::debug("UsersTableSeeder: Updated existing user ID {$userIdFromData}: ".($data['user_name'] ?? 'unknown'));
                            } else {
                                // Insert new user with specific ID
                                $data['id'] = $userIdFromData;
                                try {
                                    $result = DB::table('users')->insert($data);
                                    $userId = $userIdFromData;
                                    Log::debug("UsersTableSeeder: Inserted new user with ID {$userIdFromData}: ".($data['user_name'] ?? 'unknown'));
                                } catch (\Exception $e) {
                                    $this->command?->warn("Row {$originalIndex}: Failed to insert user with ID {$userIdFromData}: ".$e->getMessage());
                                    Log::warning("UsersTableSeeder: Failed to insert user with ID {$userIdFromData}: ".$e->getMessage());
                                    $result = false;
                                }
                            }
                        } else {
                            // No ID specified, use updateOrInsert with user_name
                            $result = DB::table('users')->updateOrInsert(['user_name' => $data['user_name']], $data);
                            if ($result) {
                                $userId = DB::table('users')->where('user_name', $data['user_name'])->value('id');
                            }
                        }

                        if (! $result || ! $userId) {
                            $this->command?->warn("Row {$originalIndex}: Failed to insert/update user: ".($data['user_name'] ?? 'unknown'));
                            Log::warning("UsersTableSeeder: Failed to insert/update user: ".($data['user_name'] ?? 'unknown'));
                            $failed++;
                            continue;
                        }

                        $seeded++;

                        // Store agent_id update for second pass
                        if ($originalAgentId) {
                            $agentIdUpdates[] = [
                                'user_id' => $userId,
                                'agent_id' => $originalAgentId,
                                'index' => $originalIndex,
                            ];
                        }

                        // Sync wallet balance using WalletService (no balance column in users table)
                        if (is_numeric($balance)) {
                            $this->syncWalletBalance(
                                $walletService,
                                $userId,
                                $data['user_name'],
                                (float) $balance
                            );
                        }
                    } catch (\Exception $e) {
                        $failed++;
                        $errorMsg = "Row {$userInfo['index']}: Error processing user - ".$e->getMessage();
                        $this->command?->error($errorMsg);
                        Log::error("UsersTableSeeder: {$errorMsg}", [
                            'exception' => $e->getMessage(),
                            'trace' => $e->getTraceAsString(),
                        ]);
                    }
                }

                DB::commit();
                $this->command?->info("Pass 1 complete: Inserted {$seeded} users");
                Log::info("UsersTableSeeder: Pass 1 complete - Inserted {$seeded} users");
            } catch (\Exception $e) {
                DB::rollBack();
                $errorMsg = 'Pass 1 (insert) failed: '.$e->getMessage();
                $this->command?->error($errorMsg);
                Log::error("UsersTableSeeder: {$errorMsg}", [
                    'exception' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                throw $e;
            }

            // PASS 2: Update agent_id for users that need it
            if (count($agentIdUpdates) > 0) {
                $this->command?->info('Pass 2: Updating agent_id for '.count($agentIdUpdates).' users');
                Log::info("UsersTableSeeder: Pass 2 - Updating agent_id for ".count($agentIdUpdates)." users");

                DB::beginTransaction();

                try {
                    $updated = 0;
                    foreach ($agentIdUpdates as $update) {
                        try {
                            // Check if the agent exists
                            $agentExists = DB::table('users')->where('id', $update['agent_id'])->exists();
                            
                            if ($agentExists) {
                                DB::table('users')->where('id', $update['user_id'])->update(['agent_id' => $update['agent_id']]);
                                $updated++;
                            } else {
                                $this->command?->warn("Row {$update['index']}: Agent ID {$update['agent_id']} does not exist, skipping agent_id update for user {$update['user_id']}");
                                Log::warning("UsersTableSeeder: Agent ID {$update['agent_id']} does not exist for user {$update['user_id']}");
                            }
                        } catch (\Exception $e) {
                            $this->command?->warn("Row {$update['index']}: Failed to update agent_id for user {$update['user_id']}: ".$e->getMessage());
                            Log::warning("UsersTableSeeder: Failed to update agent_id for user {$update['user_id']}: ".$e->getMessage());
                        }
                    }

                    DB::commit();
                    $this->command?->info("Pass 2 complete: Updated agent_id for {$updated} users");
                    Log::info("UsersTableSeeder: Pass 2 complete - Updated agent_id for {$updated} users");
                } catch (\Exception $e) {
                    DB::rollBack();
                    $errorMsg = 'Pass 2 (update agent_id) failed: '.$e->getMessage();
                    $this->command?->error($errorMsg);
                    Log::error("UsersTableSeeder: {$errorMsg}", [
                        'exception' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                }
            }

            // Reset PostgreSQL sequence to prevent ID conflicts when creating new users
            $this->resetUsersSequence();

            $successMsg = "Seeded {$seeded} users from users.sql (failed: {$failed})";
            $this->command?->info($successMsg);
            Log::info("UsersTableSeeder: {$successMsg}");
        } catch (\Throwable $e) {
            $errorMsg = 'Fatal error in UsersTableSeeder: '.$e->getMessage();
            $this->command?->error($errorMsg);
            Log::error("UsersTableSeeder: {$errorMsg}", [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            throw $e;
        }
    }

    private function syncWalletBalance(
        WalletService $walletService,
        ?int $userId,
        string $userName,
        float $targetBalance
    ): void {
        try {
            $user = $userId
                ? User::find($userId)
                : User::where('user_name', $userName)->first();

            if (! $user) {
                $this->command?->warn("User not found for wallet sync: {$userName} (ID: {$userId})");
                Log::warning("UsersTableSeeder: User not found for wallet sync: {$userName}");

                return;
            }

            $currentBalance = $user->balanceFloat ?? 0;
            $difference = $targetBalance - $currentBalance;

            if (abs($difference) < 0.0001) {
                return; // Balance already matches
            }

            if ($difference > 0) {
                $walletService->deposit(
                    $user,
                    $difference,
                    TransactionName::CreditAdjustment,
                    ['seed_source' => 'users.sql']
                );
                Log::info("UsersTableSeeder: Deposited {$difference} to {$userName} (target: {$targetBalance}, was: {$currentBalance})");
            } else {
                $walletService->withdraw(
                    $user,
                    abs($difference),
                    TransactionName::DebitAdjustment,
                    ['seed_source' => 'users.sql']
                );
                Log::info("UsersTableSeeder: Withdrew ".abs($difference)." from {$userName} (target: {$targetBalance}, was: {$currentBalance})");
            }
        } catch (\Throwable $e) {
            $errorMsg = "Failed to sync wallet for {$userName}: {$e->getMessage()}";
            $this->command?->warn($errorMsg);
            Log::error("UsersTableSeeder: {$errorMsg}", [
                'user_id' => $userId,
                'user_name' => $userName,
                'target_balance' => $targetBalance,
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    private function parseColumns(string $columnsRaw): array
    {
        return array_map(
            fn ($column) => trim($column, " \n\r\t\""),
            explode(',', $columnsRaw)
        );
    }

    private function parseValuesRow(string $row): array
    {
        $values = [];
        $current = '';
        $inString = false;
        $length = strlen($row);

        for ($i = 0; $i < $length; $i++) {
            $char = $row[$i];

            if ($inString) {
                if ($char === "'") {
                    if ($i + 1 < $length && $row[$i + 1] === "'") {
                        $current .= "'";
                        $i++;
                        continue;
                    }
                    $inString = false;
                    continue;
                }

                $current .= $char;
                continue;
            }

            if ($char === "'") {
                $inString = true;
                continue;
            }

            if ($char === ',') {
                $values[] = $this->normalizeSqlValue($current);
                $current = '';
                continue;
            }

            $current .= $char;
        }

        $values[] = $this->normalizeSqlValue($current);

        return $values;
    }

    private function normalizeSqlValue(string $value): mixed
    {
        $value = trim($value);

        if (strtoupper($value) === 'NULL') {
            return null;
        }

        if ($value === 't') {
            return true;
        }

        if ($value === 'f') {
            return false;
        }

        if (is_numeric($value)) {
            return str_contains($value, '.') ? (float) $value : (int) $value;
        }

        return $value;
    }

    private function parseInsertStatements(string $sql): array
    {
        $matches = [];
        $pattern = '~INSERT\s+INTO\s+(?:["\']?public["\']?\.)?["\']?users["\']?\s*\(([^)]+)\)\s*VALUES\s*~i';
        
        $offset = 0;
        $insertCount = 0;
        while (preg_match($pattern, $sql, $match, PREG_OFFSET_CAPTURE, $offset)) {
            $insertCount++;
            $columns = $match[1][0];
            $valuesStart = $match[0][1] + strlen($match[0][0]);
            
            // Extract all value rows until semicolon
            $valueRows = $this->extractAllValueRows($sql, $valuesStart, $endPos);
            
            if (!empty($valueRows)) {
                // Create a match entry for each value row
                foreach ($valueRows as $valueRow) {
                    $matches[] = [
                        1 => $columns,
                        2 => $valueRow,
                    ];
                }
                $offset = $endPos;
                Log::debug("UsersTableSeeder: Parsed INSERT #{$insertCount}, found ".count($valueRows)." rows, next offset: {$offset}");
            } else {
                // Skip this match and continue - but log it for debugging
                // Try to find the semicolon or next INSERT to advance offset
                $nextSemicolon = strpos($sql, ';', $valuesStart);
                $nextInsert = stripos($sql, 'INSERT INTO', $valuesStart);
                
                if ($nextSemicolon !== false && ($nextInsert === false || $nextSemicolon < $nextInsert)) {
                    $offset = $nextSemicolon + 1;
                } elseif ($nextInsert !== false) {
                    $offset = $nextInsert;
                } else {
                    $offset = $valuesStart + 100; // Fallback: skip ahead
                }
                
                Log::warning("UsersTableSeeder: INSERT #{$insertCount} matched but no rows extracted. valuesStart: {$valuesStart}, advancing to offset: {$offset}");
            }
        }
        
        Log::info("UsersTableSeeder: Found {$insertCount} INSERT statements, extracted ".count($matches)." user rows");
        
        return $matches;
    }

    private function extractAllValueRows(string $sql, int $startPos, &$endPos): array
    {
        $rows = [];
        $pos = $startPos;
        $length = strlen($sql);
        
        // Skip whitespace
        while ($pos < $length && ctype_space($sql[$pos])) {
            $pos++;
        }
        
        if ($pos >= $length || $sql[$pos] !== '(') {
            $endPos = $pos;
            return [];
        }
        
        // Extract all rows until we hit a semicolon or new INSERT statement
        while ($pos < $length) {
            // Skip whitespace
            while ($pos < $length && ctype_space($sql[$pos])) {
                $pos++;
            }
            
            if ($pos >= $length) {
                break;
            }
            
            // Check if we're at the start of a new INSERT statement (before extracting current row)
            if (preg_match('~\s*INSERT\s+INTO~i', substr($sql, $pos), $nextInsert)) {
                $endPos = $pos;
                break;
            }
            
            // Check if we've reached the semicolon (end of INSERT statement) - but only if we're not at a row start
            // We should only hit semicolon AFTER extracting a row
            if ($sql[$pos] === ';' && empty($rows)) {
                // Semicolon before any rows extracted - this shouldn't happen, but handle it
                $endPos = $pos + 1;
                break;
            }
            
            // Extract one value row (from opening paren to closing paren)
            if ($sql[$pos] !== '(') {
                // Not a valid row start - might be semicolon or something else
                if ($sql[$pos] === ';') {
                    $endPos = $pos + 1;
                    break;
                }
                // Skip invalid character and continue
                $pos++;
                continue;
            }
            
            $row = $this->extractSingleValueRow($sql, $pos, $rowEnd);
            
            if ($row !== null) {
                $rows[] = $row;
                $pos = $rowEnd;
                
                // Skip whitespace after the row
                while ($pos < $length && ctype_space($sql[$pos])) {
                    $pos++;
                }
                
                // Check if next char is comma (more rows) or semicolon (end)
                if ($pos < $length && $sql[$pos] === ',') {
                    $pos++; // Skip comma
                    continue; // Continue to next row
                } elseif ($pos < $length && $sql[$pos] === ';') {
                    $endPos = $pos + 1;
                    break;
                } elseif ($pos < $length) {
                    // Check if we're at a new INSERT statement
                    if (preg_match('~\s*INSERT\s+INTO~i', substr($sql, $pos), $nextInsert)) {
                        $endPos = $pos;
                        break;
                    }
                }
            } else {
                // Failed to extract row - might be at end or invalid format
                if ($pos < $length && $sql[$pos] === ';') {
                    $endPos = $pos + 1;
                    break;
                }
                // Skip invalid character and continue
                $pos++;
            }
        }
        
        if (!isset($endPos)) {
            $endPos = $pos;
        }
        
        return $rows;
    }

    private function extractSingleValueRow(string $sql, int $startPos, &$endPos): ?string
    {
        $pos = $startPos;
        $length = strlen($sql);
        $depth = 0;
        $inString = false;
        $stringChar = null;
        $rowStart = $pos;
        
        // Skip whitespace
        while ($pos < $length && ctype_space($sql[$pos])) {
            $pos++;
        }
        
        if ($pos >= $length || $sql[$pos] !== '(') {
            $endPos = $pos;
            return null;
        }
        
        $pos++; // Skip opening (
        $depth = 1;
        $rowStart = $pos;
        
        while ($pos < $length && $depth > 0) {
            $char = $sql[$pos];
            
            if ($inString) {
                if ($char === $stringChar) {
                    // Check for escaped quote (double quote)
                    if ($pos + 1 < $length && $sql[$pos + 1] === $stringChar) {
                        $pos += 2;
                        continue;
                    }
                    $inString = false;
                    $stringChar = null;
                }
            } else {
                if ($char === "'" || $char === '"') {
                    $inString = true;
                    $stringChar = $char;
                } elseif ($char === '(') {
                    $depth++;
                } elseif ($char === ')') {
                    $depth--;
                    if ($depth === 0) {
                        // Found the matching closing paren
                        $endPos = $pos + 1;
                        return substr($sql, $rowStart, $pos - $rowStart);
                    }
                }
            }
            
            $pos++;
        }
        
        $endPos = $pos;
        return null; // No matching closing paren found
    }

    /**
     * Reset PostgreSQL sequence for users table to prevent ID conflicts.
     * This is necessary after inserting users with explicit IDs from users.sql.
     */
    private function resetUsersSequence(): void
    {
        try {
            // Get the maximum ID from users table
            $maxId = DB::table('users')->max('id');
            
            if ($maxId === null) {
                $maxId = 0;
            }
            
            // Reset the sequence so the next ID will be maxId + 1
            // setval(sequence, value, is_called):
            // - is_called = true: next nextval() returns value + 1
            // - is_called = false: next nextval() returns value
            // We use true so that if maxId is 143, next ID will be 144
            DB::statement("SELECT setval('users_id_seq', ?, true)", [$maxId]);
            
            $nextId = $maxId + 1;
            $this->command?->info("Reset users_id_seq: max ID is {$maxId}, next ID will be {$nextId}");
            Log::info("UsersTableSeeder: Reset users_id_seq: max ID is {$maxId}, next ID will be {$nextId}");
        } catch (\Exception $e) {
            // Log warning but don't fail the seeder
            $this->command?->warn("Failed to reset users_id_seq: ".$e->getMessage());
            Log::warning("UsersTableSeeder: Failed to reset users_id_seq: ".$e->getMessage());
        }
    }
}
