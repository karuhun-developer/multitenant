<?php

namespace App\Http\Controllers\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Crypt;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CryptLoginController extends Controller
{
    public function redirectLogin(Request $request)
    {
        try {
            $user = Crypt::decryptString($request->get('user'));

            // Login
            $user = User::find($user);

            // if user not found
            if (!$user) throw new \Exception('User not found');

            // Login
            auth()->login($user);

            // Redirect to dashboard
            return redirect()->route('cms.dashboard');
        } catch (\Exception $e) {
            Log::error($e->getMessage());

            return redirect()->route('login')->with('error', 'User not found');
        }
    }
}
