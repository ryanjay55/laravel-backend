<?php

namespace App\Http\Controllers\Api\Donor\Network;

use App\Http\Controllers\Controller;
use App\Models\AdminPost;
use App\Models\BloodBag;
use App\Models\BloodComponent;
use App\Models\BloodRequest;
use App\Models\InterestedDonor;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use DateTime;

class NetworkController extends Controller
{
    
    public function createBloodRequest(Request $request){
        $user = getAuthenticatedUserId();
        $userId = $user->user_id;
        try {
    
            $validatedData = $request->validate([
                'blood_units'    => ['required','numeric'],
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

            $existedUser = BloodRequest::where('user_id', $userId)->where('status',0)->orderBy('blood_request_id', 'desc')->first();

            if($existedUser){

                if ($existedUser->isAccommodated == 1) {

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

    //history
    public function getBloodRequest(){
        $user = getAuthenticatedUserId();
        $userId = $user->user_id;

        $bloodRequest = BloodRequest::where('blood_request.user_id', $userId)
            ->join('blood_components', 'blood_request.blood_component_id', '=', 'blood_components.blood_component_id')
            ->join('user_details', 'blood_request.user_id', '=', 'user_details.user_id')
            ->select('blood_request.*', 'user_details.blood_type', 'blood_components.blood_component_desc')
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
            // ->where('blood_request.isAccommodated', 0)
            ->orderBy('blood_request.blood_request_id', 'desc')
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

    public function adminPost(){
        $user = getAuthenticatedUserId();
        $userId = $user->user_id;

        $post = app(AdminPost::class)->getPendingPost();

        return response()->json([
            'status'    => 'success',
            'data'      => $post
        ]);
    }

    public function getRecentPost(){
        
    $recentPost = AdminPost::latest()->first();
        
       return response()->json([
        'status' => 'success',
        'data' => $recentPost
        ]);
    }

    public function buttonInterested(Request $request)
    {
        $user = getAuthenticatedUserId();
        $userId = $user->user_id;
    
        try {
            $request->validate([
                'blood_request_id' => 'required'
            ]);
    
            $requestId = $request->input('blood_request_id');
    
            // Get the donation history of the user
            $donationHistory = BloodBag::where('user_id', $userId)
                ->orderBy('date_donated', 'desc')
                ->get();

            $myInterest = DB::table('interested_donors')
                ->join('admin_posts', 'interested_donors.blood_request_id', '=', 'admin_posts.blood_request_id')
                ->select('interested_donors.*', 'admin_posts.donation_date')
                ->where('interested_donors.user_id', $userId)
                ->orderBy('interested_donor_id', 'desc')
                ->first();

            $checkMyBloodRequest = BloodRequest::where('user_id', $userId)->where('isAccommodated', 0)->first();

            if($checkMyBloodRequest){
                $errorMessage = "Sorry, you cannot perform this action because you have a pending blood request at the moment.";
                return response()->json([
                    'status' => 'error',
                    'message' => $errorMessage
                ], 400);
            }

            if($myInterest){

                $donationDate = Carbon::parse($myInterest->donation_date);

                if ($donationDate->isFuture()) {
                    $formattedDate = $donationDate->format('F d, Y \a\t h:i A');
                    $errorMessage = "Sorry, you already have a scheduled donation on $formattedDate";                        
                    return response()->json([
                        'status' => 'error',
                        'message' => $errorMessage
                    ], 400);

                } else {
                    InterestedDonor::create([
                    'user_id' => $userId,
                    'blood_request_id' => $requestId,
                    
                    ]);
        
                    return response()->json([
                        'status' => 'success'
                    ]);
                }   

            }else{
                if ($donationHistory->isEmpty()) {
                    InterestedDonor::create([
                        'user_id' => $userId,
                        'blood_request_id' => $requestId,
                    ]);
        
                    return response()->json([
                        'status' => 'success'
                    ]);
                }else{
                    // Get the most recent donation date
            $mostRecentDonationDate = Carbon::parse($donationHistory->first()->date_donated)->format('F d, Y');
            $nextDonationDate = Carbon::parse($mostRecentDonationDate)->addDays(90)->format('F d, Y');

            // Get the donation date of the post
            $bloodRequest = AdminPost::where('blood_request_id', $requestId)->first();
            $dateOfDonation = Carbon::parse($bloodRequest->donation_date);

            if ($dateOfDonation->lessThan(Carbon::parse($nextDonationDate))) {
                return response()->json([
                    'status' => 'error',
                    'message' => "Sorry, your most recent donation was on $mostRecentDonationDate. You are eligible to donate again on $nextDonationDate."
                ], 400);
            } else {
                
                if($myInterest){

                    $donationDate = Carbon::parse($myInterest->donation_date);

                    if ($donationDate->isFuture()) {
                        $formattedDate = $donationDate->format('F d, Y \a\t h:i A');
                        $errorMessage = "Sorry, you already have a scheduled donation on $formattedDate";                        
                        return response()->json([
                            'status' => 'error',
                            'message' => $errorMessage
                        ], 400);

                    } else {
                        InterestedDonor::create([
                        'user_id' => $userId,
                        'blood_request_id' => $requestId,
                        
                        ]);
            
                        return response()->json([
                            'status' => 'success'
                        ]);
                    }   

                }else{
                    InterestedDonor::create([
                        'user_id' => $userId,
                        'blood_request_id' => $requestId,
                    ]);
        
                    return response()->json([
                        'status' => 'success'
                    ]);
                }
                    
            }
                }
            }

    
            

        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->validator->errors(),
            ], 400);
        }
    }

    public function getMyInterestDonation(){
        $user = getAuthenticatedUserId();
        $userId = $user->user_id;

        $myInterest = InterestedDonor::where('user_id', $userId)->pluck('blood_request_id');

        return response()->json([
            'status'    => 'success',
            'data'      => $myInterest
        ]);
    }

    public function getMyScheduleDonation(){
        $user = getAuthenticatedUserId();
        $userId = $user->user_id;

        $myInterest = DB::table('interested_donors')
                    ->join('admin_posts', 'interested_donors.blood_request_id', '=', 'admin_posts.blood_request_id')
                    ->select('interested_donors.created_at', 'admin_posts.venue', 'admin_posts.donation_date', )
                    ->where('interested_donors.user_id', $userId)
                    ->orderBy('interested_donor_id', 'desc')
                    ->first();

        
        if($myInterest){
            return response()->json([
                'status'    => 'success',
                'data'      => $myInterest
            ]);
        }else{
            return response()->json([
                'status'    => 'success',
                'data'      => null
            ]);
        }
    }

    public function cancelRequest(){
        $user = getAuthenticatedUserId();
        $userId = $user->user_id;
        
        $lastBloodRequest = BloodRequest::where('user_id', $userId)
            ->join('blood_components', 'blood_request.blood_component_id', '=', 'blood_components.blood_component_id')
            ->where('blood_request.status', 0)
            ->latest('blood_request.created_at')
            ->first();  
        
        if ($lastBloodRequest && $lastBloodRequest->created_at->diffInHours(now()) <= 24) {
            $lastBloodRequest->status = 1;
            $lastBloodRequest->save();
        
            return response()->json([
                'status'    => 'success',
                'data'      => $lastBloodRequest
            ]);
        } elseif ($lastBloodRequest) {
            return response()->json([
                'status'    => 'error',
                'message'   => 'Cannot cancel the blood request because the request exceeds 24 hours.'
            ], 400);
        } else {
            return response()->json([
                'status'    => 'error',
                'message'   => 'Blood request not found.'
            ], 404);
        }
    }
}
