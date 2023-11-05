<?php

namespace App\Http\Controllers\Api\Donor\Network;

use App\Http\Controllers\Controller;
use App\Models\BloodComponent;
use App\Models\BloodRequest;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

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
            ]);
    
            $bloodUnit = $validatedData['blood_units'];
            $bloodComponentId = $validatedData['blood_component_id'];
            $hospital = $validatedData['hospital'];
            $diagnosis = $validatedData['diagnosis'];
            $schedule = $validatedData['schedule'];     

            $existedUser = BloodRequest::where('user_id', $userId)->first();

            if($existedUser){
                if ($existedUser->isAccomodated == 1) {

                    BloodRequest::create([
                        'user_id' => $userId,
                        'blood_units' => $bloodUnit,
                        'blood_component_id' => $bloodComponentId,
                        'hospital' => $hospital,
                        'diagnosis' => $diagnosis,
                        'schedule' => $schedule                
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
                BloodRequest::create([
                    'user_id' => $userId,
                    'blood_units' => $bloodUnit,
                    'blood_component_id' => $bloodComponentId,
                    'hospital' => $hospital,
                    'diagnosis' => $diagnosis,
                    'schedule' => $schedule                
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

    public function getBloodComponent(){
        $components = BloodComponent::all();

        return response()->json([
            'status'    => 'success',
            'data'      => $components
        ]);
    }
}
