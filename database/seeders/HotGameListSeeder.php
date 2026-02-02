<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class HotGameListSeeder extends Seeder
{
    private const ALLOWED_SLOT_PROVIDERS = [
        'pragmaticplay',
        'jdb',
        'pgsoft',
        'jili',
        'epicwin',
    ];

    public function run(): void
    {
        $jsonPath = base_path('app/Console/Commands/data/hot_game_list.json');

        if (! File::exists($jsonPath)) {
            $this->command?->warn('Hot game list JSON not found: '.$jsonPath);

            return;
        }

        $payload = json_decode(File::get($jsonPath), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->command?->error('Invalid JSON in hot_game_list.json: '.json_last_error_msg());

            return;
        }

        $games = $payload['provider_games'] ?? null;

        if (! is_array($games) || $games === []) {
            $this->command?->warn('No provider_games found in hot_game_list.json');

            return;
        }

        $gameTypeId = DB::table('game_types')
            ->where('code', 'SLOT')
            ->value('id');

        if (! $gameTypeId) {
            $this->command?->warn('Game type SLOT not found.');

            return;
        }

        // Reset current slot hot flags before applying the new list.
        DB::table('game_lists')
            ->where('game_type', 'SLOT')
            ->update(['hot_status' => '0']);

        $now = now();
        $seeded = 0;
        $order = 17; // Start order from 14

        foreach ($games as $game) {
            $gameCode = $game['game_code'] ?? null;

            if (! $gameCode) {
                continue;
            }

            $productCode = $game['product_code'] ?? null;
            $providerProductId = $game['product_id'] ?? null;
            $gameTypeCode = strtoupper($game['game_type'] ?? 'SLOT');

            if ($gameTypeCode !== 'SLOT') {
                continue;
            }

            $product = DB::table('products')
                ->when($providerProductId, fn ($query) => $query->where('provider_product_id', $providerProductId))
                ->when($productCode, fn ($query) => $query->where('product_code', $productCode))
                ->where('game_type', $gameTypeCode)
                ->first();

            if (! $product) {
                $product = DB::table('products')
                    ->when($productCode, fn ($query) => $query->where('product_code', $productCode))
                    ->where('game_type', $gameTypeCode)
                    ->first();
            }

            if (! $product) {
                $this->command?->warn("Product not found for game_code {$gameCode}");

                continue;
            }

            $normalizedProvider = $this->normalizeProviderName($product->provider ?? null);
            if (! in_array($normalizedProvider, self::ALLOWED_SLOT_PROVIDERS, true)) {
                continue;
            }

            DB::table('game_lists')->updateOrInsert(
                [
                    'game_code' => $gameCode,
                    'product_code' => $productCode ?? $product->product_code,
                ],
                [
                    'game_name' => $game['game_name'] ?? $gameCode,
                    'game_type' => $gameTypeCode,
                    'image_url' => $game['image_url'] ?? '',
                    'provider_product_id' => $providerProductId ?? $product->provider_product_id,
                    'game_type_id' => $gameTypeId,
                    'product_id' => $product->id,
                    'support_currency' => $game['support_currency'] ?? null,
                    'status' => $game['status'] ?? 'ACTIVATED',
                    'provider' => $product->provider ?? null,
                    'game_list_status' => $product->game_list_status ?? 1,
                    'hot_status' => '1',
                    'order' => $order,
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );

            $seeded++;
            $order++; // Increment order for next game
        }

        $this->command?->info("Seeded {$seeded} hot games.");
    }

    private function normalizeProviderName(?string $provider): string
    {
        if (! $provider) {
            return '';
        }

        return preg_replace('/[^a-z0-9]+/', '', strtolower($provider)) ?? '';
    }
}

