<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
// use Illuminate\Foundation\Auth\User;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use App\Models\UserDetail;
use Illuminate\Support\Facades\Hash;
use App\Mail\RegistrationMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Auth;

class CustomEmailVerificationController extends Controller
{
    // Override the verify method to return JSON response
    public function verify(EmailVerificationRequest $request)
    {

        $id = $request->route('id');
        $hash = $request->route('hash');

        // Retrieve the user associated with the given 'id'
        $user = User::where('user_id', $id)->first();

        if (!$user || !hash_equals(sha1($user->email), (string) $hash)) {
            return response()->json(['message' => 'Invalid verification link'], 403);
        }

        if ($user->hasVerifiedEmail()) {
            return response()->json(['message' => 'Email already verified']);
        }

        $user->markEmailAsVerified();
        event(new Verified($user));
        Mail::to($user->email)->send(new RegistrationMail($user));

        $frontendRedirectUrl = 'https://life-link.vercel.app/login';
        return Redirect::to($frontendRedirectUrl);

    }


    public function checkIfVerify(Request $request)
    {
        $email = $request->input('email');

        $user_info = User::where('email', $email)->first();


        if(empty($user_info)){

            return response()->json([

                'status'        => 'error',
                'message'       => 'Email not found.'

            ], 400);


            }else{

                $user_details_info = UserDetail::where('user_id', $user_info->user_id)->first();

                   if(!empty($user_details_info)){

                    if ($user_info->email_verified_at == null){

                        $user_info->sendEmailVerificationNotification();

                        return response()->json([

                            'status'        => 'error',
                            'next_step'     => 3,
                            'user_id'       => $user_details_info->user_id,
                            'message'       => 'Please proceed to step 3.'
                        ], 200);


                    }else{

                        return response()->json([
                            'status'        => 'error',
                            'user_id'       => $user_details_info->user_id,
                            'message'       => 'Email Already used.'
                        ], 400);

                    }


                }else{

                    return response()->json([
                        'status'        => 'success',
                        'next_step'     => 2,
                        'user_id'       => $user_info->user_id,
                        'message'       => 'You are in step 2'
                    ], 200);

                }

            }
    }



    public function resend(Request $request)
    {
        $userId = $request->input('user_id');
        $user = User::where('user_id', $userId)->first();
        $email = $user->email;
        if (!$email) {
            return response()->json(['error' => 'Email not found'], 404);
        }

        if ($user->email_verified_at !== null) {
            return response()->json(['message' => 'Email already verified'], 200);
        }

        $user->sendEmailVerificationNotification();

        return response()->json(['message' => 'Verification email sent'], 200);
    }


    public function chechVerifyReg(Request $request){
        $userId = $request->input('user_id');
        $user = User::where('user_id', $userId)->first();

        if($user->email_verified_at){
            return response()->json([
                'status' => 'success',
                'message' => 'Verified'
            ]);
        }else{
            return response()->json([
                'status' => 'error',
                'message' => 'Not yet verified'
            ]);
        }
    }

    public function checkUserDetail(Request $request){
        $userId = $request->input('user_id');
        $user = User::where('user_id', $userId)->first();

        $userDetail = UserDetail::where('user_id', $userId)->first();


        if($userDetail){
            return response()->json([
                'status' => 'success',
                'message' => 'Verified'
            ]);
        }else{
            return response()->json([
                'status' => 'error',
                'message' => 'Not yet verified'
            ]);
        }
    }


}
