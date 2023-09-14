<?php

namespace App\Http\Controllers\Api\Admin\Inventory;

use App\Http\Controllers\Controller;
use App\Models\AuditTrail;
use Illuminate\Http\Request;

use App\Models\BloodBag;
use Illuminate\Validation\ValidationException;

class InventoryController extends Controller
{


    public function storedInInventory(Request $request){

        $user = getAuthenticatedUserId();
        $userId = $user->user_id;

        try {
                
            $validatedData = $request->validate([
                'user_id'       => 'required',
                'serial_no'     => 'required|numeric',
            ]);     

                $ch = curl_init('http://ipwho.is/' );
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HEADER, false);
            
                $ipwhois = json_decode(curl_exec($ch), true);
                curl_close($ch);

                $bloodBag = BloodBag::where( ['user_id', $validatedData['user_id']])
                    ->where('serial_no', $validatedData['serial_no'])
                    ->first();
                        
                dd($bloodBag);
                $bloodBag->update(['isStored' => 1]);

                AuditTrail::create([
                    'user_id'    => $userId,
                    'action'     => 'Move to inventory | serial no: ' . $validatedData['serial_no'],
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


        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->validator->errors(),
            ], 400);
        }

    }
    

    public function getInventory(){
        $expiration = 37;
    
        $inventory = BloodBag::where('isStored', 1)->get();
    
        $inventory->each(function ($bag) use ($expiration) {
            $daysRemaining = now()->diffInDays($bag->date_donated) - $expiration;
            $bag->remaining_days = $daysRemaining < 0 ? 0 : $daysRemaining;
        });
    
        return response()->json([
            'status' => 'success',
            'inventory' => $inventory
        ]);
    }

    public function moveToCollected(Request $request){
        $user = getAuthenticatedUserId();
        $userId = $user->user_id;

        try {
                
            $validatedData = $request->validate([
                'user_id'       => 'required',
                'serial_no'     => 'required|numeric',
            ]);

                $ch = curl_init('http://ipwho.is/' );
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HEADER, false);
            
                $ipwhois = json_decode(curl_exec($ch), true);
                curl_close($ch);

                $bloodBag = BloodBag::where( ['user_id', $validatedData['user_id']])->where('serial_no', $validatedData['serial_no'])->first();
                $bloodBag->update(['isStored' => 0]);

                AuditTrail::create([
                    'user_id'    => $userId,
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
                    'message'   => 'Blood bag stored in inventory',
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
}
