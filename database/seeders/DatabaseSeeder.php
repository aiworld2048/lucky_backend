<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            PermissionsTableSeeder::class,
            SubAgentPermissionSeeder::class,
            RolesTableSeeder::class,
            PermissionRoleTableSeeder::class,
            OwnerSystemWalletAgentSeeder::class,
            UsersTableSeeder::class,
            RoleUserTableSeeder::class,
            BannerSeeder::class,
            BannerTextSeeder::class,
            BannerAdsSeeder::class,
            PaymentTypeTableSeeder::class,
            BankTableSeeder::class,
            
            
            ContactTypeSeeder::class,
            ContactSeeder::class,
            ProductionContactSeeder::class,
            PromotionSeeder::class,
            AdsVedioSeeder::class,
            GameTypeTableSeeder::class,
            GscPlusProductTableSeeder::class,
            GameTypeProductTableSeeder::class,
            FugoProviderSeeder::class,
            FugoGameListSeeder::class,
            HotGameListSeeder::class,
            PragmaticPlaySlotGameListSeeder::class,
            PGSoftSlotGameSeeder::class,
            advantplaySeeder::class,
            AILiveCasinoGameSeeder::class,
            ArficaBuffaloGameSeeder::class,
            AviatrixOtherSeeder::class,
            BIGPOTSlotSeeder::class,
            CQ9SlotGameSeeder::class,
            DreamGamingSeeder::class,
            EpicwinGameSeeder::class,
            FACHAISLOTGameSeeder::class,
            HACKSAWSlotGameSeeder::class,
            ImoonOtherGameSeeder::class,
            JDBSFishingameSeeder::class,
            CQ9FishingGameSeeder::class,
            FACHAIFishingGameSeeder::class,
            JDBOtherGameSeeder::class,
            JDBSLOTGameSeeder::class,
            JILISlotGameSeeder::class,
            Live22SlotGameSeeder::class,
            MrSlottyBiggamingSlotSeeder::class,
            N2SlotGameSeeder::class,
            PlayAceLIVE_CASINOSeeder::class,
            PlayaceSlotSeeder::class,
            WOWGamingSLOTSeeder::class,
            PlayStarGameSeeder::class,
            PragmaticPlayLiveCasinoPremiumGameSeeder::class,
            PragmaticPlayLiveCasinoGameSeeder::class,
            WMCasinoGameSeeder::class,
            PragmaticPlayVirtualSportGameSeeder::class,
            SAGamingCasinoGameSeeder::class,
            SBOSportBookSeeder::class,
            SmartSoftGameSeeder::class,
            SpadeGamingSlotGameSeeder::class,
            WOWGamingPokerSeeder::class,
            YEEBETGameListSeeder::class,
            
            

             

        ]);
    }
}
