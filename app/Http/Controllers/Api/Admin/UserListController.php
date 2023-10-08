<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditTrail;
use App\Models\Deferral;
use App\Models\User;
use App\Models\UserDetail;
use App\Rules\EmailUpdateProfile;
use App\Rules\MobileUpdateProfile;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Dompdf\Dompdf;

class UserListController extends Controller
{
    public function getUserDetails()
    {
        $userDetails = UserDetail::join('users', 'user_details.user_id', '=', 'users.user_id')
            ->join('galloners', 'user_details.user_id', '=', 'galloners.user_id')
            ->where('user_details.status', 0)
            ->where('users.isAdmin', 0)
            ->select('users.mobile', 'users.email', 'user_details.*', 'galloners.badge', 'galloners.donate_qty')
            ->paginate(8);

        if ($userDetails->isEmpty()) { 
            return response()->json([
                'status' => 'success',
                'message' => 'No donor found.'
            ], 200);
        } else {
            return response()->json([
                'status' => 'success',
                'data' => $userDetails
            ], 200);
        }
    }

    public function exportUserDetailsAsPdf(Request $request){
        $user = getAuthenticatedUserId();
        $userId = $user->user_id;

        $userDetails = UserDetail::join('users', 'user_details.user_id', '=', 'users.user_id')
            ->join('galloners', 'user_details.user_id', '=', 'galloners.user_id')
            ->where('user_details.status', 0)
            ->select('users.mobile', 'users.email', 'user_details.*', 'galloners.badge', 'galloners.donate_qty')
            ->get();

            $ip = file_get_contents('https://api.ipify.org');
            $ch = curl_init('http://ipwho.is/'.$ip);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HEADER, false);
        
            $ipwhois = json_decode(curl_exec($ch), true);
        
            curl_close($ch);
            AuditTrail::create([
                'user_id'    => $userId,
                'module'     => 'User List',
                'action'     => 'Export Users List as PDF',
                'status'     => 'success',
                'ip_address' => $ipwhois['ip'],
                'region'     => $ipwhois['region'],
                'city'       => $ipwhois['city'],
                'postal'     => $ipwhois['postal'],
                'latitude'   => $ipwhois['latitude'],
                'longitude'  => $ipwhois['longitude'],
            ]);

            $totalUserDetails = $userDetails->count();
            $dateNow = new \DateTime();
            $formattedDate = $dateNow->format('F j, Y g:i A');

            $pdf = new Dompdf();
            $html = view('user-details', ['userDetails' => $userDetails, 'totalUsers' => $totalUserDetails, 'dateNow' => $formattedDate])->render();
            $pdf->loadHtml($html);
            $pdf->render();
    
