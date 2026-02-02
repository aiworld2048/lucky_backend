<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Models\User;
use App\Enums\UserType;
use App\Models\Contact;
use App\Models\TransferLog;
use Illuminate\Http\Request;
use App\Models\Admin\UserLog;
use App\Traits\HttpResponses;
use App\Enums\TransactionName;
use App\Services\WalletService;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Http\Resources\AgentResource;
use App\Http\Resources\PlayerResource;
use App\Http\Requests\Api\LoginRequest;
use App\Http\Resources\RegisterResource;
use App\Http\Requests\Api\ProfileRequest;
use App\Http\Requests\Api\RegisterRequest;
use App\Http\Requests\Api\ChangePasswordRequest;

class AuthController extends Controller
{
    use HttpResponses;
    private const AGENT_ROLE = 2;
    private const PLAYER_ROLE = 3;
    private const SUB_AGENT_ROLE = 5;


    public function login(LoginRequest $request)
    {
        $data = $request->validated();

        $credentials = is_numeric($data['user_name'])
            ? ['phone' => $data['user_name'], 'password' => $data['password']]
            : ['user_name' => $data['user_name'], 'password' => $data['password']];

        if (! Auth::attempt($credentials)) {
            return $this->error('', 'Credentials do not match!', 401);
        }

        $user = Auth::user();

        if ($user->status == 0) {
            return $this->error('', 'Your account is not activated!', 401);
        }

        if ($user->is_changed_password == 0) {
            return $this->error($user, 'You have to change password', 200);
        }

        // Ensure roles relationship is loaded
        $user->load('roles');

        if ($user->roles->isEmpty() || $user->roles[0]->id != self::PLAYER_ROLE) {
            return $this->error('', 'You do not have permissions', 200);
        }

        UserLog::create([
            'ip_address' => $request->ip(),
            'user_id' => $user->id,
            'user_agent' => $request->userAgent(),
        ]);
        $user->tokens()->delete();

        return $this->success(new UserResource($user), 'User login successfully.');
    }

  

    public function register(RegisterRequest $request)
    {
        $referralUser = User::where('referral_code', $request->referral_code)->first();

        if (! $referralUser) {
            return $this->error('', 'Not Found Agent', 401);
        }

        // Determine the main agent ID
        // If referral code belongs to a sub-agent, get the parent agent (main agent)
        // If referral code belongs to a main agent, use it directly
        $mainAgentId = $referralUser->id;
        $referralUserType = UserType::from((int) $referralUser->type);

        if ($referralUserType === UserType::SubAgent) {
            // If it's a sub-agent, get the parent agent (main agent)
            if (! $referralUser->agent_id) {
                return $this->error('', 'Sub-Agent has no parent agent', 401);
            }
            $mainAgentId = $referralUser->agent_id;
            
            // Verify the parent is actually a main agent
            $mainAgent = User::find($mainAgentId);
            if (! $mainAgent || (int) $mainAgent->type !== UserType::Agent->value) {
                return $this->error('', 'Invalid parent agent', 401);
            }
        } elseif ($referralUserType !== UserType::Agent) {
            // Only allow registration with Agent or SubAgent referral codes
            return $this->error('', 'Invalid referral code type', 401);
        }

        $inputs = $request->validated();

        $user = User::create([
            'phone' => $request->phone,
            'name' => $request->name,
            'user_name' => $this->generateRandomString(),
            'password' => Hash::make($inputs['password']),
            'agent_id' => $mainAgentId, // Always use main agent ID
            'type' => UserType::Player->value,
            'reg_player_ref_code' => $request->referral_code,
        ]);

        $user->roles()->sync(self::PLAYER_ROLE);

        // $this->cashIn($agent,$user);

        return $this->success(new RegisterResource($user), 'User register successfully.');
    }

    public function logout()
    {
        if (Auth::check()) {
            Auth::user()->currentAccessToken()->delete();
        }

        return $this->success([
            'message' => 'Logged out successfully.',
        ]);
    }

    public function getUser()
    {
        return $this->success(new PlayerResource(Auth::user()), 'User Success');
    }

    public function changePassword(ChangePasswordRequest $request)
    {
        $player = Auth::user();

        if (! Hash::check($request->current_password, $player->password)) {
            return $this->error('', 'Old Password is incorrect', 401);
        }

        $player->update([
            'password' => Hash::make($request->password),
            'status' => 1,
        ]);

        return $this->success($player, 'Password has been changed successfully.');
    }

    public function playerChangePassword(Request $request)
    {
        $request->validate([
            'password' => ['required', 'confirmed'],
            'user_id' => ['required'],
        ]);
        $player = User::where('id', $request->user_id)->first();

        if ($player) {
            $player->update([
                'password' => Hash::make($request->password),
                'is_changed_password' => true,
            ]);

            return $this->success($player, 'Password has been changed successfully.');
        } else {
            return $this->error('', 'Not Found Player', 401);
        }
    }

    public function profile(ProfileRequest $request)
    {
        $player = Auth::user();
        $player->update([
            'name' => $request->name,
            'phone' => $request->phone,
        ]);

        return $this->success(new PlayerResource($player), 'Update profile');
    }

    public function getAgent()
    {
        $player = Auth::user();

        return $this->success(new AgentResource($player->parent), 'Agent Information List');
    }

    private function generateRandomString()
    {
        $randomNumber = mt_rand(10000000, 99999999);

        return 'Pi'.$randomNumber;
    }

    private function isExistingUserForAgent($phone, $agent_id)
    {
        return User::where('phone', $phone)->where('agent_id', $agent_id)->first();
    }


    private function cashIn($agent,$user) {

            app(WalletService::class)->transfer($agent, $user,1000,
                TransactionName::CreditTransfer, [
                    'note' => "1000 MMK register promotion",
                    'old_balance' => $user->balanceFloat,
                    'new_balance' => $user->balanceFloat + 1000,
                ]);
            // Log the transfer
            TransferLog::create([
                'from_user_id' => $agent->id,
                'to_user_id' => $user->id,
                'amount' => 1000,
                'type' => 'top_up',
                'description' => 'Credit transfer from '.$agent->user_name.' to player',
                'meta' => [
                    'transaction_type' => TransactionName::Deposit->value,
                    'note' => "1000 MMK register promotion",
                    'old_balance' => $user->balanceFloat,
                    'new_balance' => $user->balanceFloat + 1000,
                ],
            ]);
    }
}
