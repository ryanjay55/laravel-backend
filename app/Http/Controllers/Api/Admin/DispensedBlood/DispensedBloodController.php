<?php

namespace App\Http\Controllers\Api\Admin\DispensedBlood;

use App\Http\Controllers\Controller;
use App\Models\BloodBag;
use Illuminate\Http\Request;
use App\Models\PatientReceiver;;
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
        try {
            $bloodType = $request->input('blood_type');
            $hospital = $request->input('hospital');
            $payment = $request->input('payment');
            $startDate = $request->input('startDate');
            $endDate = $request->input('endDate');
    
            $dispensedList = PatientReceiver::join('blood_bags', 'patient_receivers.patient_receivers_id', '=', 'blood_bags.patient_receivers_id');
    
            if ($bloodType == 'All') {
                if ($startDate && $endDate) {
                    $dispensedList->whereBetween('created_at', [$startDate, $endDate]);
                }
                if ($hospital && $hospital != 'All') {
                    $dispensedList->where('hospital', $hospital);
                }
                if ($payment && $payment != 'All') {
                    $dispensedList->where('payment', $payment);
                }
                $totalCount = $dispensedList->count();
                $dispensedList = $dispensedList->orderBy('patient_receivers.created_at')->paginate(8);
            } else {
                $dispensedList->where('patient_receivers.blood_type', $bloodType);
                if ($startDate && $endDate) {
                    $dispensedList->whereBetween('created_at', [$startDate, $endDate]);
                }
                if ($hospital && $hospital != 'All') {
                    $dispensedList->where('hospital', $hospital);
                }
                if ($payment && $payment != 'All') {
                    $dispensedList->where('payment', $payment);
                }
                $totalCount = $dispensedList->count();
                $dispensedList = $dispensedList->orderBy('patient_receivers.created_at')->paginate(8);
            }
    
            return response()->json([
                'status' => 'success',
                'data' => $dispensedList,
                'total_count' => $totalCount
            ]);
    
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->validator->errors(),
            ], 400);
        }
    }

}
