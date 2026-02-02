<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Models\Admin\UserLog;
use App\Models\User;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class LoginController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        //dd($request->all());
        $request->validate([
            'user_name' => 'required|string',
            'password' => 'required|string',
        ]);

        // Find user by user_name instead of email
        $user = User::where('user_name', $request->user_name)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return back()->with('error', 'The credentials does not match our records.')->withInput($request->only('user_name'));
        }

        if ($user->status == 0) {
            return redirect()->back()->with('error', 'Your account is not activated!')->withInput($request->only('user_name'));
        }

        if ($user->is_changed_password == 0) {
            Auth::login($user);
            return redirect()->route('getChangePassword', $user->id);
        }

        // Log the user in
        Auth::login($user, $request->has('remember'));

        UserLog::create([
            'ip_address' => $request->ip(),
            'user_id' => $user->id,
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()->route('home');
    }

    public function logout(Request $request)
    {
        Auth::logout();

        return redirect('/login');
    }

    public function changePassword(User $user)
    {
        return view('admin.change_password', compact('user'));
    }

    public function updatePassword(Request $request, User $user)
    {
        try {
            $request->validate([
                'password' => 'required|min:6|confirmed',
            ]);

            $user->update([
                'password' => Hash::make($request->password),
                'is_changed_password' => true,
            ]);

            return redirect()->route('showLogin')->with('success', 'Password has been Updated.');
        } catch (Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}
