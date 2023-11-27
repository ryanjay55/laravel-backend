<?php

namespace App\Http\Controllers\Api\Admin\DispensedBlood;

use App\Http\Controllers\Controller;
use App\Models\BloodBag;
use Illuminate\Http\Request;
use App\Models\PatientReceiver;
use Dompdf\Dompdf;
use App\Models\AuditTrail;
use Illuminate\Validation\ValidationException;

class DispensedBloodController extends Controller
{
    
    public function dispensedBloodList(Request $request){
        $serialNo = $request->input('serialNo');
        $serialNumbers = $request->input('serialNumbers');
        $dipensedList = app(PatientReceiver::class)->getDispensedList($serialNo);
        $donors = app(PatientReceiver::class)->getDonorWhoDonate($serialNumbers);

        return response()->json([
            'status' => 'success',
            'dipensedList' => $dipensedList,
            'donors' => $donors
        ]);
    }

    public function getAllSerialNumber(){

        $serialNumbers = app(BloodBag::class)->getAllSerialNo();

        return response()->json([
            'status' => 'success',
            'serialNumbers' => $serialNumbers
        ]);
    }

    public function filterDispensedList(Request $request)
    {
 
        $dispensedList = PatientReceiver::leftJoin('blood_bags', function ($join) {
            $join->on('patient_receivers.patient_receivers_id', '=', 'blood_bags.patient_receivers_id')
                ->where('blood_bags.isUsed', 1)
                ->whereRaw('blood_bags.blood_bags_id = (SELECT MAX(blood_bags_id) FROM blood_bags WHERE patient_receivers_id = patient_receivers.patient_receivers_id)');
        })
            ->groupBy('patient_receivers.patient_receivers_id')
            ->select('patient_receivers.*')
            ->orderBy('patient_receivers.created_at', 'desc')
            ->get();

        // Transform the results to include a list of blood bags for each user
        $dispensedList->transform(function ($donor) {
            $bloodBags = BloodBag::where('patient_receivers_id', $donor->patient_receivers_id)
                ->select('serial_no', 'date_donated')
                ->orderBy('date_donated', 'asc')
                ->get();
        
            $donor->blood_bags = $bloodBags;
        
            return $donor;
        });

        $totalCount = $dispensedList->count();

        return response()->json([
            'status' => 'success',
            'data' => $dispensedList,
            'total_count' => $totalCount
        ]);

    }

    public function dispList(Request $request) {
        $bloodType = $request->input('blood_type');
        $payment = $request->input('payment');
        $hospital = $request->input('hospital');
        $startDate = $request->input('startDate');
        $endDate = $request->input('endDate');
    
        $patientReceiver = PatientReceiver::leftJoin('blood_bags', function ($join) {
            $join->on('patient_receivers.patient_receivers_id', '=', 'blood_bags.patient_receivers_id')
                ->where('blood_bags.isUsed', 1)
                ->whereRaw('blood_bags.blood_bags_id = (SELECT MAX(blood_bags_id) FROM blood_bags WHERE patient_receivers_id = patient_receivers.patient_receivers_id)');
        })
        ->leftJoin('hospitals', 'patient_receivers.hospital', '=', 'hospitals.hospitals_id')
        ->groupBy('hospitals.hospital_desc','patient_receivers.patient_receivers_id', 'patient_receivers.user_id', 'patient_receivers.first_name', 'patient_receivers.middle_name', 'patient_receivers.last_name', 'patient_receivers.sex', 'patient_receivers.dob', 'patient_receivers.blood_type', 'patient_receivers.diagnosis', 'patient_receivers.hospital', 'patient_receivers.payment', 'patient_receivers.status', 'patient_receivers.created_at', 'patient_receivers.updated_at')
        ->select('patient_receivers.*','hospitals.hospital_desc');
    
        if ($bloodType !== 'All') {
            $patientReceiver->where('patient_receivers.blood_type', $bloodType);
        }
        
        if ($payment !== 'All') {
            $patientReceiver->where('patient_receivers.payment', $payment);
        }
        
        if ($hospital !== 'All') {
            $patientReceiver->where('hospitals.hospitals_id', $hospital);
        }
    
        if (!empty($startDate) && !empty($endDate)) {
            $patientReceiver->whereBetween('patient_receivers.created_at', [$startDate, $endDate]);
        } 
        
        $patient = $patientReceiver->orderBy('patient_receivers.patient_receivers_id', 'desc')->get();
        
        // Transform the results to include a list of blood bags for each user
        $patient->transform(function ($donor) {
            $bloodBags = BloodBag::where('patient_receivers_id', $donor->patient_receivers_id)
                ->select('serial_no', 'date_donated')
                ->orderBy('date_donated', 'asc')
                ->get();
        
            $donor->blood_bags = $bloodBags;
        
            return $donor;
        });
    
        // $totalCount = $patient->total();
        
        return response()->json([
            'status' => 'success',
            'data' => $patient,
           
        ]);
    }

