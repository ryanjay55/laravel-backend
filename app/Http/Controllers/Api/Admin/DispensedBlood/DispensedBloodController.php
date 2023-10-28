<?php

namespace App\Http\Controllers\Api\Admin\DispensedBlood;

use App\Http\Controllers\Controller;
use App\Models\BloodBag;
use Illuminate\Http\Request;
use App\Models\PatientReceiver;;

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

}
