<?php

namespace App\Http\Controllers\Api\Admin\Inventory;

use App\Http\Controllers\Controller;
use App\Models\AuditTrail;
use Illuminate\Http\Request;

use App\Models\BloodBag;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class InventoryController extends Controller
{


    public function storedInInventory(Request $request){

        $user = getAuthenticatedUserId();
        $userId = $user->user_id;
        
        try {
                
            $validatedData = $request->validate([
                'serial_no'     => 'required',
            ]);     
                
                $ch = curl_init('http://ipwho.is/' );
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
    

    public function getStocks()
    {
        $inventory = BloodBag::join('user_details', 'blood_bags.user_id', '=', 'user_details.user_id')
            ->where('blood_bags.isStored', 1)
            ->where('blood_bags.isExpired', 0)
            ->where('user_details.remarks', 0)
            ->select('blood_bags.blood_bags_id','blood_bags.serial_no','user_details.first_name', 'user_details.last_name','user_details.blood_type','user_details.donor_no','blood_bags.date_donated', 'blood_bags.expiration_date')
            ->orderBy('blood_bags.expiration_date','desc') 
            ->paginate(8);

        if($inventory->isEmpty()){
            return response()->json([
                'status' => 'error',
                'message' => 'No blood bag in inventory',
            ]);
        }else{
            
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
            
                if ($expirationDate->lte($today) || $remainingDays == 0) { // Use $remainingDays instead of $bloodBag->remaining_days
                    $bloodBag->isExpired = 1;
                } else {
                    $bloodBag->isExpired = 0;
                }
            
                $bloodBag->save();
                return $bloodBag;
            });
    
            
            return response()->json([
                'status' => 'success',
                'data' => $inventory
            ]);
        }

    }

    public function moveToCollected(Request $request){
        $user = getAuthenticatedUserId();
        $userId = $user->user_id;

        try {
                
            $validatedData = $request->validate([
                'serial_no'     => 'required',
            ]);

                $ch = curl_init('http://ipwho.is/' );
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
                return response()->json([
                    'status' => 'success',
                    'data' => $expiredBlood
                ]);
            }
            
    }

    public function getTempDeferralBloodBag(){
        $tempExpiredBlood = BloodBag::join('user_details', 'blood_bags.user_id', '=', 'user_details.user_id')
            ->where('blood_bags.isExpired', 0)
            ->where('blood_bags.isDisposed', 0)
            ->where('user_details.remarks', 1)
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

    public function disposeBlood(Request $request){

        $user = getAuthenticatedUserId();
        $userId = $user->user_id;

        try {
                
            $validatedData = $request->validate([
                'serial_no'     => 'required',
            ]);

                $ch = curl_init('http://ipwho.is/' );
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HEADER, false);
            
                $ipwhois = json_decode(curl_exec($ch), true);
                curl_close($ch);

                $bloodBag = BloodBag::where('serial_no', $validatedData['serial_no'])->first();

                if(empty($bloodBag)) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Blood bag not found',
                    ], 400);
                    
                }else{

                    $bloodBag->update(['isDisposed' => 1]);

                    AuditTrail::create([
                        'user_id'    => $userId,
                        'module'     => 'Inventory',
                        'action'     => 'Disposed blood bag | serial no: ' . $validatedData['serial_no'],
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
                        'message'   => 'Blood bag successfully disposed',
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


}