            // Return the PDF as a response
            return response($pdf->output(), 200)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="user-details.pdf"');
                
    }

    public function searchUsers(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'searchInput' => 'required',
            ]);

            $searchInput = str_replace(' ', '', $request->input('searchInput')); 
            
            $userDetails = UserDetail::join('users', 'user_details.user_id', '=', 'users.user_id')
                ->where('user_details.remarks', 0)
                ->where('user_details.status', 0)
                ->where(function ($query) use ($searchInput) {
                    $query->where('users.mobile', 'LIKE', '%' . $searchInput . '%')
                        ->orWhere('users.email', 'LIKE', '%' . $searchInput . '%')
                        ->orWhere('user_details.donor_no', 'LIKE', '%' . $searchInput . '%')
                        ->orWhere('user_details.first_name', 'LIKE', '%' . $searchInput . '%')
                        ->orWhere('user_details.last_name', 'LIKE', '%' . $searchInput . '%')
                        ->orWhere('user_details.blood_type', 'LIKE', '%' . $searchInput . '%')
                        ->orWhere('user_details.dob', 'LIKE', '%' . $searchInput . '%');
                })
                ->select('users.mobile', 'users.email', 'user_details.*')
                ->paginate(8);


            if($userDetails->isEmpty()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'No user found.'
                ], 200);
            }else{
                return response()->json([
                    'status' => 'success',
                    'data' => $userDetails
                ], 200);

            }
           
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->validator->errors(),
            ], 400);
        }
    }
    
    public function moveToDeferral(Request $request)
    {
        $user = getAuthenticatedUserId();
        $userId = $user->user_id;

        try {
            $validatedData = $request->validate([
                'user_id'           => 'required',
                'category'          => 'required',
                'specific_reason'   => '',
                'remarks'           => 'required',
                'duration'          => ['numeric','min:1']
            ],[
                'duration.min' => 'Minimum duration is 1 day.',
            ]);
            
            $ip = file_get_contents('https://api.ipify.org');
            $ch = curl_init('http://ipwho.is/'.$ip);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HEADER, false);
            $ipwhois = json_decode(curl_exec($ch), true);
            curl_close($ch);

            $user_detail = UserDetail::where('user_id', $validatedData['user_id'])->first();

            if($user_detail->remarks == 1 || $user_detail->remarks == 2){
                return response()->json([
                    'status' => 'error',
                    'message' => 'User already has a deferral.'
                ], 400);
            }else{

                if($validatedData['remarks'] === '1'){
                    $user_detail->remarks = 1;
                    $user_detail->save();

                    $deferredStartDate = now();
                    $deferredDuration = $validatedData['duration'];
                    
                    $endDateOfDeferral = Carbon::parse($deferredStartDate)
                        ->addDays($deferredDuration)
                        ->addDay() 
                        ->toDateString();

                    Deferral::create([
                        'user_id'           => $validatedData['user_id'],
                        'categories_id'     => $validatedData['category'],
                        'specific_reason'   => $validatedData['specific_reason'],
                        'remarks_id'        => $validatedData['remarks'],
                        'deferred_duration' => $validatedData['duration'],
                        'end_date'          => $endDateOfDeferral
                    ]);

                    AuditTrail::create([
                        'user_id'    => $userId,
                        'module'     => 'Donor List',
                        'action'     => 'Move to Temporary Deferral | donor no: ' . $user_detail->donor_no,
                        'status'     => 'success',
                        'ip_address' => $ipwhois['ip'],
                        'region'     => $ipwhois['region'],
                        'city'       => $ipwhois['city'],
                        'postal'     => $ipwhois['postal'],
                        'latitude'   => $ipwhois['latitude'],
                        'longitude'  => $ipwhois['longitude'],
                    ]);
                }else{
                    $user_detail->remarks = 2;
                    $user_detail->save();

                    Deferral::create([
                        'user_id'   => $validatedData['user_id'],
                        'categories_id'  => $validatedData['category'],
                        'specific_reason' => $validatedData['specific_reason'],
                        'remarks_id'         => $validatedData['remarks'],
                    ]);

                    AuditTrail::create([
                        'user_id'    => $userId,
                        'module'     => 'Donor List',
                        'action'     => 'Move to Permanent Deferral | donor no: ' . $user_detail->donor_no,
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

            

            
            


        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->validator->errors(),
            ], 400);
        }
    }



    public function getTemporaryDeferral()
    {
     
        $userDetails = UserDetail::join('users', 'user_details.user_id', '=', 'users.user_id')
            ->join('deferrals', 'user_details.user_id', '=', 'deferrals.user_id')
            ->join('categories', 'categories.categories_id', '=', 'deferrals.categories_id')
            ->where('user_details.remarks', 1)
            ->where('user_details.status', 0)
            ->select('users.mobile', 'users.email', 'user_details.*', 'deferrals.*','categories.*')
            ->paginate(8);
        


        if ($userDetails->isEmpty()) {
            return response()->json([
                'status' => 'success',
                'message' => 'No donor has been temporary deferred.'
            ], 200);
        } else {
            return response()->json([
                'status' => 'success',
                'data' => $userDetails
            ], 200);
        }
    }

    public function getPermanentDeferral()
    {
     
        $userDetails = UserDetail::join('users', 'user_details.user_id', '=', 'users.user_id')
            ->join('deferrals', 'user_details.user_id', '=', 'deferrals.user_id')
            ->join('categories', 'categories.categories_id', '=', 'deferrals.categories_id')
            ->where('user_details.remarks', 2)
            ->where('user_details.status', 0)
            ->select('users.mobile', 'users.email', 'user_details.*', 'deferrals.*','categories.*')
            ->paginate(8);
        

        if ($userDetails->isEmpty()) {
            return response()->json([
                'status' => 'success',
                'message' => 'No donor has been permanent deferred.'
            ], 200);
        } else {
            return response()->json([
                'status' => 'success',
                'data' => $userDetails
            ], 200);
        }
    }

    public function editUserDetails(Request $request)
    {
        $user = getAuthenticatedUserId();
        $userId = $user->user_id;

        try {
            $validatedData = $request->validate([
                'user_id'               => ['required', 'exists:users,user_id'], 
                'first_name'            => ['required', 'string'],
                'middle_name'           => ['nullable', 'string'],
                'last_name'             => ['required', 'string'],
                'email'                 => ['required', 'string', new EmailUpdateProfile],
                'mobile'                => ['required', 'string', new MobileUpdateProfile],
                'sex'                   => ['required'],
                'dob'                   => ['required'],
                'blood_type'            => ['required'],
                'occupation'            => ['required', 'string'],
                'street'                => ['required', 'string'],
                'region'                => ['required'],
                'province'              => ['required'],
                'municipality'          => ['required'],
                'barangay'              => ['required' ],
                'postalcode'            => ['required', 'integer'],
            ]);

                $ip = file_get_contents('https://api.ipify.org');
                $ch = curl_init('http://ipwho.is/'.$ip);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HEADER, false);

                $ipwhois = json_decode(curl_exec($ch), true);

                curl_close($ch);
        
                $userDetails = UserDetail::where('user_id', $validatedData['user_id'])->first();
                $userDetails->first_name = ucwords(strtolower($validatedData['first_name']));
                $userDetails->middle_name = ucwords(strtolower($validatedData['middle_name']));
                $userDetails->last_name = ucwords(strtolower($validatedData['last_name']));
                $userDetails->sex = $validatedData['sex'];
                $userDetails->dob = $validatedData['dob'];
                $userDetails->blood_type = $validatedData['blood_type'];
                $userDetails->occupation = ucwords(strtolower($validatedData['occupation']));
                $userDetails->street = ucwords(strtolower($validatedData['street']));
                $userDetails->region = $validatedData['region'];
                $userDetails->province = $validatedData['province'];
                $userDetails->municipality = $validatedData['municipality'];
                $userDetails->barangay = $validatedData['barangay'];
                $userDetails->postalcode = $validatedData['postalcode'];
                $userDetails->update();

                $userAuthDetails = User::where('user_id', $validatedData['user_id'])->first();
                $userAuthDetails->mobile = $validatedData['mobile'];
                $userAuthDetails->email = $validatedData['email'];
                $userAuthDetails->update();
           
                AuditTrail::create([
                    'user_id'    => $userId,
                    'module'     => 'User List',
                    'action'     => 'Edit User Details | donor no: ' . $userDetails->donor_no,
                    'status'     => 'success',
                    'ip_address' => $ipwhois['ip'],
                    'region'     => $ipwhois['region'],
                    'city'       => $ipwhois['city'],
                    'postal'     => $ipwhois['postal'],
                    'latitude'   => $ipwhois['latitude'],
                    'longitude'  => $ipwhois['longitude'],
                ]);

                return response()->json([
                    'status'    => 'success',
                    'message'   => 'Profile updated',
                ]);
        
        } catch (ValidationException $e) {

            return response()->json([
                'status'        => 'error',
                'errors'        => $e->validator->errors(),
            ], 422);


        } catch (QueryException $e) {

            return response()->json([
                'status'    => 'error',
                'message'   => 'Database error',
                'errors'    => $e->getMessage(),
            ], 500);
            
        }
    }

    
}
