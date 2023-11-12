<?php

namespace App\Http\Controllers\Api\admin\Network;

use App\Http\Controllers\Controller;
use App\Models\AuditTrail;
use App\Models\BloodRequest;
use App\Models\AdminPost;
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

    public function getAllBloodRequest(){
       
        $bloodRequests = BloodRequest::join('user_details', 'blood_request.user_id', '=', 'user_details.user_id')
            ->join('blood_components', 'blood_request.blood_component_id', '=', 'blood_components.blood_component_id')
            ->join('users', 'user_details.user_id', '=', 'users.user_id')
            ->select('blood_request.*', 'user_details.first_name', 'user_details.middle_name' ,'user_details.last_name', 'user_details.blood_type', 'blood_components.blood_component_desc', 'users.email', 'users.mobile')
            ->orderBy('blood_request.schedule')
            ->get();

        return response()->json([
            'status'    => 'success',
            'data'      => $bloodRequests
        ]);
    }

    public function getRequestIdNumber(){
        $requestId = app(BloodRequest::class)->getAllRequestId();


        return response()->json([
            'status' => 'success',
            'data'=> $requestId
        ]);
    }

    public function createPost(Request $request){
        $user = getAuthenticatedUserId();
        $userId = $user->user_id;

        try {
            $request->validate([
                'blood_request_id' => 'required',
                'donation_date' => 'required',
                'venue' => 'required',
                'body' => 'required',
                'blood_needs' => 'required',
            ]);

            $requestIdNumber = $request->input('blood_request_id');
            $donationDate = $request->input('donation_date');
            $venue = $request->input('venue');
            $body = $request->input('body');
            $blood_needs = $request->input('blood_needs');

            AdminPost::create([
                'blood_request_id' => $requestIdNumber,
                'donation_date'=> $donationDate,
                'venue'=> $venue,
                'body'=> $body,
                'blood_needs'=> $blood_needs
            ]); 

            $ip = file_get_contents('https://api.ipify.org');
            $ch = curl_init('http://ipwho.is/'.$ip);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HEADER, false);
            $ipwhois = json_decode(curl_exec($ch), true);
            curl_close($ch);


            AuditTrail::create([
                'user_id'    => $userId,
                'module'     => 'Donor Post',
                'action'     => 'Create Post for' . $request->input('request_id_number'),
                'status'     => 'Success',
                'ip_address' => $ipwhois['ip'],
                'region'     => $ipwhois['region'],
                'city'       => $ipwhois['city'],
                'postal'     => $ipwhois['postal'],
                'latitude'   => $ipwhois['latitude'],
                'longitude'  => $ipwhois['longitude'],
            ]);

            return response()->json([
                'status' => 'success',
            ]);
           
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->validator->errors(),
            ], 400);
        }
    }

    
}
