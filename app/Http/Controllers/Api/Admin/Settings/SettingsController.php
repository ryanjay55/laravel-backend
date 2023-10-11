<?php

namespace App\Http\Controllers\Api\Admin\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

use App\Models\Setting;

class SettingsController extends Controller
{
    public function createSecurityPin(Request $request)
    {

        try {
            $request->validate([
                'security_pin' => ['required','min:8'],
            ],[
                'security_pin.min' => 'Security pin must be at least 8 characters or digits.',
            ]);
            
            $securityPin = $request->input('security_pin');
            $hashedPin = password_hash($securityPin, PASSWORD_DEFAULT);
        
            $pin = Setting::where('setting_desc', 'security_pin')->first();
            $pin->setting_value = $hashedPin;
            $pin->save();
            
            // dd($hashedPin);
            
            return response()->json([
                'status' => 'success',
                'message' => 'Security pin saved successfully',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->validator->errors(),
            ], 400);
        }

    }

    public function checkSecurityPin(Request $request)
    {
        try {
            $request->validate([
                'security_pin' => ['required'],
            ]);
            
            $securityPin = $request->input('security_pin');
            $setting = Setting::where('setting_desc', 'security_pin')->first();
            $savedSecurityPin = $setting->setting_value;
    
            if (password_verify($securityPin, $savedSecurityPin)) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Security pin is correct',
                ]);
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Security pin is incorrect',
                ]);
            }
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->validator->errors(),
            ], 400);
        }
    }
}
