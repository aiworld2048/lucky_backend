<?php

namespace Database\Seeders;

use App\Models\FugoGameList;
use App\Models\FugoProvider;
use Illuminate\Database\Seeder;

class FugoGameListSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Game data from JSON
        $games = [
            [
                'name' => 'African Buffalo (50)',
                'image' => 'https://buffalo-555.s3.ap-southeast-1.amazonaws.com/50.png',
                'type' => 'slot',
                'provider' => 'African Buffalo',
                'gameId' => 23,
                'roomId' => 1,
                'order' => 1,
            ],
            [
                'name' => 'African Buffalo (500)',
                'image' => 'https://buffalo-555.s3.ap-southeast-1.amazonaws.com/500.png',
                'type' => 'slot',
                'provider' => 'African Buffalo',
                'gameId' => 23,
                'roomId' => 2,
                'order' => 2,
            ],
            [
                'name' => 'African Buffalo (5000)',
                'image' => 'https://buffalo-555.s3.ap-southeast-1.amazonaws.com/5000.png',
                'type' => 'slot',
                'provider' => 'African Buffalo',
                'gameId' => 23,
                'roomId' => 3,
                'order' => 3,
            ],
            [
                'name' => 'African Buffalo (10000)',
                'image' => 'https://buffalo-555.s3.ap-southeast-1.amazonaws.com/10000.png',
                'type' => 'slot',
                'provider' => 'African Buffalo',
                'gameId' => 23,
                'roomId' => 4,
                'order' => 4,
            ],
            [
                'name' => 'African Buffalo Scatter (50)',
                'image' => 'https://buffalo-555.s3.ap-southeast-1.amazonaws.com/scatter-300x200.jpg',
                'type' => 'slot',
                'provider' => 'African Buffalo',
                'gameId' => 42,
                'roomId' => 1,
                'order' => 5,
            ],
            [
                'name' => 'African Buffalo Scatter (500)',
                'image' => 'https://buffalo-555.s3.ap-southeast-1.amazonaws.com/scatter-300x200.jpg',
                'type' => 'slot',
                'provider' => 'African Buffalo',
                'gameId' => 42,
                'roomId' => 2,
                'order' => 6,
            ],
            [
                'name' => 'Fortune Cat',
                'image' => 'https://buffalo-555.s3.ap-southeast-1.amazonaws.com/FC-200x200.jpg',
                'type' => 'slot',
                'provider' => 'African Buffalo',
                'gameId' => 122,
                'roomId' => 1,
                'order' => 7,
            ],
            [
                'name' => 'Autumn Moon',
                'image' => 'https://buffalo-555.s3.ap-southeast-1.amazonaws.com/AM-200x200.jpg',
                'type' => 'slot',
                'provider' => 'African Buffalo',
                'gameId' => 123,
                'roomId' => 1,
                'order' => 8,
            ],
            [
                'name' => 'Golden Century',
                'image' => 'https://buffalo-555.s3.ap-southeast-1.amazonaws.com/GC-200x200.jpg',
                'type' => 'slot',
                'provider' => 'African Buffalo',
                'gameId' => 124,
                'roomId' => 1,
                'order' => 9,
            ],
            [
                'name' => 'Autumn Moon 88',
                'image' => 'https://buffalo-555.s3.ap-southeast-1.amazonaws.com/am88.jpg',
                'type' => 'slot',
                'provider' => 'African Buffalo',
                'gameId' => 125,
                'roomId' => 1,
                'order' => 10,
            ],
            [
                'name' => 'Golden Century 88',
                'image' => 'https://buffalo-555.s3.ap-southeast-1.amazonaws.com/gc88.jpg',
                'type' => 'slot',
                'provider' => 'African Buffalo',
                'gameId' => 126,
                'roomId' => 1,
                'order' => 11,
            ],
            [
                'name' => 'LuxuryLine Buffalo ',
                'image' => 'https://buffalo-555.s3.ap-southeast-1.amazonaws.com/llb.jpg',
                'type' => 'slot',
                'provider' => 'African Buffalo',
                'gameId' => 128,
                'roomId' => 1,
                'order' => 12,
            ],
            [
                'name' => 'Peace LongLife',
                'image' => 'https://buffalo-555.s3.ap-southeast-1.amazonaws.com/pll.jpg',
                'type' => 'slot',
                'provider' => 'African Buffalo',
                'gameId' => 134,
                'roomId' => 1,
                'order' => 13,
            ],
            [
                'name' => 'Happy Prosperous',
                'image' => 'https://buffalo-555.s3.ap-southeast-1.amazonaws.com/hp.jpg',
                'type' => 'slot',
                'provider' => 'African Buffalo',
                'gameId' => 158,
                'roomId' => 1,
                'order' => 14,
            ],
            [
                'name' => 'Grand Dragons',
                'image' => 'https://buffalo-555.s3.ap-southeast-1.amazonaws.com/gd.jpg',
                'type' => 'slot',
                'provider' => 'African Buffalo',
                'gameId' => 119,
                'roomId' => 1,
                'order' => 15,
                ],
            [
                'name' => 'African Buffalo Megaways',
                'image' => 'https://buffalo-555.s3.ap-southeast-1.amazonaws.com/mg200x200.jpg',
                'type' => 'slot',
                'provider' => 'African Buffalo',
                'gameId' => 204,
                'roomId' => 1,
                'order' => 16,
            ],
        ];

        // Match each game to its provider by name, gameId, and roomId
        foreach ($games as $game) {
            $provider = FugoProvider::where('name', $game['name'])
                ->where('gameId', $game['gameId'])
                ->where('roomId', $game['roomId'])
                ->first();

            if ($provider) {
                FugoGameList::create([
                    'fugo_provider_id' => $provider->id,
                    'name' => $game['name'],
                    'image' => $game['image'],
                    'type' => $game['type'],
                    'provider' => $game['provider'],
                    'gameId' => $game['gameId'],
                    'roomId' => $game['roomId'],
                    'order' => $game['order'] ?? null,
                ]);
            }
        }
    }
}
