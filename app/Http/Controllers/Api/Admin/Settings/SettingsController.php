<?php

namespace App\Http\Controllers\Api\Admin\Settings;

use App\Http\Controllers\Controller;
use App\Models\AuditTrail;
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
        $user = getAuthenticatedUserId();
        $userId = $user->user_id;

        try {
            $request->validate([
                'security_pin' => ['required'],
            ]);

            $ip = file_get_contents('https://api.ipify.org');
            $ch = curl_init('http://ipwho.is/'.$ip);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HEADER, false);
        
            $ipwhois = json_decode(curl_exec($ch), true);
        
            curl_close($ch);
            
            $securityPin = $request->input('security_pin');
            $setting = Setting::where('setting_desc', 'security_pin')->first();
            $savedSecurityPin = $setting->setting_value;
    
            if (password_verify($securityPin, $savedSecurityPin)) {
                AuditTrail::create([
                    'user_id'    => $userId,
                    'module'     => 'Deferral List',
                    'action'     => 'Accessed Deferral List',
                    'status'     => 'success',
                    'ip_address' => $ipwhois['ip'],
                    'region'     => $ipwhois['region'],
                    'city'       => $ipwhois['city'],
                    'postal'     => $ipwhois['postal'],
                    'latitude'   => $ipwhois['latitude'],
                    'longitude'  => $ipwhois['longitude'],
                ]);
                return response()->json([
                    'status' => 'success',
                    'message' => 'Security pin is correct',
                ]);
            } else {
                AuditTrail::create([
                    'user_id'    => $userId,
                    'module'     => 'Deferral List',
                    'action'     => 'Accessed Deferral List',
                    'status'     => 'failed',
                    'ip_address' => $ipwhois['ip'],
                    'region'     => $ipwhois['region'],
                    'city'       => $ipwhois['city'],
                    'postal'     => $ipwhois['postal'],
                    'latitude'   => $ipwhois['latitude'],
                    'longitude'  => $ipwhois['longitude'],
                ]);
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
