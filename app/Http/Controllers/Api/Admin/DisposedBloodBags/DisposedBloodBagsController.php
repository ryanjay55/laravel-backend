<?php

namespace App\Http\Controllers\Api\Admin\DisposedBloodBags;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use App\Models\AuditTrail;
use Dompdf\Dompdf;
use Illuminate\Support\Facades\Auth;

use App\Models\BloodBag;

class DisposedBloodBagsController extends Controller
{
    public function filterDisposedBloodBags(Request $request){

        $bloodType = $request->blood_type;
        $bbType = $request->bbType;
        $startDate = $request->startDate;
        $endDate = $request->endDate;

        $bloodBags = BloodBag::join('user_details', 'blood_bags.user_id', '=', 'user_details.user_id')
            ->where('isDisposed', '1');

        // Apply filters based on parameters
        if ($bloodType !== 'All') {
            $bloodBags->where('user_details.blood_type', $bloodType);
        }

        if ($bbType !== 'All') {
            $bloodBags->where('blood_bags.unsafe', $bbType);
        }

        if (!empty($startDate)) {
            $bloodBags->whereDate('blood_bags.disposed_date', '>=', $startDate);
        }

        if (!empty($endDate)) {
            $bloodBags->whereDate('blood_bags.disposed_date', '<=', $endDate);
        }

        $result = $bloodBags->get();
        $totalCount = $bloodBags->count();
        return response()->json([
            'status' => 'success',
            'data' => $result,
            'total_count' => $totalCount,
        ]);
    }

    public function getDisposedBloodBag(){

        $bloodBags = BloodBag::join('user_details', 'blood_bags.user_id', '=', 'user_details.user_id')
            ->where('isDisposed', '1')
            ->get();


            if($bloodBags->isEmpty()){
                return response()->json([
                    'status' => 'success',
                    'message' => 'deferral blood bag',
                ]);
            }else{

                $totalCount = $bloodBags->count();

                return response()->json([
                    'status' => 'success',
                    'data' => $bloodBags,
                    'total_count' => $totalCount
                ]);
            }

    }

    public function searchDisposedBloodBag(Request $request){
        try {
            $request->validate([
                'searchInput' => 'required',
            ]);

            $searchInput = str_replace(' ', '', $request->input('searchInput'));

            $bloodBags = BloodBag::join('user_details', 'blood_bags.user_id', '=', 'user_details.user_id')
            ->where('isDisposed', '1')
            ->where(function ($query) use ($searchInput) {
                    $query->where('blood_bags.serial_no', 'LIKE', '%' . $searchInput . '%')
                        ->orWhere('user_details.blood_type', 'LIKE', '%' . $searchInput . '%')
                        ->orWhere('user_details.donor_no', 'LIKE', '%' . $searchInput . '%')
                        ->orWhere('blood_bags.date_donated', 'LIKE', '%' . $searchInput . '%')
                        ->orWhere('blood_bags.expiration_date', 'LIKE', '%' . $searchInput . '%')
                        ->orWhere('blood_bags.disposed_date', 'LIKE', '%' . $searchInput . '%');
                })
            ->select('blood_bags.blood_bags_id','blood_bags.serial_no','user_details.donor_no','user_details.blood_type','user_details.first_name', 'user_details.last_name','blood_bags.date_donated', 'blood_bags.unsafe','blood_bags.expiration_date', 'blood_bags.disposed_date')
            ->paginate(8);

            if($bloodBags->isEmpty()){
                return response()->json([
                    'status' => 'success',
                    'message' => 'No disposed blood bag',
                ]);
            }else{

                return response()->json([
                    'status' => 'success',
                    'data' => $bloodBags,
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

    public function exportDisposedAsPdf(Request $request){
        $bloodType = $request->blood_type;
        $bbType = $request->bbType;
        $startDate = $request->startDate;
        $endDate = $request->endDate;

        $user = Auth::user();
        $userId = $user->user_id;

        $bloodBags = BloodBag::join('user_details', 'blood_bags.user_id', '=', 'user_details.user_id')
            ->where('isDisposed', '1');

        // Apply filters based on parameters
        if ($bloodType !== 'All') {
            $bloodBags->where('user_details.blood_type', $bloodType);
        }

        if ($bbType !== 'All') {
            $bloodBags->where('blood_bags.unsafe', $bbType);
        }

        if (!empty($startDate)) {
            $bloodBags->whereDate('blood_bags.disposed_date', '>=', $startDate);
        }

        if (!empty($endDate)) {
            $bloodBags->whereDate('blood_bags.disposed_date', '<=', $endDate);
        }

        $inventory = $bloodBags->select('blood_bags.blood_bags_id','blood_bags.serial_no','user_details.donor_no','user_details.blood_type','user_details.first_name', 'user_details.last_name','blood_bags.date_donated', 'blood_bags.unsafe','blood_bags.expiration_date', 'blood_bags.disposed_date')
        ->orderBy('blood_bags.disposed_date', 'desc')
        ->get();
        //dd($inventory);

        $totalCount = $inventory->count();

        $ip = file_get_contents('https://api.ipify.org');
        $ch = curl_init('http://ipwho.is/'.$ip);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);

        $ipwhois = json_decode(curl_exec($ch), true);

        curl_close($ch);

        AuditTrail::create([
            'user_id'    => $userId,
            'module'     => 'Disposed Blood Bags',
            'action'     => 'Export Disposed Blood Bags as PDF',
            'status'     => 'success',
            'ip_address' => $ipwhois['ip'],
            'region'     => $ipwhois['region'],
            'city'       => $ipwhois['city'],
            'postal'     => $ipwhois['postal'],
            'latitude'   => $ipwhois['latitude'],
            'longitude'  => $ipwhois['longitude'],
        ]);

        $totalBloodBags = $totalCount;
        $dateNow = new \DateTime();
        $formattedDate = $dateNow->format('F j, Y g:i A');

        $pdf = new Dompdf();
        $pdf->setPaper('A4', 'landscape');
        $html = view('disposed-bag-details', ['inventory' => $inventory, 'totalBloodBags' => $totalBloodBags, 'dateNow' => $formattedDate])->render();
        $pdf->loadHtml($html);
        $pdf->render();

        // Return the PDF as a response
        return response($pdf->output(), 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="donor-list.pdf"');
    }

}
