<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BannerTextSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Check if user with id=1 exists, if not, find the first Owner user or use null
        $adminId = DB::table('users')->where('id', 1)->value('id');
        
        if (! $adminId) {
            // Try to find an Owner user (type = 10)
            $adminId = DB::table('users')->where('type', '10')->value('id');
        }
        
        if (! $adminId) {
            // If still no admin found, use null (assuming admin_id is nullable)
            $adminId = null;
        }

        $bannerTexts = [
            ['text' => 'မြန်မာနိုင်ငံရဲ့ အယုံကြည်ရဆုံး Slot Casino - Slot Casino Website - ကြီး', 'admin_id' => $adminId, 'created_at' => now(), 'updated_at' => now()],
            // Add more banner texts here if needed
        ];

        DB::table('banner_texts')->insert($bannerTexts);
    }
}
