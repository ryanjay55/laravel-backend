<?php

namespace App\Http\Controllers\Api\Admin\DonorList;

use App\Http\Controllers\Controller;
use App\Models\AuditTrail;
use App\Models\BloodBag;
use App\Models\UserDetail;
use Dompdf\Dompdf;
use Faker\Core\Blood;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DonorController extends Controller
{
    
    public function donorList() {
        $donorList = UserDetail::join('users', 'user_details.user_id', '=', 'users.user_id')
            ->join('galloners', 'user_details.user_id', '=', 'galloners.user_id')
            ->join('blood_bags', 'user_details.user_id', '=', 'blood_bags.user_id')
            ->where('user_details.remarks', 0)
            ->where('user_details.status', 0)
            ->where('galloners.donate_qty', '>', 0) 
            ->select('users.mobile', 'users.email', 'user_details.*', 'galloners.badge', 'galloners.donate_qty')
            ->distinct('user_details.user_id')
            ->orderBy('blood_bags.date_donated', 'desc')
            ->paginate(8);
        
        // Transform the results to include a list of blood bags for each user
        $donorList->transform(function ($donor) {
            $bloodBags = BloodBag::where('user_id', $donor->user_id)
                ->select('serial_no', 'date_donated')
                ->orderBy('date_donated', 'asc')
                ->get();
            
            $donor->blood_bags = $bloodBags;
            
            // Retrieve the last date_donated
            $lastDonated = $bloodBags->last()->date_donated ?? null;
            
            $donor->last_donated = $lastDonated;
            
            return $donor;
        });
    
        return response()->json([
            'status' => 'success',
            'data' => $donorList
        ]);
    }
    
    // public function donorList() {
    //     $donorList = UserDetail::join('users', 'user_details.user_id', '=', 'users.user_id')
    //         ->join('galloners', 'user_details.user_id', '=', 'galloners.user_id')
    //         ->join('blood_bags', 'user_details.user_id', '=', 'blood_bags.user_id')
    //         ->where('user_details.remarks', 0)
    //         ->where('user_details.status', 0)
    //         ->where('galloners.donate_qty', '>', 0) 
    //         ->select('users.mobile', 'users.email', 'user_details.*', 'galloners.badge', 'galloners.donate_qty','blood_bags.serial_no', 'blood_bags.date_donated')
    //         ->orderBy('blood_bags.date_donated', 'desc')
    //         ->paginate(8);
    
    //     // Transform the results to include a list of blood bags for each user
    //     // $donorList->transform(function ($donor) {
    //     //     $bloodBags = BloodBag::where('user_id', $donor->user_id)
    //     //         ->select('serial_no', 'date_donated')
    //     //         ->orderBy('date_donated', 'desc')
    //     //         ->get();
    //     //     $donor->blood_bags = $bloodBags;
    //     //     return $donor;
    //     // });
    
    //     return response()->json([
    //         'status' => 'success',
    //         'data' => $donorList
    //     ]);
    // }

    public function searchDonor(Request $request){
        try {
            $request->validate([
                'searchInput' => 'required',
            ]);

            $searchInput = str_replace(' ', '', $request->input('searchInput')); 
            
            $userDetails = UserDetail::join('users', 'user_details.user_id', '=', 'users.user_id')
                ->join('galloners', 'user_details.user_id', '=', 'galloners.user_id')
                ->where('galloners.donate_qty', '>', 0) 
                ->where('user_details.remarks', 0)
                ->where('user_details.status', 0)
                ->where(function ($query) use ($searchInput) {
                    $query->where('users.mobile', 'LIKE', '%' . $searchInput . '%')
                        ->orWhere('users.email', 'LIKE', '%' . $searchInput . '%')
                        ->orWhere('user_details.donor_no', 'LIKE', '%' . $searchInput . '%')
                        ->orWhere('user_details.first_name', 'LIKE', '%' . $searchInput . '%')
                        ->orWhere('user_details.last_name', 'LIKE', '%' . $searchInput . '%')
                        ->orWhere('user_details.blood_type', 'LIKE', '%' . $searchInput . '%')
                        ->orWhere('user_details.dob', 'LIKE', '%' . $searchInput . '%')
                        ->orWhere('galloners.badge', 'LIKE', '%' . $searchInput . '%')
                        ->orWhere('galloners.donate_qty', 'LIKE', '%' . $searchInput . '%');
                        
                })
                ->select('users.mobile', 'users.email', 'user_details.*', 'galloners.badge', 'galloners.donate_qty')
                ->paginate(8);

            // Transform the results to include a list of blood bags for each user
            $userDetails->transform(function ($donor) {
            $bloodBags = BloodBag::where('user_id', $donor->user_id)
                ->select('serial_no', 'date_donated')
                ->get();
            
            $donor->blood_bags = $bloodBags;
            return $donor;
        });

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

    public function exportDonorListAsPdf(){
        $user = getAuthenticatedUserId();
        $userId = $user->user_id;
    
        $donorList = UserDetail::join('users', 'user_details.user_id', '=', 'users.user_id')
            ->join('galloners', 'user_details.user_id', '=', 'galloners.user_id')
            ->where('user_details.remarks', 0)
            ->where('user_details.status', 0)
            ->where('galloners.donate_qty', '>', 0) 
            ->select('users.mobile', 'users.email', 'user_details.*', 'galloners.badge', 'galloners.donate_qty')
            ->get();
    
        $donorsPerBarangay = DB::table('user_details')
            ->leftJoin('blood_bags', 'user_details.user_id', '=', 'blood_bags.user_id')
            ->select('user_details.barangay', DB::raw('count(distinct user_details.user_id) as donor_count'))
            ->where('blood_bags.isCollected', '=', 1)
            ->groupBy('user_details.barangay')
            ->get();
    
        // Count the number of male and female donors
        $maleDonor = $donorList->where('sex', 'Male')->count();
        $femaleDonor = $donorList->where('sex', 'Female')->count();
    
        $ip = file_get_contents('https://api.ipify.org');
        $ch = curl_init('http://ipwho.is/'.$ip);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
    
        $ipwhois = json_decode(curl_exec($ch), true);
    
        curl_close($ch);
    
        AuditTrail::create([
            'user_id'    => $userId,
            'module'     => 'Donor List',
            'action'     => 'Export Donor List as PDF',
            'status'     => 'success',
            'ip_address' => $ipwhois['ip'],
            'region'     => $ipwhois['region'],
            'city'       => $ipwhois['city'],
            'postal'     => $ipwhois['postal'],
            'latitude'   => $ipwhois['latitude'],
            'longitude'  => $ipwhois['longitude'],
        ]);
    
        $totalDonorDetails = $donorList->count();
        $dateNow = new \DateTime();
        $formattedDate = $dateNow->format('F j, Y g:i A');
    
        $pdf = new Dompdf();
        $html = view('donor-details', ['donorDetails' => $donorList, 'donorsPerBarangay' => $donorsPerBarangay, 'maleDonor' => $maleDonor, 'femaleDonor' => $femaleDonor, 'totalDonors' => $totalDonorDetails, 'dateNow' => $formattedDate])->render();
        $pdf->loadHtml($html);
        $pdf->render();
    
        // Return the PDF as a response
        return response($pdf->output(), 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="donor-list.pdf"');
    }
}
