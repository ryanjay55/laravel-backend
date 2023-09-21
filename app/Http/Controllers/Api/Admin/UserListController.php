<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditTrail;
use App\Models\UserDetail;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Dompdf\Dompdf;

class UserListController extends Controller
{
    public function getUserDetails()
    {
        $userDetails = UserDetail::join('users', 'user_details.user_id', '=', 'users.user_id')
            ->where('user_details.isDeffered', 0)
            ->where('user_details.status', 0)
            ->select('users.mobile', 'users.email','user_details.*')
            ->paginate('7');
            

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

    public function exportUserDetailsAsPdf(){
        $userDetails = UserDetail::join('users', 'user_details.user_id', '=', 'users.user_id')
            ->where('user_details.isDeffered', 0)
            ->where('user_details.status', 0)
            ->select('users.mobile', 'users.email','user_details.*')
            ->paginate('7');
        
            if ($userDetails->isEmpty()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'No donor found.'
                ], 200);
            } else {
                // Generate the PDF
                $pdf = new Dompdf();
                $html = view('user-details', ['userDetails' => $userDetails])->render();
                $pdf->loadHtml($html);
                $pdf->render();
        
                // Return the PDF as a response
                return response($pdf->output(), 200)
                    ->header('Content-Type', 'application/pdf')
                    ->header('Content-Disposition', 'attachment; filename="user-details.pdf"');
            }
    }

    public function searchUsers(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'searchInput' => 'required',
            ]);

            $searchInput = str_replace(' ', '', $request->input('searchInput')); 
            
            $userDetails = UserDetail::join('users', 'user_details.user_id', '=', 'users.user_id')
                ->where('user_details.isDeffered', 0)
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
                ->paginate(7);


            if($userDetails->isEmpty()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'No donor found.'
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
                'user_id' => 'required',
            ]);

            $ch = curl_init('http://ipwho.is/');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HEADER, false);

            $ipwhois = json_decode(curl_exec($ch), true);

            curl_close($ch);

            $user_detail = UserDetail::where('user_id', $validatedData['user_id'])->first();

            if ($user_detail) {
                
                if($user_detail->isDeffered === 1) {
                    
                    return response()->json([
                        'status'  => 'error',
                        'message' => 'This Donor is already in deferral list',
                    ], 400);

                }else{
                    $user_detail->isDeffered = 1;
                    $user_detail->save();

                    AuditTrail::create([
                        'user_id'    => $userId,
                        'action'     => 'Move to Deferral | donor no: ' . $user_detail->donor_no,
                        'status'     => 'success',
                        'ip_address' => $ipwhois['ip'],
                        'region'     => $ipwhois['region'],
                        'city'       => $ipwhois['city'],
                        'postal'     => $ipwhois['postal'],
                        'latitude'   => $ipwhois['latitude'],
                        'longitude'  => $ipwhois['longitude'],
                    ]);

                    return response()->json([
                        'status'  => 'success',
                        'message' => 'Successfully moved to deferral list',
                    ], 200);
                }

            } else {
                AuditTrail::create([
                    'user_id'    => $userId,
                    'action'     => 'Move to Deferral | donor no: N/A',
                    'status'     => 'failed',
                    'ip_address' => $ipwhois['ip'],
                    'region'     => $ipwhois['region'],
                    'city'       => $ipwhois['city'],
                    'postal'     => $ipwhois['postal'],
                    'latitude'   => $ipwhois['latitude'],
                    'longitude'  => $ipwhois['longitude'],
                ]);

                return response()->json([
                    'status'  => 'error',
                    'message' => 'Donor not found.',
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



    public function getDeferralList()
    {
        $userDetails = UserDetail::where('isDeffered', 1)
            ->where('status', 0)
            ->get();

        if ($userDetails->isEmpty()) {
            return response()->json([
                'status' => 'success',
                'message' => 'No donor has been deferred.'
            ], 200);
        } else {
            return response()->json([
                'status' => 'success',
                'data' => $userDetails
            ], 200);
        }
    }

    
}
