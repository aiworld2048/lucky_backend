<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\WithdrawResource;
use App\Models\User;
use App\Models\WithDrawRequest;
use App\Notifications\PlayerWithdrawNotification;
use App\Services\Notification\AdminSocketNotifier;
use App\Traits\HttpResponses;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class WithDrawRequestController extends Controller
{
    use HttpResponses;

    public function __construct(private AdminSocketNotifier $adminNotifier)
    {
    }

    public function FinicalWithdraw(Request $request)
    {
        $request->validate([
            'account_name' => ['required', 'string'],
            'amount' => ['required', 'integer', 'min: 10000'],
            'account_number' => ['required', 'regex:/^[0-9]+$/'],
            'payment_type_id' => ['required', 'integer'],
            'password' => ['nullable'],
        ]);

        //Log::info('Financial Withdraw Request', $request->all());
        // payment type id 1 is bank, 2 is mobile banking, 3 is cash
        if ($request->payment_type_id == 1) {
            $paymentType = 'AYA Banking';
        } else if ($request->payment_type_id == 2) {
            $paymentType = ' AYA Pay';
        } else if ($request->payment_type_id == 3) {
            $paymentType = 'CB Banking';
        } else if ($request->payment_type_id == 4) {
            $paymentType = 'CB Pay';
        } else if ($request->payment_type_id == 5) {
            $paymentType = 'KBZ Banking';
        } else if ($request->payment_type_id == 6) {
            $paymentType = 'KBZ Pay';
        } else if ($request->payment_type_id == 7) {
            $paymentType = 'MAB Banking';
        }
        else if ($request->payment_type_id == 8) {
            $paymentType = 'UAB Pay';
        } else if ($request->payment_type_id == 9) {
            $paymentType = 'Wave Pay';
        } else if ($request->payment_type_id == 10) {
            $paymentType = 'Yoma Banking';
        }else{
            return $this->error('', 'Invalid Payment Type!', 401);
        }
        //Log::info('Payment Type', ['payment_type' => $paymentType]);

        $player = Auth::user();
        if ($request->amount > $player->balance) {
            return $this->error('', 'Insufficient Balance!', 401);
        }

        // if (! Hash::check($request->password, $player->password)) {
        //     return $this->error('', 'Your password is wrong!', 401);
        // }

        $withdraw = WithDrawRequest::create([
            'user_id' => $player->id,
            'agent_id' => $player->agent_id,
            'amount' => $request->amount,
            'account_name' => $request->account_name,
            'account_number' => $request->account_number,
            'payment_type_id' => $request->payment_type_id,
        ]);

        $agent = User::find($player->agent_id);
        if ($agent) {
            $agent->notify(new PlayerWithdrawNotification($withdraw));
        }

        $this->adminNotifier->notifyWithdraw($withdraw);

        return $this->success($withdraw, 'ငွေထုတ်ယူမှု့ အောင်မြင်ပါသည်။ ငါးမိနစ်ခန့် စောင့်ဆိုင်းပေးပါ ၊ သင့် ဘဏ်အကောင့်သို့ ငွေလွဲပြောင်းပေးနေပါ သည်။ သင့်ရဲ့စောင့်ဆိုင်းနေမှု့အတွက် ကျေးဇူးတင်ပါသည်။');
    }

    public function log()
    {
        $withdraw = WithDrawRequest::where('user_id', Auth::id())->get();

        return $this->success(WithdrawResource::collection($withdraw));
    }

    public function withdrawTest(Request $request)
    {
        $request->validate([
            'account_name' => ['required', 'string'],
            'amount' => ['required', 'integer', 'min: 10000'],
            'account_number' => ['required', 'regex:/^[0-9]+$/'],
            'payment_type_id' => ['required', 'integer'],
        ]);

        $player = Auth::user();
        if ($request->amount > $player->balance) {
            return $this->error('', 'Insufficient Balance', 401);
        }
        if ($player && ! Hash::check($request->password, $player->password)) {
            return $this->error('', 'လျို့ဝှက်နံပါတ်ကိုက်ညီမှု မရှိပါ။', 401);
        }

        $withdraw = WithDrawRequest::create([
            'user_id' => $player->id,
            'agent_id' => $player->agent_id,
            'amount' => $request->amount,
            'account_name' => $request->account_name,
            'account_number' => $request->account_number,
            'payment_type_id' => $request->payment_type_id,
        ]);

        $agent = User::find($player->agent_id);
        if ($agent) {
            $agent->notify(new PlayerWithdrawNotification($withdraw));
        }

        $this->adminNotifier->notifyWithdraw($withdraw);

        return $this->success($withdraw, 'Withdraw Request Success');
    }
}
