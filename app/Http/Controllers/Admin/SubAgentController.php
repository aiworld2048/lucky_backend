<?php

namespace App\Http\Controllers\Admin;

use App\Enums\TransactionName;
use App\Enums\UserType;
use App\Http\Controllers\Controller;
use App\Http\Requests\SubAgentRequest;
use App\Models\Admin\Permission;
use App\Models\Admin\Role;
use App\Models\TransferLog;
use App\Models\User;
use App\Services\WalletService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class SubAgentController extends Controller
{
    private const SUB_AGENT_ROLE = 5; // SubAgent role ID from RolesTableSeeder

    public function __construct()
    {
        $this->middleware('permission:player_create')->only(['create', 'store']);
        $this->middleware('permission:player_view')->only(['index', 'show']);
        $this->middleware('permission:player_update')->only(['edit', 'update', 'banSubAgent']);
        $this->middleware('permission:player_password_change')->only(['getChangePassword', 'makeChangePassword']);
        $this->middleware('permission:player_wallet_deposit|player_wallet_withdraw')->only(['getCashIn', 'makeCashIn', 'getCashOut', 'makeCashOut']);
    }

    /**
     * Display a listing of sub-agents for the authenticated agent.
     */
    public function index(): View
    {
        try {
            $agent = Auth::user();
            $this->ensureAgent($agent);

            $subAgents = User::where('type', UserType::SubAgent->value)
                ->where('agent_id', $agent->id)
                ->with('wallet')
                ->select('id', 'name', 'user_name', 'phone', 'status', 'referral_code', 'commission', 'created_at')
                ->orderByDesc('created_at')
                ->paginate(10);

            return view('admin.subagent.index', compact('subAgents'));
        } catch (\Exception $e) {
            Log::error('Error in SubAgentController@index: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'user_id' => Auth::id(),
            ]);
            
            abort(500, 'An error occurred while loading sub-agents. Please try again later.');
        }
    }

    /**
     * Show the form for creating a new sub-agent.
     */
    public function create(): View
    {
        $subagent_name = $this->generateRandomString();
        $referral_code = $this->generateReferralCode();

        return view('admin.subagent.create', compact('subagent_name', 'referral_code'));
    }

    /**
     * Store a newly created sub-agent in storage.
     */
    public function store(SubAgentRequest $request): RedirectResponse
    {
        $agent = Auth::user();
        $this->ensureAgent($agent);
        
        $inputs = $request->validated();
        $siteLink = "https://blog.silentforestnature.xyz";

        // Check if agent has sufficient balance for transfer
        if (isset($inputs['amount']) && $inputs['amount'] > $agent->balanceFloat) {
            return redirect()->back()->with('error', 'Balance Insufficient');
        }

        $transfer_amount = $inputs['amount'] ?? 0;

        // Create the sub-agent
        $userPrepare = array_merge(
            $inputs,
            [
                'password' => Hash::make($inputs['password']),
                'agent_id' => $agent->id,
                'type' => UserType::SubAgent->value,
                'referral_code' => $inputs['referral_code'],
                'commission' => $inputs['commission'],
            ]
        );

        $subAgent = User::create($userPrepare);

        // Assign SubAgent role
        $subAgent->roles()->sync(self::SUB_AGENT_ROLE);

        // Assign SubAgent permissions (limited permissions)
        $permissions = Permission::whereIn('title', [
            'player_view',
            'player_create',
            'player_update',
            'player_delete',
            'player_ban',
            'player_password_change',
        ])->get();
        $subAgent->permissions()->sync($permissions->pluck('id'));

        // Handle transfer if amount is provided
        if ($transfer_amount > 0) {
            try {
                DB::beginTransaction();

                // Perform the transfer
                app(AdminWalletService::class)->transfer(
                    $agent,
                    $subAgent,
                    $transfer_amount,
                    TransactionName::CreditTransfer,
                    [
                        'old_balance' => $subAgent->balance,
                        'new_balance' => $subAgent->balance + $transfer_amount,
                    ]
                );

                // Log the transfer
                TransferLog::create([
                    'from_user_id' => $agent->id,
                    'to_user_id' => $subAgent->id,
                    'amount' => $transfer_amount,
                    'type' => 'top_up',
                    'description' => 'Initial Top Up from Agent to new sub-agent',
                    'meta' => [
                        'transaction_type' => TransactionName::CreditTransfer->value,
                        'old_balance' => $subAgent->balance,
                        'new_balance' => $subAgent->balance + $transfer_amount,
                    ],
                ]);

                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Error during sub-agent creation and transfer: '.$e->getMessage());

                return redirect()->back()->with('error', 'Error occurred during transfer. Please try again.');
            }
        }

        return redirect()->route('admin.subagent.index')
            ->with('successMessage', 'Sub-Agent created successfully')
            ->with('password', $request->password)
            ->with('username', $subAgent->user_name)
            ->with('amount', $transfer_amount)
            ->with('site_link', $siteLink)
            ->with('referral_code', $subAgent->referral_code);
    }

    /**
     * Show the form for editing the specified sub-agent.
     */
    public function edit(string $id): View
    {
        $agent = Auth::user();
        $this->ensureAgent($agent);

        $subAgent = User::where('type', UserType::SubAgent->value)
            ->where('agent_id', $agent->id)
            ->findOrFail($id);

        return view('admin.subagent.edit', compact('subAgent'));
    }

    /**
     * Update the specified sub-agent in storage.
     */
    public function update(Request $request, string $id): RedirectResponse
    {
        $agent = Auth::user();
        $this->ensureAgent($agent);

        $subAgent = User::where('type', UserType::SubAgent->value)
            ->where('agent_id', $agent->id)
            ->findOrFail($id);

        // Validate all the fields according to users table structure
        $validatedData = $request->validate([
            // Basic Information
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:255',
            'email' => 'nullable|email|unique:users,email,' . $id,
            'profile' => 'nullable|string|max:2000',
            'agent_logo' => 'nullable|string|max:255',
            
            // Financial Settings
            'max_score' => 'nullable|numeric|min:0',
            
            'site_name' => 'nullable|string|max:255',
            'site_link' => 'nullable|url|max:255',
            
            // Status Settings
            'status' => 'required|integer|in:0,1',
            'is_changed_password' => 'required|integer|in:0,1',
        ]);

        // Update sub-agent with validated data
        $subAgent->update($validatedData);

        return redirect()->route('admin.subagent.index')
            ->with('success', 'Sub-Agent updated successfully');
    }

    /**
     * Show cash-in form for sub-agent.
     */
    public function getCashIn(string $id): View
    {
        $agent = Auth::user();
        $this->ensureAgent($agent);

        $subAgent = User::where('type', UserType::SubAgent->value)
            ->where('agent_id', $agent->id)
            ->findOrFail($id);

        return view('admin.subagent.cash_in', compact('subAgent'));
    }

    /**
     * Show cash-out form for sub-agent.
     */
    public function getCashOut(string $id): View
    {
        $agent = Auth::user();
        $this->ensureAgent($agent);

        $subAgent = User::where('type', UserType::SubAgent->value)
            ->where('agent_id', $agent->id)
            ->findOrFail($id);

        return view('admin.subagent.cash_out', compact('subAgent'));
    }

    /**
     * Process cash-in (transfer from agent to sub-agent).
     */
    public function makeCashIn(Request $request, $id): RedirectResponse
    {
        try {
            $agent = Auth::user();
            $this->ensureAgent($agent);

            $subAgent = User::where('type', UserType::SubAgent->value)
                ->where('agent_id', $agent->id)
                ->findOrFail($id);

            $request->validate([
                'amount' => ['required', 'numeric', 'min:1'],
                'note' => ['nullable', 'string', 'max:255'],
            ]);

            $amount = (int) $request->amount;

            if ($amount > (int) $agent->balance) {
                throw new \Exception('You do not have enough balance to transfer!');
            }

            app(AdminWalletService::class)->transfer(
                $agent,
                $subAgent,
                $amount,
                TransactionName::CreditTransfer,
                [
                    'note' => $request->note,
                    'description' => $request->note ?? 'Agent to sub-agent top up',
                ]
            );

            return redirect()->route('admin.subagent.index')->with('success', 'Money fill request submitted successfully!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Process cash-out (transfer from sub-agent to agent).
     */
    public function makeCashOut(Request $request, string $id): RedirectResponse
    {
        try {
            $agent = Auth::user();
            $this->ensureAgent($agent);

            $subAgent = User::where('type', UserType::SubAgent->value)
                ->where('agent_id', $agent->id)
                ->findOrFail($id);

            $request->validate([
                'amount' => ['required', 'numeric', 'min:1'],
                'note' => ['nullable', 'string', 'max:255'],
            ]);

            $amount = (int) $request->amount;

            if ($amount > (int) $subAgent->balance) {
                return redirect()->back()->with('error', 'You do not have enough balance to transfer!');
            }

            app(AdminWalletService::class)->transfer(
                $subAgent,
                $agent,
                $amount,
                TransactionName::DebitTransfer,
                [
                    'note' => $request->note,
                    'description' => $request->note ?? 'Sub-agent cash out to agent',
                ]
            );

            return redirect()->back()->with('success', 'Money fill request submitted successfully!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Ban/Unban sub-agent.
     */
    public function banSubAgent($id): RedirectResponse
    {
        $agent = Auth::user();
        $this->ensureAgent($agent);

        $subAgent = User::where('type', UserType::SubAgent->value)
            ->where('agent_id', $agent->id)
            ->findOrFail($id);
        
        $subAgent->update(['status' => $subAgent->status == 1 ? 0 : 1]);

        return redirect()->back()->with(
            'success',
            'Sub-Agent '.($subAgent->status == 1 ? 'activated' : 'inactivated').' successfully'
        );
    }

    /**
     * Show change password form for sub-agent.
     */
    public function getChangePassword($id): View
    {
        $agent = Auth::user();
        $this->ensureAgent($agent);

        $subAgent = User::where('type', UserType::SubAgent->value)
            ->where('agent_id', $agent->id)
            ->findOrFail($id);

        return view('admin.subagent.change_password', compact('subAgent'));
    }

    /**
     * Update sub-agent password.
     */
    public function makeChangePassword($id, Request $request): RedirectResponse
    {
        $request->validate([
            'password' => 'required|min:6|confirmed',
        ]);

        $agent = Auth::user();
        $this->ensureAgent($agent);

        $subAgent = User::where('type', UserType::SubAgent->value)
            ->where('agent_id', $agent->id)
            ->findOrFail($id);
        
        $subAgent->update([
            'password' => Hash::make($request->password),
        ]);

        return redirect()->route('admin.subagent.index')
            ->with('successMessage', 'Sub-Agent password changed successfully')
            ->with('password', $request->password)
            ->with('username', $subAgent->user_name);
    }

    /**
     * Display sub-agent profile.
     */
    public function show($id): View
    {
        $agent = Auth::user();
        $this->ensureAgent($agent);

        $subAgent = User::where('type', UserType::SubAgent->value)
            ->where('agent_id', $agent->id)
            ->findOrFail($id);

        return view('admin.subagent.show', compact('subAgent'));
    }

    /**
     * Generate random sub-agent username.
     */
    private function generateRandomString(): string
    {
        $randomNumber = mt_rand(10000000, 99999999);
        return 'SA'.$randomNumber;
    }

    /**
     * Generate referral code.
     */
    private function generateReferralCode($length = 8): string
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';

        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }

        return $randomString;
    }

    /**
     * Ensure the authenticated user is an Agent.
     */
    private function ensureAgent(User $user): void
    {
        if ((int) $user->type !== UserType::Agent->value) {
            abort(
                Response::HTTP_FORBIDDEN,
                'Unauthorized action. || ဤလုပ်ဆောင်ချက်အား သင့်မှာ လုပ်ဆောင်ပိုင်ခွင့်မရှိပါ, ကျေးဇူးပြု၍ သက်ဆိုင်ရာ Agent များထံ ဆက်သွယ်ပါ'
            );
        }
    }
}

