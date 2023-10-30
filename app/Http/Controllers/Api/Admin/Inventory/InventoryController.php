<?php

namespace App\Http\Controllers\Api\Admin\Inventory;

use App\Http\Controllers\Controller;
use App\Models\AuditTrail;
use App\Models\Deferral;
use App\Models\Hospital;
use App\Models\PatientReceiver;
use App\Models\UserDetail;
use Illuminate\Http\Request;

use App\Models\BloodBag;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventoryController extends Controller
{


    public function storedInInventory(Request $request)
    {

        $user = getAuthenticatedUserId();
        $userId = $user->user_id;
        
        try {
                
            $validatedData = $request->validate([
                'serial_no'     => 'required',
            ]);     
                
                $ip = file_get_contents('https://api.ipify.org');
                $ch = curl_init('http://ipwho.is/'.$ip);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HEADER, false);
            
                $ipwhois = json_decode(curl_exec($ch), true);
                curl_close($ch);

                $bloodBag = BloodBag::where('serial_no', $validatedData['serial_no'])->first();

                $today = Carbon::today();
                $expirationDate = Carbon::parse($bloodBag->expiration_date);

                if ($expirationDate->lte($today)) {

                    return response()->json([
                        'status' => 'error',
                        'message' => 'Cannot add to inventory because this blood bag is already expired',
                    ], 400);

                } else {
                    
                    $bloodBag->update(['isStored' => 1]);
                    $bloodBag->update(['isTested' => 1]);


                    AuditTrail::create([
                        'user_id'    => $userId,
                        'module'     => 'Collected Blood Bags',
                        'action'     => 'Move to stocks | serial no: ' . $validatedData['serial_no'],
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
                        'message'   => 'Blood bag stored in inventory',
                        'blood_bag' => $bloodBag
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

    public function multipleMoveToInventory(Request $request)
    {
        $user = getAuthenticatedUserId();
        $userId = $user->user_id;

        try {
            $validatedData = $request->validate([
                'blood_bags_id' => 'required|array',
            ]);
    
            
            $ip = file_get_contents('https://api.ipify.org');
            $ch = curl_init('http://ipwho.is/'.$ip);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HEADER, false);
    
            $ipwhois = json_decode(curl_exec($ch), true);
            curl_close($ch);
    
            foreach ($validatedData['blood_bags_id'] as $serialNo) {
                $bloodBag = BloodBag::where('blood_bags_id', $serialNo)->first();
    
                if (empty($bloodBag)) {
                    return response()->json([
                        'status'  => 'error',
                        'message' => 'Blood bag not found',
                    ], 400);
                } else {
                    $bloodBag->update(['isStored' => 1]);
    
                    AuditTrail::create([
                        'user_id'    => $userId,
                        'module'     => 'Inventory',
                        'action'     => 'move blood bag to stocks | blood bag ID: ' . $serialNo,
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
    
            return response()->json([
                'status'    => 'success',
                'message'   => 'Blood bags successfully move to stocks',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Validation failed',
                'errors'  => $e->validator->errors(),
            ], 400);
        }
    }
    

    public function getStocks()
    {
        $now = Carbon::now();

        $deferralsToUpdate = Deferral::where('end_date', '<=', $now)
        ->where('status', '!=', 1)
        ->get();

        foreach ($deferralsToUpdate as $deferral) {
            $deferral->status = 1;
            $deferral->save();

            $user_detail = UserDetail::where('user_id', $deferral->user_id)->first();
            if ($user_detail) {
                $user_detail->remarks = 0;
                $user_detail->save();
            }
        }
        
        $inventory = BloodBag::join('user_details', 'blood_bags.user_id', '=', 'user_details.user_id')
            ->where('blood_bags.isStored', 1)
            ->where('blood_bags.isExpired', 0)
            ->where('user_details.remarks', 0)
            ->where('blood_bags.isDisposed', 0)
            ->where('blood_bags.isUsed', 0)
            ->select('blood_bags.blood_bags_id', 'blood_bags.serial_no', 'user_details.first_name', 'user_details.last_name', 'user_details.blood_type', 'user_details.donor_no', 'blood_bags.date_donated', 'blood_bags.expiration_date')
            ->orderBy('blood_bags.expiration_date')
            ->paginate(8);

        if ($inventory->isEmpty()) {
            return response()->json([
                'status' => 'error',
                'message' => 'No blood bag in inventory',
                'total_count' => 0
            ]);
        } else {
            $inventory->each(function ($bloodBag) {
                $today = Carbon::today();
                $dateDonated = Carbon::parse($bloodBag->date_donated);
                $expirationDate = Carbon::parse($bloodBag->expiration_date);
                $remainingDays = $expirationDate->diffInDays($today);
                $bloodBag->remaining_days = $remainingDays;
                $bloodBag->save();

                if ($remainingDays <= 7) {
                    $bloodBag->priority = 'High Priority';
                } elseif ($remainingDays <= 14) {
                    $bloodBag->priority = 'Medium Priority';
                } else {
                    $bloodBag->priority = 'Low Priority';
                }

                if ($expirationDate->lte($today) || $remainingDays == 0) {
                    $bloodBag->isExpired = 1;
                } else {
                    $bloodBag->isExpired = 0;
                }

                $bloodBag->save();
                return $bloodBag;
            });

            $totalCount = BloodBag::join('user_details', 'blood_bags.user_id', '=', 'user_details.user_id')
                ->where('blood_bags.isStored', 1)
                ->where('blood_bags.isExpired', 0)
                ->where('user_details.remarks', 0)
                ->count();

            return response()->json([
                'status' => 'success',
                'data' => $inventory,
                'total_count' => $totalCount
            ]);
        }
    }
    
    public function searchStocks(Request $request)
    {
        try {
            $request->validate([
                'searchInput' => 'required',
            ]);

            $searchInput = str_replace(' ', '', $request->input('searchInput'));

            $stocks = BloodBag::join('user_details', 'blood_bags.user_id', '=', 'user_details.user_id')
                ->where('blood_bags.isStored', 1)
                ->where('blood_bags.isExpired', 0)
                ->where('user_details.remarks', 0)
                ->where('blood_bags.isDisposed', 0)
                ->where(function ($query) use ($searchInput) {
                    $query->where('blood_bags.serial_no', 'LIKE', '%' . $searchInput . '%')
                        ->orWhere('user_details.first_name', 'LIKE', '%' . $searchInput . '%')
                        ->orWhere('user_details.last_name', 'LIKE', '%' . $searchInput . '%')
                        ->orWhere('user_details.blood_type', 'LIKE', '%' . $searchInput . '%')
                        ->orWhere('user_details.donor_no', 'LIKE', '%' . $searchInput . '%')
                        ->orWhere('blood_bags.date_donated', 'LIKE', '%' . $searchInput . '%')
                        ->orWhere('blood_bags.expiration_date', 'LIKE', '%' . $searchInput . '%');
                })
                ->select('blood_bags.blood_bags_id', 'blood_bags.serial_no', 'user_details.first_name', 'user_details.last_name', 'user_details.blood_type', 'user_details.donor_no', 'blood_bags.date_donated', 'blood_bags.expiration_date')
                ->paginate(8);
            dd($stocks);

            if ($stocks->isEmpty()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'No stocks found.'
                ], 200);
            } else {
                return response()->json([
                    'status' => 'success',
                    'data' => $stocks
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
   

    public function filterBloodTypeStocks(Request $request)
    {
        try {
            $bloodType = $request->input('blood_type');
            $startDate = $request->input('startDate');
            $endDate = $request->input('endDate');
    
            $inventory = BloodBag::join('user_details', 'blood_bags.user_id', '=', 'user_details.user_id')
                ->where('blood_bags.isStored', 1)
                ->where('blood_bags.isExpired', 0)
                ->where('user_details.remarks', 0)
                ->where('blood_bags.isDisposed', 0)
                ->select('blood_bags.blood_bags_id', 'blood_bags.serial_no','blood_bags.priority' ,'blood_bags.remaining_days', 'user_details.first_name', 'user_details.last_name', 'user_details.blood_type', 'user_details.donor_no', 'blood_bags.date_donated', 'blood_bags.expiration_date');
    
            if ($bloodType == 'All') {
                if ($startDate && $endDate) {
                    $inventory->whereBetween('blood_bags.expiration_date', [$startDate, $endDate]);
                }
                $totalCount = $inventory->count();
                $inventory = $inventory->orderBy('blood_bags.expiration_date')->paginate(8);
            } else {
                $inventory->where('user_details.blood_type', $bloodType);
                if ($startDate && $endDate) {
                    $inventory->whereBetween('blood_bags.expiration_date', [$startDate, $endDate]);
                }
                $totalCount = $inventory->count();
                $inventory = $inventory->orderBy('blood_bags.expiration_date')->paginate(8);
            }
    
            return response()->json([
                'status' => 'success',
                'data' => $inventory,
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

    public function filterBloodTypeExp(Request $request)
    {
        try {
            $bloodType = $request->input('blood_type');
            $startDate = $request->input('startDate');
            $endDate = $request->input('endDate');
    
            $inventory = BloodBag::join('user_details', 'blood_bags.user_id', '=', 'user_details.user_id')
                ->where('blood_bags.isExpired', 1)
                ->where('user_details.remarks', 0)
                ->where('blood_bags.isDisposed', 0)
                ->select('blood_bags.blood_bags_id', 'blood_bags.serial_no','blood_bags.priority' ,'blood_bags.remaining_days', 'user_details.first_name', 'user_details.last_name', 'user_details.blood_type', 'user_details.donor_no', 'blood_bags.date_donated', 'blood_bags.expiration_date');
    
            if ($bloodType == 'All') {
                if ($startDate && $endDate) {
                    $inventory->whereBetween('blood_bags.expiration_date', [$startDate, $endDate]);
                }
                $totalCount = $inventory->count();
                $inventory = $inventory->orderBy('blood_bags.expiration_date')->paginate(8);
            } else {
                $inventory->where('user_details.blood_type', $bloodType);
                if ($startDate && $endDate) {
                    $inventory->whereBetween('blood_bags.expiration_date', [$startDate, $endDate]);
                }
                $totalCount = $inventory->count();
                $inventory = $inventory->orderBy('blood_bags.expiration_date')->paginate(8);
            }
    
            return response()->json([
                'status' => 'success',
                'data' => $inventory,
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
   
    public function moveToCollected(Request $request){
        $user = getAuthenticatedUserId();
        $userId = $user->user_id;

        try {
                
            $validatedData = $request->validate([
                'serial_no'     => 'required',
            ]);

                $ip = file_get_contents('https://api.ipify.org');
                $ch = curl_init('http://ipwho.is/'.$ip);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HEADER, false);
            
                $ipwhois = json_decode(curl_exec($ch), true);
                curl_close($ch);

                $bloodBag = BloodBag::where('serial_no', $validatedData['serial_no'])->first();
                $bloodBag->update(['isStored' => 0]);

                AuditTrail::create([
                    'user_id'    => $userId,
                    'module'     => 'Inventory',
                    'action'     => 'Move to collected blood bags | serial no: ' . $validatedData['serial_no'],
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
                    'message'   => 'Blood bag moved to collected',
                    'blood_bag' => $bloodBag
                ]);


        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->validator->errors(),
            ], 400);
        }
    }


    public function expiredBlood(){
        $expiredBlood = BloodBag::join('user_details', 'blood_bags.user_id', '=', 'user_details.user_id')
            ->where('blood_bags.isExpired', 1)
            ->where('blood_bags.isDisposed', 0)
            ->where('user_details.remarks', 0)
            ->select('blood_bags.blood_bags_id','blood_bags.serial_no','user_details.donor_no','user_details.blood_type','user_details.first_name', 'user_details.last_name','blood_bags.date_donated', 'blood_bags.expiration_date')
            ->paginate(8);

            if($expiredBlood->isEmpty()){
                return response()->json([
                    'status' => 'success',
                    'message' => 'No expired blood bag',
                ]);
            }else{

                $totalCount = BloodBag::join('user_details', 'blood_bags.user_id', '=', 'user_details.user_id')
                ->where('blood_bags.isDisposed', 0)
                ->where('blood_bags.isExpired', 1)
                ->where('user_details.remarks', 0)
                ->count();

                return response()->json([
                    'status' => 'success',
                    'data' => $expiredBlood,
                    'total_count' => $totalCount
                ]);
            }
            
    }

    public function getTempDeferralBloodBag(){
        
        $tempExpiredBlood = BloodBag::join('user_details', 'blood_bags.user_id', '=', 'user_details.user_id')
            ->join('deferrals', 'deferrals.user_id', '=', 'user_details.user_id')
            ->where('deferrals.remarks_id', 1)
            ->where('blood_bags.separate', 1)
            // ->where('deferrals.status', 1)
            ->where('blood_bags.isCollected', 1)
            ->where('blood_bags.isExpired', 0)
            ->where('blood_bags.isDisposed', 0)
            ->select(
                'blood_bags.blood_bags_id',
                'blood_bags.serial_no',
                'user_details.donor_no',
                'user_details.blood_type',
                'user_details.first_name',
                'user_details.last_name',
                'blood_bags.date_donated',
                'blood_bags.expiration_date'
            )
            ->distinct()
            ->paginate(8);
    
    
            if($tempExpiredBlood->isEmpty()){
                return response()->json([
                    'status' => 'success',
                    'message' => 'deferral blood bag',
                ]);
            }else{
    
                $totalCount = DB::table('blood_bags')
                ->join('user_details', 'blood_bags.user_id', '=', 'user_details.user_id')
                ->join('deferrals', 'deferrals.user_id', '=', 'user_details.user_id')
                ->where('deferrals.remarks_id', '=', 1)
                ->where('deferrals.status', '=', 1)
                ->where('blood_bags.isExpired', '=', 0)
                ->where('blood_bags.isDisposed', '=', 0)
                ->selectRaw('COUNT(DISTINCT blood_bags.blood_bags_id) as total_count')
                ->first();
    
                return response()->json([
                    'status' => 'success',
                    'data' => $tempExpiredBlood,
                    'total_count' => $totalCount->total_count
                ]);
            }
            
    }

    public function filterBloodTypeTempDeferral(Request $request)
    {
        try {
            $bloodType = $request->input('blood_type');
            $startDate = $request->input('startDate');
            $endDate = $request->input('endDate');
    
            $inventory = BloodBag::join('user_details', 'blood_bags.user_id', '=', 'user_details.user_id')
                ->where('blood_bags.isExpired', 0)
                ->where('blood_bags.isDisposed', 0)
                ->where('user_details.remarks', 1)
                ->select('blood_bags.blood_bags_id', 'blood_bags.serial_no','blood_bags.priority' ,'blood_bags.remaining_days', 'user_details.first_name', 'user_details.last_name', 'user_details.blood_type', 'user_details.donor_no', 'blood_bags.date_donated', 'blood_bags.expiration_date');
    
            if ($bloodType == 'All') {
                if ($startDate && $endDate) {
                    $inventory->whereBetween('blood_bags.expiration_date', [$startDate, $endDate]);
                }
                $totalCount = $inventory->count();
                $inventory = $inventory->orderBy('blood_bags.expiration_date')->paginate(8);
            } else {
                $inventory->where('user_details.blood_type', $bloodType);
                if ($startDate && $endDate) {
                    $inventory->whereBetween('blood_bags.expiration_date', [$startDate, $endDate]);
                }
                $totalCount = $inventory->count();
                $inventory = $inventory->orderBy('blood_bags.expiration_date')->paginate(8);
            }
    
            return response()->json([
                'status' => 'success',
                'data' => $inventory,
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

    public function getPermaDeferralBloodBag(){
        $tempExpiredBlood = BloodBag::join('user_details', 'blood_bags.user_id', '=', 'user_details.user_id')
            ->where('blood_bags.isExpired', 0)
            ->where('blood_bags.isDisposed', 0)
            ->where('user_details.remarks', 2)
            ->select('blood_bags.blood_bags_id','blood_bags.serial_no','user_details.donor_no','user_details.blood_type','user_details.first_name', 'user_details.last_name','blood_bags.date_donated', 'blood_bags.expiration_date')
            ->paginate(8);

            if($tempExpiredBlood->isEmpty()){
                return response()->json([
                    'status' => 'success',
                    'message' => 'deferral blood bag',
                ]);
            }else{
                return response()->json([
                    'status' => 'success',
                    'data' => $tempExpiredBlood
                ]);
            }
            
    }

    public function filterBloodTypePermaDeferral(Request $request)
    {
        try {
            $bloodType = $request->input('blood_type');
            $startDate = $request->input('startDate');
            $endDate = $request->input('endDate');
    
            $inventory = BloodBag::join('user_details', 'blood_bags.user_id', '=', 'user_details.user_id')
                ->where('blood_bags.isExpired', 0)
                ->where('blood_bags.isDisposed', 0)
                ->where('user_details.remarks', 2)
                ->select('blood_bags.blood_bags_id', 'blood_bags.serial_no','blood_bags.priority' ,'blood_bags.remaining_days', 'user_details.first_name', 'user_details.last_name', 'user_details.blood_type', 'user_details.donor_no', 'blood_bags.date_donated', 'blood_bags.expiration_date');
    
            if ($bloodType == 'All') {
                if ($startDate && $endDate) {
                    $inventory->whereBetween('blood_bags.expiration_date', [$startDate, $endDate]);
                }
                $totalCount = $inventory->count();
                $inventory = $inventory->orderBy('blood_bags.expiration_date')->paginate(8);
            } else {
                $inventory->where('user_details.blood_type', $bloodType);
                if ($startDate && $endDate) {
                    $inventory->whereBetween('blood_bags.expiration_date', [$startDate, $endDate]);
                }
                $totalCount = $inventory->count();
                $inventory = $inventory->orderBy('blood_bags.expiration_date')->paginate(8);
            }
    
            return response()->json([
                'status' => 'success',
                'data' => $inventory,
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

   public function disposeBlood(Request $request)
   {
       $user = getAuthenticatedUserId();
       $userId = $user->user_id;
   
       try {
           $validatedData = $request->validate([
               'blood_bags_id' => 'required|array',
           ]);
   
           $ip = file_get_contents('https://api.ipify.org');
           $ch = curl_init('http://ipwho.is/'.$ip);
           curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
           curl_setopt($ch, CURLOPT_HEADER, false);
   
           $ipwhois = json_decode(curl_exec($ch), true);
           curl_close($ch);
   
           foreach ($validatedData['blood_bags_id'] as $bloodBagId) {
               $bloodBag = BloodBag::where('blood_bags_id', $bloodBagId)->first();
   
               if (empty($bloodBag)) {
                   return response()->json([
                       'status'  => 'error',
                       'message' => 'Blood bag not found',
                   ], 400);
               } else {
                   $bloodBag->update(['isDisposed' => 1]);
   
                   AuditTrail::create([
                       'user_id'    => $userId,
                       'module'     => 'Inventory',
                       'action'     => 'Disposed blood bag | blood bag ID: ' . $bloodBagId,
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
   
           return response()->json([
               'status'    => 'success',
               'message'   => 'Blood bags successfully disposed',
           ]);
       } catch (ValidationException $e) {
           return response()->json([
               'status'  => 'error',
               'message' => 'Validation failed',
               'errors'  => $e->validator->errors(),
           ], 400);
       }
   }


   public function dispensedBlood(Request $request)
   {
       $user = getAuthenticatedUserId();
       $userId = $user->user_id;
   
       try {
           $validatedData = $request->validate([
               'blood_bags_id' => 'required|array',
               'first_name' => 'required',
               'middle_name' => 'required',
               'last_name' => 'required',
               'dob' => 'required',
               'sex' => 'required',
               'diagnosis' => 'required',
               'blood_type' => 'required',
               'hospital' => 'required',
               'payment'   => 'required'
           ]);
           
           $user_id = $request->user_id;
           $first_name = $validatedData['first_name'];
           $middle_name = $validatedData['middle_name'];
           $last_name = $validatedData['last_name'];
           $dob = $validatedData['dob'];
           $blood_type = $validatedData['blood_type'];
           $hospital = $validatedData['hospital'];
           $payment = $validatedData['payment'];
           $sex = $validatedData['sex'];
           $diagnosis = $validatedData['diagnosis'];

           $ip = file_get_contents('https://api.ipify.org');
           $ch = curl_init('http://ipwho.is/'.$ip);
           curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
           curl_setopt($ch, CURLOPT_HEADER, false);
   
           $ipwhois = json_decode(curl_exec($ch), true);
           curl_close($ch);

           $patientReceiverId = '';
           if ($user_id == null) {
               $patientReceiver = PatientReceiver::create([
                   'first_name' => $first_name,
                   'middle_name' => $middle_name,
                   'last_name' => $last_name,
                   'dob' => $dob,
                   'sex' => $sex,
                   'blood_type' => $blood_type,
                   'diagnosis' => $diagnosis,
                   'hospital' => $hospital,
                   'payment' => $payment,
               ]);
               $patientReceiverId = PatientReceiver::latest()->value('patient_receivers_id');
           } else {
               $patientReceiver = PatientReceiver::create([
                   'user_id' => $user_id,
                   'first_name' => $first_name,
                   'middle_name' => $middle_name,
                   'last_name' => $last_name,
                   'dob' => $dob,
                   'sex' => $sex,
                   'blood_type' => $blood_type,
                   'diagnosis' => $user_id,
                   'hospital' => $hospital,
                   'payment' => $payment,
               ]);

               $patientReceiverId = PatientReceiver::latest()->value('patient_receivers_id');
           }
           
      
           foreach ($validatedData['blood_bags_id'] as $bloodBagId) {
            $bloodBag = BloodBag::where('blood_bags_id', $bloodBagId)->first();
            $bloodBag->patient_receivers_id =  $patientReceiverId;
            $bloodBag->isUsed = 1;
            $bloodBag->dispensed_date = now();
            $bloodBag->save();

            if (empty($bloodBag)) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Blood bag not found',
                ], 400);
            } else {

                 
                AuditTrail::create([
                    'user_id'    => $userId,
                    'module'     => 'Inventory',
                    'action'     => 'Dipensed blood bag | blood bag ID: ' . $bloodBagId,
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

            
           return response()->json([
               'status'    => 'success',
               'message'   => 'Blood bags successfully dispensed',
           ]);
       } catch (ValidationException $e) {
           return response()->json([
               'status'  => 'error',
               'message' => 'Validation failed',
               'errors'  => $e->validator->errors(),
           ], 400);
       }
   }

   public function getRegisteredUsers(){
        $userDetails = UserDetail::join('users', 'user_details.user_id', '=', 'users.user_id')
            ->join('galloners', 'user_details.user_id', '=', 'galloners.user_id')
            ->where('user_details.status', 0)
            ->where('users.isAdmin', 0)
            ->select('users.mobile', 'users.email', 'user_details.*', 'galloners.badge', 'galloners.donate_qty')
            ->orderBy('user_details.user_id', 'desc')
            ->get();

        return response()->json([
            'status'    => 'success',
            'userDetails'   => $userDetails,
        ]);
   }

    public function getHospitals(){
        $hospitals = app(Hospital::class)->getAllHospital();

        return response()->json([
            'status'    => 'success',
            'hospitals'   => $hospitals,
        ]);
    }
  
    
}

