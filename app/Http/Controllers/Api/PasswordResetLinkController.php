<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Mail\OtpMail;
use App\Models\Otp;
use Carbon\Carbon;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

class PasswordResetLinkController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'email'     => ['required', 'string', 'email'],
        ],[
            'email.required' => 'The email field is required.',
        ]);
    
        $user = User::where('email', $request->email)->first();
    
        if ($user) {

            return $this->sendOtpEmail($request);

        } else {

            return response()->json(['message' => 'Email not found.'], 404);

        }
    }

    function generateOtp($length = 6): string
    {
        $characters = '0123456789'; 
        $otp = '';
        
        for ($i = 0; $i < $length; $i++) {
            $otp .= $characters[rand(0, strlen($characters) - 1)];
        }
        
        return $otp;
    }


    public function sendOtpEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|string', 
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json(['message' => 'User not found.'], 404);
        }

        $otp = $this->generateOtp();
        $expiresAt = Carbon::now()->addMinutes(5);


        Otp::create([
            'email_or_phone' => $request->email,
            'otp' => $otp,
            'expires_at' => $expiresAt,
        ]);

        Mail::to($request->email)->send(new OtpMail($otp));
        return response()->json([
            'status' => 'success',
            'message' => 'OTP sent to email.',
            'email' => $request->email,
            // 'OTP' => $otp,
            'expires_at' => $expiresAt], 200);
    }


    public function verifyOtp(Request $request)
    {
        $otp = Otp::where('email_or_phone', $request->email)
            ->where('otp', $request->otp)
            ->where('expires_at', '>', now())
            ->first();

        if (!$otp) {
            return response()->json([
                'status' => 'error',
                'message' => 'You entered Invalid OTP.'
            ], 422);
        } elseif ($otp->expires_at < now()) {
            $otp->delete();
            return response()->json([
                'status' => 'error',
                'message' => 'OTP has expired.'
            ], 422);
        }

        //the process of resetting password will be executed if the otp is valid
        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'User not found.',
                'email_or_phone' => $request->email], 404);
        }

        // generate a password reset token and send 
        $token = Password::getRepository()->create($user);
        // $user->notify(new ResetPassword($token));
        $otp->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'OTP verification successful.',
            'token' => $token,
            'email_or_phone' => $request->email_or_phone
        ],200);
    }


    public function resendOtpEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|string', 
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json(['message' => 'User not found.'], 404);
        }

        $otp = $this->generateOtp();
        $expiresAt = Carbon::now()->addMinutes(5);


        $newotp = Otp::create([
            'email_or_phone' => $request->email,
            'otp' => $otp,
            'expires_at' => $expiresAt,
            'next_resend_otp' => $request->nextResendDate
        ]);

        Mail::to($request->email)->send(new OtpMail($otp));
        return response()->json([
            'status' => 'success',
            'message' => 'OTP sent to email.',
            'email_or_phone' => $request->email,
            // 'OTP' => $otp,
            'expires_at' => $expiresAt,
            'next_resend_otp' => $newotp->next_resend_otp], 200);
    }

    public function getNextResendOtp(Request $request){

        try{
            $request->validate([
                'email' => 'required|string', 
            ]);
        
            $user = OTP::where('email_or_phone', $request->email)->latest()->first();
            $nextResendDate = $user->next_resend_otp;
            
            if(!$user){
                return response()->json(['message' => 'User not found.'], 404);
            }else{

                if($nextResendDate === null){
        
                    return response()->json([
                        'status' => 'success',
                        'email' => $user->email,
                        'nextResendOtp' => '' 
                    ]);
                }else{
            
                    return response()->json([
                        'status' => 'success',
                        'email' => $user->email,
                        'nextResendOtp' => $nextResendDate 
                    ]);
                }
            }
            

        } catch (ValidationException $e) {
            return response()->json([
                'status'    => 'error',
                'message'   => 'Validation failed',
                'errors'    => $e->validator->errors(),
            ], 400);
        }
    }
}
