<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProductionContactSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        try {
            DB::beginTransaction();

            $this->command?->info('Seeding production contacts...');
            Log::info('ProductionContactSeeder: Starting to seed contacts');

            // Exact data from contacts.sql
            $contacts = [
                [
                    'id' => 1,
                    'agent_id' => 2,
                    'name' => 'John FB',
                    'value' => 'john.fb',
                    'type_id' => 1,
                    'created_at' => '2025-12-03 07:23:45',
                    'updated_at' => '2025-12-03 07:23:45',
                ],
                [
                    'id' => 2,
                    'agent_id' => 3,
                    'name' => 'Agent-999',
                    'value' => '09977781439',
                    'type_id' => 6,
                    'created_at' => '2025-12-03 07:23:45',
                    'updated_at' => '2025-12-19 04:29:58',
                ],
                [
                    'id' => 3,
                    'agent_id' => 2,
                    'name' => 'Tom IG',
                    'value' => '@tominsta',
                    'type_id' => 3,
                    'created_at' => '2025-12-03 07:23:45',
                    'updated_at' => '2025-12-03 07:23:45',
                ],
                [
                    'id' => 4,
                    'agent_id' => 2,
                    'name' => 'Lisa Line',
                    'value' => 'lisa_line',
                    'type_id' => 4,
                    'created_at' => '2025-12-03 07:23:45',
                    'updated_at' => '2025-12-03 07:23:45',
                ],
                [
                    'id' => 6,
                    'agent_id' => 1,
                    'name' => '999SLOT',
                    'value' => '+959977781439',
                    'type_id' => 6,
                    'created_at' => '2025-12-03 07:23:45',
                    'updated_at' => '2025-12-10 05:52:20',
                ],
                [
                    'id' => 7,
                    'agent_id' => 1,
                    'name' => '999SLOT',
                    'value' => '@Azm999Azm',
                    'type_id' => 7,
                    'created_at' => '2025-12-03 07:23:45',
                    'updated_at' => '2025-12-10 05:53:52',
                ],
                [
                    'id' => 8,
                    'agent_id' => 3,
                    'name' => 'Agent-999',
                    'value' => '@Azm999Azm',
                    'type_id' => 7,
                    'created_at' => '2025-12-03 07:23:45',
                    'updated_at' => '2025-12-19 04:33:09',
                ],
                [
                    'id' => 9,
                    'agent_id' => 32,
                    'name' => 'TestAgent',
                    'value' => '09789456123',
                    'type_id' => 1,
                    'created_at' => '2025-12-19 03:08:33',
                    'updated_at' => '2025-12-19 03:08:33',
                ],
                [
                    'id' => 10,
                    'agent_id' => 32,
                    'name' => 'TestPlayerAMK',
                    'value' => '09789456123',
                    'type_id' => 6,
                    'created_at' => '2025-12-19 03:09:17',
                    'updated_at' => '2025-12-19 03:09:17',
                ],
                [
                    'id' => 11,
                    'agent_id' => 32,
                    'name' => 'Agentdepshein',
                    'value' => '09789456123',
                    'type_id' => 7,
                    'created_at' => '2025-12-19 03:10:07',
                    'updated_at' => '2025-12-19 03:10:07',
                ],
            ];

            $inserted = 0;
            $updated = 0;
            $skipped = 0;

            foreach ($contacts as $contact) {
                try {
                    // Check if record already exists
                    $exists = DB::table('contacts')->where('id', $contact['id'])->exists();
                    
                    // Use updateOrInsert to handle existing records
                    DB::table('contacts')
                        ->updateOrInsert(
                            ['id' => $contact['id']],
                            $contact
                        );

                    if ($exists) {
                        $updated++;
                    } else {
                        $inserted++;
                    }
                    
                    Log::info("ProductionContactSeeder: Processed contact ID {$contact['id']} - {$contact['name']}");
                } catch (\Exception $e) {
                    $skipped++;
                    $this->command?->warn("Failed to insert contact ID {$contact['id']}: ".$e->getMessage());
                    Log::warning("ProductionContactSeeder: Failed to insert contact ID {$contact['id']}: ".$e->getMessage());
                }
            }

            // Reset the contacts sequence to prevent ID conflicts
            $this->resetContactsSequence();

            DB::commit();

            $this->command?->info("ProductionContactSeeder completed: {$inserted} inserted, {$updated} updated, {$skipped} skipped");
            Log::info("ProductionContactSeeder: Completed - {$inserted} inserted, {$updated} updated, {$skipped} skipped");
        } catch (\Exception $e) {
            DB::rollBack();
            $errorMsg = 'ProductionContactSeeder failed: '.$e->getMessage();
            $this->command?->error($errorMsg);
            Log::error($errorMsg);
            throw $e;
        }
    }

    /**
     * Reset the contacts sequence to the maximum ID
     */
    private function resetContactsSequence(): void
    {
        try {
            // Get the maximum ID from contacts table
            $maxId = DB::table('contacts')->max('id');
            
            if ($maxId === null) {
                $maxId = 0;
            }
            
            // Reset the sequence so the next ID will be maxId + 1
            // setval(sequence, value, is_called):
            // - is_called = true: next nextval() returns value + 1
            // - is_called = false: next nextval() returns value
            // We use true so that if maxId is 11, next ID will be 12
            DB::statement("SELECT setval('contacts_id_seq', ?, true)", [$maxId]);
            
            $nextId = $maxId + 1;
            $this->command?->info("Reset contacts_id_seq: max ID is {$maxId}, next ID will be {$nextId}");
            Log::info("ProductionContactSeeder: Reset contacts_id_seq: max ID is {$maxId}, next ID will be {$nextId}");
        } catch (\Exception $e) {
            // Log warning but don't fail the seeder
            $this->command?->warn("Failed to reset contacts_id_seq: ".$e->getMessage());
            Log::warning("ProductionContactSeeder: Failed to reset contacts_id_seq: ".$e->getMessage());
        }
    }
}

