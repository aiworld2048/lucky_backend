<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Enums\TransactionName;
use App\Enums\UserType;
use App\Models\User;
use App\Services\WalletService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class OwnerSystemWalletAgentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $walletService = new WalletService;

        // Create owner with large initial capital
        $owner = $this->createUser(
            UserType::Owner,
            'Owner',
            'AZM999',
            '09123456789',
            null,
            'OWNER' . Str::random(6)
        );
        $walletService->deposit($owner, 500_000_000, TransactionName::CapitalDeposit);

        // Create system wallet
        $systemWallet = $this->createUser(
            UserType::SystemWallet,
            'System Wallet',
            'SYS001',
            '09222222222',
            null,
            'SYS' . Str::random(6)
        );
        $walletService->deposit($systemWallet, 500_000_000, TransactionName::CapitalDeposit);

        $agent999 = $this->createUser(
            UserType::Agent,
            'Agent 999',
            'AZMAG999',
            '0911234561',
            $owner->id,
            '999'
        );
        $walletService->transfer($owner, $agent999, 2_000_000, TransactionName::CreditTransfer);
    }

    private function createUser(
        UserType $type,
        string $name,
        string $user_name,
        string $phone,
        ?int $parent_id = null,
        ?string $referral_code = null
    ): User {
        return User::create([
            'name' => $name,
            'user_name' => $user_name,
            'phone' => $phone,
            'password' => Hash::make('azm@999@2025$$$'),
            'agent_id' => $parent_id,
            'status' => 1,
            'is_changed_password' => 1,
            'type' => $type->value,
            'referral_code' => $referral_code,

        ]);
    }
}
