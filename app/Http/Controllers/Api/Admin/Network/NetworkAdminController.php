<?php

namespace App\Http\Controllers\Api\admin\Network;

use App\Http\Controllers\Controller;
use App\Models\AuditTrail;
use App\Models\BloodRequest;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class NetworkAdminController extends Controller
{
    public function markAsAccomodated(Request $request){
        $user = getAuthenticatedUserId();
        $userId = $user->user_id;

       try {
           $validatedData = $request->validate([
               'blood_request_id' => 'required|array',
           ]);
   
           $ip = file_get_contents('https://api.ipify.org');
           $ch = curl_init('http://ipwho.is/'.$ip);
           curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
           curl_setopt($ch, CURLOPT_HEADER, false);
   
           $ipwhois = json_decode(curl_exec($ch), true);
           curl_close($ch);
   
           foreach ($validatedData['blood_request_id'] as $bloodRequestId) {
               $bloodRequest = BloodRequest::where('blood_request_id', $bloodRequestId)->first();
   
               if (empty($bloodRequest)) {
                   return response()->json([
                       'status'  => 'error',
                       'message' => 'Blood request not found',
                   ], 400);
               } else {
                   $bloodRequest->update(['isAccommodated' => 1]);
   
                   AuditTrail::create([
                       'user_id'    => $userId,
                       'module'     => 'Inventory',
                       'action'     => 'Mark as Accomodated | Blood Request ID: ' . $bloodRequestId,
                       'status'     => 'success',
                       'ip_address' => $ipwhois['ip'],
                       'region'     => $ipwhois['region'],
                       'city'       => $ipwhois['city'],
                       'postal'     => $ipwhois['postal'],
                       'latitude'   => $ipwhois['latitude'],
                       'longitude'  => $ipwhois['longitude'],
                   ]);
               }
           }
   
           return response()->json([
               'status'    => 'success',
               'message'   => 'Blood bags mark as accomodated successfully',
           ]);
        } catch (ValidationException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Validation failed',
                'errors'  => $e->validator->errors(),
            ], 400);
        }
    }
}
