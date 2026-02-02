<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CheckPermission
{
    public function handle(Request $request, Closure $next, ...$permissions)
    {
        $user = Auth::user();
        
        if (!$user) {
            abort(401, 'Unauthenticated');
        }

        // Handle pipe-separated permissions (e.g., "banner_view|banner_create")
        $permissionList = [];
        foreach ($permissions as $permission) {
            if (strpos($permission, '|') !== false) {
                $permissionList = array_merge($permissionList, explode('|', $permission));
            } else {
                $permissionList[] = $permission;
            }
        }
        
        $permissionList = array_unique($permissionList);

        Log::info('Permission check', [
            'user_id' => $user->id,
            'roles' => $user->roles->pluck('title')->toArray(),
            'permissions' => $user->permissions->pluck('title')->toArray(),
            'checking_for' => $permissionList,
        ]);

        // Owner has all permissions
        if ($user->hasRole('Owner')) {
            return $next($request);
        }

        // Agent has all permissions
        if ($user->hasRole('Agent')) {
            return $next($request);
        }

        // Check if user has any of the required permissions
        $userPermissions = $user->permissions->pluck('title')->toArray();
        foreach ($permissionList as $permission) {
            if (in_array($permission, $userPermissions) || $user->hasPermission($permission)) {
                return $next($request);
            }
        }

        abort(403, 'Unauthorized action. || ဤလုပ်ဆောင်ချက်အား သင့်မှာ လုပ်ဆောင်ပိုင်ခွင့်မရှိပါ, ကျေးဇူးပြု၍ သက်ဆိုင်ရာ Agent များထံ ဆက်သွယ်ပါ');
    }
}
