<?php

namespace App\Http\Controllers\Api\Admin\BloodBags;

use App\Http\Controllers\Controller;
use App\Models\BloodBag;
use App\Models\AuditTrail;
use App\Models\Galloner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class BloodBagController extends Controller
{
    
    public function store(Request $request)
    {
        $user = getAuthenticatedUserId();
        $userId = $user->user_id;
            try {
                
                $validatedData = $request->validate([
                    'user_id'       => 'required',
                    'serial_no'     => 'required|numeric',
                    'date_donated'  => 'required|date',
                    'venue'         => 'required|string',
                    'bled_by'       => 'required|string',
                ]);               

                 $ch = curl_init('http://ipwho.is/' );
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HEADER, false);
            
                $ipwhois = json_decode(curl_exec($ch), true);
            
                curl_close($ch);

                $lastRecord = BloodBag::where('user_id', $validatedData['user_id'])->latest('date_donated')->first();

                if ($lastRecord) {
                    $lastDonationDate = Carbon::parse($lastRecord->date_donated);
                    $currentDonationDate = Carbon::parse($validatedData['date_donated']);
                    $minDonationInterval = clone $lastDonationDate;
                    $minDonationInterval->addMonths(3);
                
                    if ($currentDonationDate <= $lastDonationDate) {
                        AuditTrail::create([
                            'user_id'    => $userId,
                            'action'     => 'Add Blood Bag | serial no: ' . $validatedData['serial_no'],
                            'status'     => 'failed',
                            'ip_address' => $ipwhois['ip'],
                            'region'     => $ipwhois['region'],
                            'city'       => $ipwhois['city'],
                            'postal'     => $ipwhois['postal'],
                            'latitude'   => $ipwhois['latitude'],
                            'longitude'  => $ipwhois['longitude'],
                        ]);
                
                        return response()->json([
                            'status'       => 'error',
                            'last_donated' => $lastRecord->date_donated,
                            'message'      => 'The minimum donation interval is 3 months. Please wait until ' . $minDonationInterval->toDateString() . ' before donating again.',
                        ], 400);

                    } elseif ($currentDonationDate < $minDonationInterval) {

                        AuditTrail::create([
                            'user_id'    => $userId,
                            'action'     => 'Add Blood Bag | serial no: ' . $validatedData['serial_no'],
                            'status'     => 'failed',
                            'ip_address' => $ipwhois['ip'],
                            'region'     => $ipwhois['region'],
                            'city'       => $ipwhois['city'],
                            'postal'     => $ipwhois['postal'],
                            'latitude'   => $ipwhois['latitude'],
                            'longitude'  => $ipwhois['longitude'],
                        ]);

                        return response()->json([
                            'status'       => 'error',
                            'last_donated' => $lastRecord->date_donated,
                            'message'      => 'The minimum donation interval is 3 months. Please wait until ' . $minDonationInterval->toDateString() . ' before donating again.',
                        ], 400);

                    }else{

                        BloodBag::create([
                            'user_id'      => $validatedData['user_id'],
                            'serial_no'    => $validatedData['serial_no'],
                            'date_donated' => $validatedData['date_donated'],
                            'venue'        => $validatedData['venue'],
                            'bled_by'      => $validatedData['bled_by'],
                        ]);
                    
                        AuditTrail::create([
                            'user_id'    => $userId,
                            'action'     => 'Add Blood Bag | serial no: ' . $validatedData['serial_no'],
                            'status'     => 'success',
                            'ip_address' => $ipwhois['ip'],
                            'region'     => $ipwhois['region'],
                            'city'       => $ipwhois['city'],
                            'postal'     => $ipwhois['postal'],
                            'latitude'   => $ipwhois['latitude'],
                            'longitude'  => $ipwhois['longitude'],
                        ]);

                        $galloner = Galloner::where('user_id', $validatedData['user_id'])->first();
                        $galloner->donate_qty += 1;
                        $galloner->save();
                        
                        if($galloner->donate_qty == 4){
                            $galloner->badge = 'bronze';
                            $galloner->save();
                        }elseif($galloner->donate_qty == 8){
                            $galloner->badge = 'silver';
                            $galloner->save();
                        }elseif($galloner->donate_qty == 12){
                            $galloner->badge = 'gold';
                            $galloner->save();
                        }


                        return response()->json([
                            'status'  => 'success',
                            'message' => 'Blood bag added successfully',
                        ], 200);
                    }
                    

                } else {

                    BloodBag::create([
                        'user_id'      => $validatedData['user_id'],
                        'serial_no'    => $validatedData['serial_no'],
                        'date_donated' => $validatedData['date_donated'],
                        'venue'        => $validatedData['venue'],
                        'bled_by'      => $validatedData['bled_by'],
                    ]);
                
                    AuditTrail::create([
                        'user_id'    => $userId,
                        'action'     => 'Add Blood Bag | serial no: ' . $validatedData['serial_no'],
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
                        'message' => 'Blood bag added successfully',
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
    

}

