<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\DepositLogResource;
use App\Models\DepositRequest;
use App\Models\User;
use App\Notifications\PlayerDepositNotification;
use App\Services\Notification\AdminSocketNotifier;
use App\Traits\HttpResponses;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class DepositRequestController extends Controller
{
    use HttpResponses;

    public function __construct(private AdminSocketNotifier $adminNotifier)
    {
    }

    public function FinicialDeposit(Request $request)
    {
        $request->validate([
            'agent_payment_type_id' => ['required', 'integer'],
            'amount' => ['required', 'integer', 'min: 1000'],
            'refrence_no' => ['required', 'digits:6'],
        ]);
        Log::info('Financial Deposit Request', $request->all());
        // payment type id 1 is bank, 2 is mobile banking, 3 is cash
        if ($request->agent_payment_type_id == 1) {
            $paymentType = 'AYA Banking';
        } else if ($request->agent_payment_type_id == 2) {
            $paymentType = ' AYA Pay';
        } else if ($request->agent_payment_type_id == 3) {
            $paymentType = 'CB Banking';
        } else if ($request->agent_payment_type_id == 4) {
            $paymentType = 'CB Pay';
        } else if ($request->agent_payment_type_id == 5) {
            $paymentType = 'KBZ Banking';
        } else if ($request->agent_payment_type_id == 6) {
            $paymentType = 'KBZ Pay';
        } else if ($request->agent_payment_type_id == 7) {
            $paymentType = 'MAB Banking';
        }
        else if ($request->agent_payment_type_id == 8) {
            $paymentType = 'UAB Pay';
        } else if ($request->agent_payment_type_id == 9) {
            $paymentType = 'Wave Pay';
        } else if ($request->agent_payment_type_id == 10) {
            $paymentType = 'Yoma Banking';
        }else{
            return $this->error('', 'Invalid Agent Payment Type!', 401);
        }
        Log::info('Deposit Agent Payment Type', ['agent_payment_type_id' => $request->agent_payment_type_id, 'payment_type' => $paymentType]);

        $player = Auth::user();
        $image = null;

        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $filename = uniqid('deposit').'.'.$image->getClientOriginalExtension();
            $image->move(public_path('assets/img/deposit/'), $filename);
        }

        $depositData = [
            'agent_payment_type_id' => $request->agent_payment_type_id,
            'user_id' => $player->id,
            'agent_id' => $player->agent_id,
            'amount' => $request->amount,
            'refrence_no' => $request->refrence_no,
        ];

        if ($image) {
            $depositData['image'] = $filename;
        }

        $deposit = DepositRequest::create($depositData);

        $agent = User::find($player->agent_id);
        if ($agent) {
            Log::info('Triggering PlayerDepositNotification for agent:', [
                'agent_id' => $player->agent_id,
                'deposit_id' => $deposit->id,
            ]);
            $agent->notify(new PlayerDepositNotification($deposit));
        }

        $this->adminNotifier->notifyDeposit($deposit);

        return $this->success($deposit, 'ငွေထည့်သွင်းမှု့ အောင်မြင်ပါသည်။ ငါးမိနစ်ခန့် စောင့်ဆိုင်းပေးပါ ၊ သင့် ဂိမ်းအကောင့်သို့ ငွေလွဲပြောင်းပေးနေပါ သည်။ သင့်ရဲ့စောင့်ဆိုင်းနေမှု့အတွက် ကျေးဇူးတင်ပါသည်။');

    }

    public function log()
    {
        $deposit = DepositRequest::with('bank')->where('user_id', Auth::id())->get();

        return $this->success(DepositLogResource::collection($deposit));
    }
}
