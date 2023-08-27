<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
 
    public function login(Request $request){
        try {    
            $credentials = $request->validate([
                'email_or_phone' => 'required',
                'password' => 'required',
            ]);
    
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->validator->errors(),
            ], 402);
        }        
        // dd($credentials);
    
        $isEmail = filter_var($credentials['email_or_phone'], FILTER_VALIDATE_EMAIL);
        $field = $isEmail ? 'email' : 'mobile';
    
        if (Auth::attempt([$field => $credentials['email_or_phone'], 'password' => $credentials['password']])) {
    
            /** @var \App\Models\User $user **/
            $user = Auth::user();
    
            $token = $user->createToken('api-token')->plainTextToken;
    
            if ($user->is_admin == 1) {
                return response()->json(['token' => $token, 'user' => $user, 'redirect' => 'Admin Dashboard']);
            }else{
                return response()->json(['token' => $token, 'user' => $user, 'redirect' => 'Donor Dashboard']);
            }
    
        } else {
            return response()->json([
                'res'   => 'error',
                'msg'   => 'Invalid account please check username or password',
            ]);
        }
    }
    
 

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully']);
        
    }


    public function isLoggedIn()
    {
    
        if (Auth::check()) {

            $user = Auth::user();
            $user_id = $user->user_id;

            return response()->json([
                'res'   => 'success',
                'user_id'   => $user_id,
                'msg'   => 'User is logged in',
            ], 200);

        }        
    }
}