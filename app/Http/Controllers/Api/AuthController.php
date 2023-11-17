<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserDetail;
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
            
            $isEmail = filter_var($credentials['email_or_phone'], FILTER_VALIDATE_EMAIL);
            $field = $isEmail ? 'email' : 'mobile';
        
            if (Auth::attempt([$field => $credentials['email_or_phone'], 'password' => $credentials['password']])) {
        
                /** @var \App\Models\User $user **/
                $user = Auth::user();
        
                $token = $user->createToken('api-token')->plainTextToken;
        
                if ($user->isAdmin == 1) {
                    return response()->json(['token' => $token, 'user' => $user, 'redirect' => 'Admin Dashboard']);
                }else{
                    // dd($user->user_id );
                    $userId = $user->user_id;   
                    $userDetail = UserDetail::where('user_id', $userId)->first();

                    if(!$userDetail){
                        return response()->json([
                            'res'   => 'error',
                            'user_id' => $userId,
                            'next_step' => 2,
                            'msg'   => 'Plese complete registration process ',
                        ],400);
                    }else{
                        return response()->json(['token' => $token, 'user' => $user, 'redirect' => 'Donor Dashboard']);
                    }
                }
        
            } else {
                return response()->json([
                    'res'   => 'error',
                    'msg'   => 'Invalid account please check username or password',
                ],400);
            }

        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->validator->errors(),
            ], 400);
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

    public function checkIfAdmin(){
        if (Auth::check()) {
            $user = Auth::user();
            $user_id = $user->user_id;
            $user_details = User::where('user_id', $user_id)->first();
            $user_role = $user_details->isAdmin;
            return response()->json([
                'status' => 'success',
                'isAdmin' => $user_role,
            ], 200);
        }
    }

    public function me(){
        $user = getAuthenticatedUserId();
        $userId = $user->user_id;

        $userDetail = UserDetail::join('users', 'user_details.user_id', '=', 'users.user_id')
        ->where('user_details.user_id', $userId)
        ->select('user_details.*', 'users.*')
        ->first();

        return response()->json([
            'status' => 'success',
            'data' => $userDetail
        ], 200);
        
    }
}