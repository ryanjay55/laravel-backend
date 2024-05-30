<?php

namespace App\Http\Controllers\Api\admin\Network;

use App\Http\Controllers\Controller;
use App\Models\AuditTrail;
use App\Models\BloodRequest;
use App\Models\AdminPost;
use App\Models\UserDetail;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class NetworkAdminController extends Controller
{
    public function markAsAccomodated(Request $request)
    {
        $user = Auth::user();
        $userId = $user->user_id;

        try {

            $validatedData = $request->validate([
                'blood_request_id' => 'required',
            ]);

            $ip = file_get_contents('https://api.ipify.org');
            $ch = curl_init('http://ipwho.is/' . $ip);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HEADER, false);

            $ipwhois = json_decode(curl_exec($ch), true);
            curl_close($ch);

            $bloodRequestId = $validatedData['blood_request_id'];
            $bloodRequest = BloodRequest::where('blood_request_id', $bloodRequestId)->first();
            $adminPost = AdminPost::where('blood_request_id', $bloodRequestId)->first();
            if (empty($bloodRequest)) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Blood request not found',
                ], 400);
            } else {
                $bloodRequest->update(['isAccommodated' => 1]);
                $adminPost->update(['status' => 1]);

                AuditTrail::create([
                    'user_id'    => $userId,
                    'module'     => 'Network',
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


            return response()->json([
                'status'    => 'success',
                'message'   => 'Blood request mark as accomodated successfully',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Validation failed',
                'errors'  => $e->validator->errors(),
            ], 400);
        }
    }

    public function markAsReferred(Request $request)
    {
        $user = Auth::user();
        $userId = $user->user_id;

        try {

            $validatedData = $request->validate([
                'blood_request_id' => 'required',
                'remarks'   => 'required',
            ]);

            $ip = file_get_contents('https://api.ipify.org');
            $ch = curl_init('http://ipwho.is/' . $ip);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HEADER, false);

            $ipwhois = json_decode(curl_exec($ch), true);
            curl_close($ch);

            $bloodRequestId = $validatedData['blood_request_id'];
            $bloodRequest = BloodRequest::where('blood_request_id', $bloodRequestId)->first();

            if (empty($bloodRequest)) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Blood request not found',
                ], 400);
            } else {
                $bloodRequest->update(['isAccommodated' => 2]);
                $bloodRequest->remarks = $validatedData['remarks'];
                $bloodRequest->save();

                AuditTrail::create([
                    'user_id'    => $userId,
                    'module'     => 'Network',
                    'action'     => 'Mark as Referred | Blood Request ID: ' . $bloodRequestId,
                    'status'     => 'success',
                    'ip_address' => $ipwhois['ip'],
                    'region'     => $ipwhois['region'],
                    'city'       => $ipwhois['city'],
                    'postal'     => $ipwhois['postal'],
                    'latitude'   => $ipwhois['latitude'],
                    'longitude'  => $ipwhois['longitude'],
                ]);
            }


            return response()->json([
                'status'    => 'success',
                'message'   => 'Blood request mark as referred successfully',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Validation failed',
                'errors'  => $e->validator->errors(),
            ], 400);
        }
    }

    public function getAllBloodRequest()
    {

        $bloodRequests = BloodRequest::join('user_details', 'blood_request.user_id', '=', 'user_details.user_id')
            ->join('blood_components', 'blood_request.blood_component_id', '=', 'blood_components.blood_component_id')
            ->join('users', 'user_details.user_id', '=', 'users.user_id')
            ->where('blood_request.status', 0)
            ->select('blood_request.*', 'user_details.first_name', 'user_details.middle_name', 'user_details.last_name', 'user_details.blood_type', 'blood_components.blood_component_desc', 'users.email', 'users.mobile')
            ->orderBy('blood_request.isAccommodated', 'asc')
            ->get();

        return response()->json([
            'status'    => 'success',
            'data'      => $bloodRequests
        ]);
    }

    public function getRequestIdNumber()
    {
        $requestId = app(BloodRequest::class)->getAllRequestId();


        return response()->json([
            'status' => 'success',
            'data' => $requestId
        ]);
    }

    public function createPost(Request $request)
    {
        $user = Auth::user();
        $userId = $user->user_id;

        try {
            $request->validate([
                'blood_request_id' => 'required',
                'donation_date' => 'required',
                'venue' => 'required',
                'body' => 'required',
            ]);

            $requestIdNumber = $request->input('blood_request_id');
            $donationDate = $request->input('donation_date');
            $venue = $request->input('venue');
            $body = $request->input('body');
            // $blood_needs = $request->input('blood_needs');

            $bloodRequest = BloodRequest::where('blood_request_id', $requestIdNumber)->first();
            $user_id = $bloodRequest->user_id;
            $bloodType = UserDetail::where('user_id', $user_id)->first();

            if ($donationDate > $bloodRequest->schedule) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid Donation Date. The blood transfusion for this request is on ' . Carbon::parse($bloodRequest->schedule)->format('F j, Y'),
                ], 400);
            } else {
                AdminPost::create([
                    'blood_request_id' => $requestIdNumber,
                    'donation_date' => $donationDate,
                    'venue' => $venue,
                    'body' => $body,
                    'blood_needs' => $bloodType->blood_type
                ]);

                $ip = file_get_contents('https://api.ipify.org');
                $ch = curl_init('http://ipwho.is/' . $ip);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HEADER, false);
                $ipwhois = json_decode(curl_exec($ch), true);
                curl_close($ch);


                AuditTrail::create([
                    'user_id'    => $userId,
                    'module'     => 'Network',
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
            }
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->validator->errors(),
            ], 400);
        }
    }

    public function getInterestedDonor()
    {

        $interestedDonors = DB::table('interested_donors as i')
            ->join('user_details as ud', 'i.user_id', '=', 'ud.user_id')
            ->join('users as u', 'ud.user_id', '=', 'u.user_id')
            ->select('i.blood_request_id', 'ud.remarks','ud.first_name', 'ud.middle_name', 'ud.last_name', 'ud.blood_type', 'u.email', 'u.mobile')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $interestedDonors
        ]);
    }

    public function getCreatedPosts()
    {
        $adminPosts = DB::table('admin_posts as ap')
            ->leftJoin('interested_donors as i', 'ap.blood_request_id', '=', 'i.blood_request_id') // changed to leftJoin
            ->leftJoin('user_details as ud', 'i.user_id', '=', 'ud.user_id') // changed to leftJoin
            ->leftJoin('users as u', 'ud.user_id', '=', 'u.user_id') // changed to leftJoin
            ->select(
                'ap.blood_request_id',
                'ap.donation_date',
                'ap.venue',
                'ap.body',
                'ap.created_at',
                'ap.blood_needs',
                'ud.first_name',
                'ud.middle_name',
                'ud.last_name',
                'ud.blood_type',
                'ud.remarks',
                'u.email',
                'u.mobile'
            )
            ->where('ap.status', 0)
            ->get();
        //dd($adminPosts);

        // Group the donors by blood_request_id
        $groupedPosts = $adminPosts->reduce(function ($carry, $item) {
            $carry[$item->blood_request_id]['blood_request_id'] = $item->blood_request_id;
            $carry[$item->blood_request_id]['donation_date'] = $item->donation_date;
            $carry[$item->blood_request_id]['venue'] = $item->venue;
            $carry[$item->blood_request_id]['body'] = $item->body;
            $carry[$item->blood_request_id]['blood_needs'] = $item->blood_needs;
            $carry[$item->blood_request_id]['created_at'] = $item->created_at;

            // Check if donor details are present before adding
            if (isset($item->first_name)) { // Assuming 'first_name' as an indicator of donor details
                $donorDetails = [
                    'first_name'   => $item->first_name,
                    'middle_name'  => $item->middle_name,
                    'last_name'    => $item->last_name,
                    'blood_type'   => $item->blood_type,
                    'email'        => $item->email,
                    'mobile'       => $item->mobile,
                    'remarks'      => $item->remarks
                ];

                $carry[$item->blood_request_id]['interested_donors'][] = $donorDetails;
            } else {
                $carry[$item->blood_request_id]['interested_donors'] = []; // Initialize as empty array if no donors
            }

            return $carry;
        }, []);

        // Convert the grouped posts to a list
        $data = array_values($groupedPosts);

        return response()->json([
            'status' => 'success',
            'data' => $data
        ]);
    }

    public function editCreatedPost(Request $request)
    {
        $user = Auth::user();
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

            $bloodRequest = BloodRequest::where('blood_request_id', $requestIdNumber)->first();
            $adminPosts = AdminPost::where('blood_request_id', $requestIdNumber)->first();

            if ($donationDate > $bloodRequest->schedule) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid Donation Date. The blood transfusion for this request is on ' . Carbon::parse($bloodRequest->schedule)->format('F j, Y'),
                ], 400);
            } else {

                $adminPosts->donation_date = $donationDate;
                $adminPosts->venue = $venue;
                $adminPosts->body = $body;
                $adminPosts->blood_needs = $blood_needs;
                $adminPosts->save();

                $ip = file_get_contents('https://api.ipify.org');
                $ch = curl_init('http://ipwho.is/' . $ip);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HEADER, false);
                $ipwhois = json_decode(curl_exec($ch), true);
                curl_close($ch);


                AuditTrail::create([
                    'user_id'    => $userId,
                    'module'     => 'Network',
                    'action'     => 'Edit Post for' . $requestIdNumber,
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
            }
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->validator->errors(),
            ], 400);
        }
    }

    public function deleteCreatedPost(Request $request)
    {
        $user = Auth::user();
        $userId = $user->user_id;


        $bloodRequestId = $request->input('blood_request_id');

        $post = AdminPost::where('blood_request_id', $bloodRequestId)->first();

        $ip = file_get_contents('https://api.ipify.org');
        $ch = curl_init('http://ipwho.is/' . $ip);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        $ipwhois = json_decode(curl_exec($ch), true);
        curl_close($ch);


        AuditTrail::create([
            'user_id'    => $userId,
            'module'     => 'Network',
            'action'     => 'Delete Post for' . $bloodRequestId,
            'status'     => 'Success',
            'ip_address' => $ipwhois['ip'],
            'region'     => $ipwhois['region'],
            'city'       => $ipwhois['city'],
            'postal'     => $ipwhois['postal'],
            'latitude'   => $ipwhois['latitude'],
            'longitude'  => $ipwhois['longitude'],
        ]);


        $post->status = 1;
        $post->save();

        return response()->json([
            'status' => 'success',
            'data' => $post
        ]);
    }
}
