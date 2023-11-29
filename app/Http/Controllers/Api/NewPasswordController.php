<?php

namespace App\Http\Controllers\Api;
use Illuminate\Contracts\Auth\PasswordBroker;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use App\Models\User;

class NewPasswordController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required',
            'password' => ['required', 'confirmed','min:6',],
        ]);

        $user = User::where(function ($query) use ($request) {
            $query->where('email', $request->email)
                ->orWhere('mobile', $request->email);
        })->first();

        if (!$user) {
            return response()->json(['message' => 'User not found.'], 404);
        }
        

        // if ($request->email_or_phone = 'email')

        $status = $this->broker()->reset(
            ['email' => $user->email]+ $request->only('password', 'password_confirmation', 'token'), // Use 'email' instead of 'email_or_phone'
            function ($user, $password) use ($request) {
                $user->forceFill([
                    'password' => Hash::make($password),
                ])->save();

                // app(CompletePasswordReset::class)(Auth::guard(), $user);
            }
        );

        return $status == Password::PASSWORD_RESET
            ? response()->json(['status' => 'success','message' => 'Password reset successfully.'], 200)
            : response()->json(['status' => 'error', 'message' => $status], 400);
    }


    protected function broker(): PasswordBroker
    {
        return Password::broker(config('fortify.passwords'));
    }
}
