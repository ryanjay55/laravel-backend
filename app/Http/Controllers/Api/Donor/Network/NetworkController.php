<?php

namespace App\Http\Controllers\Api\Donor\Network;

use App\Http\Controllers\Controller;
use App\Models\AdminPost;
use App\Models\BloodBag;
use App\Models\BloodComponent;
use App\Models\BloodRequest;
use App\Models\InterestedDonor;
use App\Models\UserDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use DateTime;
use Illuminate\Support\Facades\Auth;

class NetworkController extends Controller
{

    public function createBloodRequest(Request $request)
    {
        $user = Auth::user();
        $userId = $user->user_id;
        try {

            $validatedData = $request->validate([
                'blood_units'    => ['required', 'numeric'],
                'blood_component_id'  => ['required'],
                'hospital'    => ['required'],
                'diagnosis'    => ['required'],
                'schedule'    => ['required', 'date', 'after_or_equal:today']
            ], [
                'blood_component_id.required'  => 'The blood component field is required.',
                'schedule.after_or_equal' => 'Invalid date. The schedule must not be in the past.',
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

            $existedUser = BloodRequest::where('user_id', $userId)->where('status', 0)->orderBy('blood_request_id', 'desc')->first();

            if ($existedUser) {

                if ($existedUser->isAccommodated == 1) {

                    do {
                        $uniqueRequestId = mt_rand(100000, 999999); // Generate a random 6-digit number
                    } while (BloodRequest::where('request_id_number', $uniqueRequestId)->exists());

                    BloodRequest::create([
                        'user_id' => $userId,
                        'request_id_number' => $uniqueRequestId,
                        'blood_units' => $bloodUnit,
                        'blood_component_id' => $bloodComponentId,
                        'hospital' => ucwords(strtolower($hospital)),
                        'diagnosis' => ucwords(strtolower($diagnosis)),
                        'schedule' => $humanReadableDate
                    ]);

                    return response()->json([
                        'status'    => 'success',
                        'message'   => 'Blood request created successfully',
                    ]);
                } elseif ($existedUser->isAccommodated == 2) {

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
                } elseif ($existedUser->isAccommodated == 3) {
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
                } else {

                    return response()->json([
                        'status'    => 'error',
                        'message'   => 'You cannot make a new blood request while there is a pending request.',
                    ], 400);
                }
            } else {

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
                'message'   => 'Please check your input and try again',
                'errors'    => $e->validator->errors(),
            ], 400);
        }
    }

    //history
    public function getBloodRequest()
    {
        $user = Auth::user();
        $userId = $user->user_id;

        $bloodRequest = BloodRequest::where('blood_request.user_id', $userId)
            ->join('blood_components', 'blood_request.blood_component_id', '=', 'blood_components.blood_component_id')
            ->join('user_details', 'blood_request.user_id', '=', 'user_details.user_id')
            ->select('blood_request.*', 'user_details.blood_type', 'blood_components.blood_component_desc')
            ->orderBy('blood_request.blood_request_id', 'desc')
            ->get();

        return response()->json([
            'status'    => 'success',
            'data'      => $bloodRequest
        ]);
    }

    public function getLastRequest()
    {
        $user = Auth::user();
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


    public function getBloodComponent()
    {
        $components = BloodComponent::all();

        return response()->json([
            'status'    => 'success',
            'data'      => $components
        ]);
    }

    public function adminPost()
    {
        $user = Auth::user();
        $userId = $user->user_id;

        $post = app(AdminPost::class)->getPendingPost();

        return response()->json([
            'status'    => 'success',
            'data'      => $post
        ]);
    }

    public function getRecentPost()
    {

        $recentPost = AdminPost::latest()->first();

        return response()->json([
            'status' => 'success',
            'data' => $recentPost
        ]);
    }

    public function buttonInterested(Request $request)
    {
        $user = Auth::user();
        $userId = $user->user_id;

        try {
            $request->validate([
                'blood_request_id' => 'required',
                'patient_blood_type' => 'required',
            ]);

            $requestId = $request->input('blood_request_id');

            $patientBloodType = $request->input('patient_blood_type');
            $userBloodType = UserDetail::where('user_id', $userId)->first()->blood_type;


            // Get the donation history of the user
            $donationHistory = BloodBag::where('user_id', $userId)
                ->orderBy('date_donated', 'desc')
                ->get();

            $myInterest = DB::table('interested_donors')
                ->join('admin_posts', 'interested_donors.blood_request_id', '=', 'admin_posts.blood_request_id')
                ->select('interested_donors.*', 'admin_posts.donation_date', 'admin_posts.status as ap_status')
                ->where('interested_donors.user_id', $userId)
                ->orderBy('interested_donor_id', 'desc')
                ->first();
            //dd($myInterest);
            $checkMyBloodRequest = BloodRequest::where('user_id', $userId)->orderBy('blood_request_id', 'desc')->first();

            if (!$this->isBloodTypeCompatible($patientBloodType, $userBloodType)) {

                return response()->json([
                    'status' => 'error',
                    'message' => 'Blood type is not compatible'
                ], 400);
            } else {

                if (!$checkMyBloodRequest) {

                    if (!$myInterest) {
                        InterestedDonor::create([
                            'user_id' => $userId,
                            'blood_request_id' => $requestId,

                        ]);

                        return response()->json([
                            'status' => 'success'
                        ]);
                    } elseif ($myInterest->status == 0) {
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
                    } else {
                        InterestedDonor::create([
                            'user_id' => $userId,
                            'blood_request_id' => $requestId,

                        ]);

                        return response()->json([
                            'status' => 'success'
                        ]);
                    }
                } else {

                    if ($checkMyBloodRequest->status == 1) {
                        //  dd('sdasd');
                        if ($myInterest->status == 0) {

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
                        } else {
                            if ($donationHistory->isEmpty()) {
                                InterestedDonor::create([
                                    'user_id' => $userId,
                                    'blood_request_id' => $requestId,
                                ]);

                                return response()->json([
                                    'status' => 'success'
                                ]);
                            } else {
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

                                    if ($myInterest) {

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
                                    } else {
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
                    } else if ($checkMyBloodRequest->isAccommodated == 0) {
                        $errorMessage = "Sorry, you cannot perform this action because you have a pending blood request at the moment.";
                        return response()->json([
                            'status' => 'error',
                            'message' => $errorMessage
                        ], 400);
                    } else {

                        if (!$myInterest) {
                            InterestedDonor::create([
                                'user_id' => $userId,
                                'blood_request_id' => $requestId,

                            ]);

                            return response()->json([
                                'status' => 'success'
                            ]);
                        } else {
                            if ($myInterest->status == 0) {

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
                            } else {
                                if ($donationHistory->isEmpty()) {
                                    InterestedDonor::create([
                                        'user_id' => $userId,
                                        'blood_request_id' => $requestId,
                                    ]);

                                    return response()->json([
                                        'status' => 'success'
                                    ]);
                                } else {
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

                                        if ($myInterest) {

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
                                        } else {
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

    public function isBloodTypeCompatible($patientBloodType, $userBloodType)
    {
        $compatibility = [
            'A+' => ['A+', 'A-', 'O+', 'O-'],
            'A-' => ['A-', 'O-'],
            'B+' => ['B+', 'B-', 'O+', 'O-'],
            'B-' => ['B-', 'O-'],
            'AB+' => ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'],
            'AB-' => ['AB-', 'A-', 'B-', 'O-'],
            'O+' => ['O+', 'O-'],
            'O-' => ['O-'],
        ];

        // Check if the patient's blood type is in the array
        if (!array_key_exists($patientBloodType, $compatibility)) {
            return false;
        }

        // Check if the donor's blood type is compatible
        if (in_array($userBloodType, $compatibility[$patientBloodType])) {
            return true;
        }

        return false;
    }

    public function getMyInterestDonation()
    {
        $user = Auth::user();
        $userId = $user->user_id;

        $myInterest = InterestedDonor::where('user_id', $userId)->pluck('blood_request_id');

        return response()->json([
            'status'    => 'success',
            'data'      => $myInterest
        ]);
    }

    public function getMyScheduleDonation()
    {
        $user = Auth::user();
        $userId = $user->user_id;

        $myInterest = DB::table('interested_donors')
            ->join('admin_posts', 'interested_donors.blood_request_id', '=', 'admin_posts.blood_request_id')
            ->select('interested_donors.created_at', 'admin_posts.venue', 'admin_posts.donation_date',)
            ->where('interested_donors.user_id', $userId)
            ->orderBy('interested_donor_id', 'desc')
            ->first();


        if ($myInterest && strtotime($myInterest->donation_date) >= time()) {
            return response()->json([
                'status' => 'success',
                'data' => $myInterest
            ]);
        } else {
            return response()->json([
                'status' => 'success',
                'data' => null
            ]);
        }
    }

    public function cancelRequest(Request $request)
    {
        $user = Auth::user();
        $userId = $user->user_id;

        try {
            $request->validate([
                'reason'  => ['required'],
            ]);

            $reason = $request->input('reason');
            $lastBloodRequest = BloodRequest::where('user_id', $userId)
                ->join('blood_components', 'blood_request.blood_component_id', '=', 'blood_components.blood_component_id')
                ->where('blood_request.status', 0)
                ->latest('blood_request.created_at')
                ->first();

            if ($lastBloodRequest) {
                $lastBloodRequest->isAccommodated = 3;
                $lastBloodRequest->isCancelled = 1;
                $lastBloodRequest->cancel_reason = $reason;
                $lastBloodRequest->save();

                return response()->json([
                    'status'    => 'success',
                    'data'      => $lastBloodRequest
                ]);
            } else {
                return response()->json([
                    'status'    => 'error',
                    'message'   => 'Blood request not found.'
                ], 404);
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
