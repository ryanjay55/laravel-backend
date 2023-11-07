<?php

namespace App\Http\Controllers\Api\Donor\Network;

use App\Http\Controllers\Controller;
use App\Models\BloodComponent;
use App\Models\BloodRequest;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use DateTime;

class NetworkController extends Controller
{
    
    public function createBloodRequest(Request $request){
        $user = getAuthenticatedUserId();
        $userId = $user->user_id;
        try {
    
            $validatedData = $request->validate([
                'blood_units'    => ['required'],
                'blood_component_id'  => ['required'],
                'hospital'    => ['required'],
                'diagnosis'    => ['required'],
                'schedule'    => ['required']
            ],[
                'blood_component_id.required'  => 'The blood component field is required.',
            ]);
    
            $bloodUnit = $validatedData['blood_units'];
            $bloodComponentId = $validatedData['blood_component_id'];
            $hospital = $validatedData['hospital'];
            $diagnosis = $validatedData['diagnosis'];
            $schedule = $validatedData['schedule'];     


            // Parse the ISO 8601 date string to a DateTime object
            $date = new DateTime($schedule);

            // Format the date as a human-readable string
            $humanReadableDate = $date->format('Y-m-d H:i:s');

            $existedUser = BloodRequest::where('user_id', $userId)->first();

            if($existedUser){
                if ($existedUser->isAccomodated == 1) {

                    do {
                        $uniqueRequestId = mt_rand(100000, 999999); // Generate a random 6-digit number
                    } while (BloodRequest::where('request_id_number', $uniqueRequestId)->exists());

                    BloodRequest::create([
                        'user_id' => $userId,
                        'request_id_number' => $uniqueRequestId,
                        'blood_units' => $bloodUnit,
                        'blood_component_id' => $bloodComponentId,
                        'hospital' => $hospital,
                        'diagnosis' => $diagnosis,
                        'schedule' => $humanReadableDate                
                    ]);
    
                    return response()->json([
                        'status'    => 'success',
                        'message'   => 'Blood request created successfully',
                    ]);
    
                }else{
                   return response()->json([
                       'status'    => 'error',
                       'message'   => 'You cannot make a new blood request while there is a pending request.',
                   ], 400);
                }
            }else{

                do {
                    $uniqueRequestId = mt_rand(100000, 999999); // Generate a random 6-digit number
                } while (BloodRequest::where('request_id_number', $uniqueRequestId)->exists());

                BloodRequest::create([
                    'user_id' => $userId,
                    'request_id_number' => $uniqueRequestId,
                    'blood_units' => $bloodUnit,
                    'blood_component_id' => $bloodComponentId,
                    'hospital' => $hospital,
                    'diagnosis' => $diagnosis,
                    'schedule' => $humanReadableDate                
                ]);

                return response()->json([
                    'status'    => 'success',
                    'message'   => 'Blood request created successfully',
                ]);
            }
            
    
    
        } catch (ValidationException $e) {
            return response()->json([
                'status'    => 'error',
                'message'   => 'Validation failed',
                'errors'    => $e->validator->errors(),
            ], 400);
        }
    }

    public function getBloodRequest(){
        $user = getAuthenticatedUserId();
        $userId = $user->user_id;

        $bloodRequest = BloodRequest::where('user_id', $userId)
            ->where('status', 0)
            ->get();

        return response()->json([
            'status'    => 'success',
            'data'      => $bloodRequest
        ]);

    }
    
    public function getLastRequest(){
        $user = getAuthenticatedUserId();
        $userId = $user->user_id;
    
        $lastBloodRequest = BloodRequest::where('user_id', $userId)
            ->join('blood_components', 'blood_request.blood_component_id', '=', 'blood_components.blood_component_id')
            ->where('blood_request.status', 0)
            ->latest('blood_request.created_at')
            ->first();  
    
            return response()->json([
                'status'    => 'success',
                'data'      => $lastBloodRequest
            ]);
    
    }
    

    public function getBloodComponent(){
        $components = BloodComponent::all();

        return response()->json([
            'status'    => 'success',
            'data'      => $components
        ]);
    }
}