    public function searchPatient(Request $request) {
        try {
            $request->validate([
                'searchInput' => 'required',
            ]);

            $searchInput = str_replace(' ', '', $request->input('searchInput')); 
            
            $patientReceiver = PatientReceiver::leftJoin('blood_bags', function ($join) {
                $join->on('patient_receivers.patient_receivers_id', '=', 'blood_bags.patient_receivers_id')
                    ->where('blood_bags.isUsed', 1)
                    ->whereRaw('blood_bags.blood_bags_id = (SELECT MAX(blood_bags_id) FROM blood_bags WHERE patient_receivers_id = patient_receivers.patient_receivers_id)');
            })
                ->groupBy('patient_receivers.patient_receivers_id', 'patient_receivers.user_id', 'patient_receivers.first_name', 'patient_receivers.middle_name', 'patient_receivers.last_name', 'patient_receivers.sex', 'patient_receivers.dob', 'patient_receivers.blood_type', 'patient_receivers.diagnosis', 'patient_receivers.hospital', 'patient_receivers.payment', 'patient_receivers.status', 'patient_receivers.created_at', 'patient_receivers.updated_at')
                ->select('patient_receivers.*')
                ->where(function ($query) use ($searchInput) {
                    $query->where('patient_receivers.first_name', 'LIKE', '%' . $searchInput . '%')
                        ->orWhere('patient_receivers.middle_name', 'LIKE', '%' . $searchInput . '%')
                        ->orWhere('patient_receivers.last_name', 'LIKE', '%' . $searchInput . '%')
                        ->orWhere('patient_receivers.blood_type', 'LIKE', '%' . $searchInput . '%')
                        ->orWhere('patient_receivers.diagnosis', 'LIKE', '%' . $searchInput . '%')
                        ->orWhere('patient_receivers.hospital', 'LIKE', '%' . $searchInput . '%')
                        ->orWhere('patient_receivers.payment', 'LIKE', '%' . $searchInput . '%');
                        
                });

                $patient = $patientReceiver->orderBy('patient_receivers.patient_receivers_id', 'desc')->paginate(8);

           // Transform the results to include a list of blood bags for each user
           $patient->transform(function ($donor) {
            $bloodBags = BloodBag::where('patient_receivers_id', $donor->patient_receivers_id)
                ->select('serial_no', 'date_donated')
                ->orderBy('date_donated', 'asc')
                ->get();
        
            $donor->blood_bags = $bloodBags;
        
            return $donor;
        });

            if($patient->isEmpty()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'No donor found.'
                ], 200);
            }else{
                return response()->json([
                    'status' => 'success',
                    'data' => $patient
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

    public function exportPatientList(Request $request){
        $bloodType = $request->input('blood_type');
        $payment = $request->input('payment');
        $hospital = $request->input('hospital');
        $startDate = $request->input('startDate');
        $endDate = $request->input('endDate');

        $user = getAuthenticatedUserId();
        $userId = $user->user_id;

        $patientReceiver = PatientReceiver::leftJoin('blood_bags', function ($join) {
            $join->on('patient_receivers.patient_receivers_id', '=', 'blood_bags.patient_receivers_id')
                ->where('blood_bags.isUsed', 1)
                ->whereRaw('blood_bags.blood_bags_id = (SELECT MAX(blood_bags_id) FROM blood_bags WHERE patient_receivers_id = patient_receivers.patient_receivers_id)');
        })
            ->groupBy('patient_receivers.patient_receivers_id', 'patient_receivers.user_id', 'patient_receivers.first_name', 'patient_receivers.middle_name', 'patient_receivers.last_name', 'patient_receivers.sex', 'patient_receivers.dob', 'patient_receivers.blood_type', 'patient_receivers.diagnosis', 'patient_receivers.hospital', 'patient_receivers.payment', 'patient_receivers.status', 'patient_receivers.created_at', 'patient_receivers.updated_at')
            ->select('patient_receivers.*');
        
        if ($bloodType !== 'All') {
            $patientReceiver->where('patient_receivers.blood_type', $bloodType);
        }
        
        if ($payment !== 'All') {
            $patientReceiver->where('patient_receivers.payment', $payment);
        }
        
        if ($hospital !== 'All') {
            $patientReceiver->where('patient_receivers.hospital', $hospital);
        }

        if (!empty($startDate) && !empty($endDate)) {
            $patientReceiver->whereBetween('patient_receivers.created_at', [$startDate, $endDate]);
        } 
        
        $patient = $patientReceiver->orderBy('patient_receivers.patient_receivers_id', 'desc')->paginate(8);
        
        // Transform the results to include a list of blood bags for each user
        $patient->transform(function ($donor) {
            $bloodBags = BloodBag::where('patient_receivers_id', $donor->patient_receivers_id)
                ->select('serial_no', 'date_donated')
                ->orderBy('date_donated', 'asc')
                ->get();
        
            $donor->blood_bags = $bloodBags;
        
            return $donor;
        });

        //dd($patient);
        $totalCount = $patient->count();
       

        $ip = file_get_contents('https://api.ipify.org');
        $ch = curl_init('http://ipwho.is/'.$ip);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
    
        $ipwhois = json_decode(curl_exec($ch), true);
    
        curl_close($ch);

        AuditTrail::create([
            'user_id'    => $userId,
            'module'     => 'Dispensed Blood',
            'action'     => 'Export Patient List as PDF',
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
        $html = view('patientlist-details', ['patientList' => $patient, 'totalBloodBags' => $totalBloodBags, 'dateNow' => $formattedDate])->render();
        $pdf->loadHtml($html);
        $pdf->render();

        // Return the PDF as a response
        return response($pdf->output(), 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="donor-list.pdf"');
    }

}
