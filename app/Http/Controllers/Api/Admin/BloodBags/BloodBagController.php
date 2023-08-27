<?php

namespace App\Http\Controllers\Api\Admin\BloodBags;

use App\Http\Controllers\Controller;
use App\Models\BloodBag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class BloodBagController extends Controller
{
    
    public function store(Request $request)
    {
        $user = getAuthenticatedUserId();
       

            try {
                
                $validatedData = $request->validate([
                    'user_id'       => 'required',
                    'serial_no'     => 'required|numeric',
                    'date_donated'  => 'required|date',
                    'venue'         => 'required|string',
                    'bled_by'       => 'required|string',
                ]);               

                $lastRecord = BloodBag::where('user_id', $validatedData['user_id'])->latest('date_donated')->first();

                if ($lastRecord) {
                    $lastDonationDate = Carbon::parse($lastRecord->date_donated);
                    $currentDonationDate = Carbon::parse($validatedData['date_donated']);

                    if ($currentDonationDate->diffInMonths($lastDonationDate) < 3) {
                        return response()->json([
                            'status' => 'error',
                            'last_donated' => $lastRecord->date_donated,
                            'message' => 'The minimum donation interval is 3 months. Please wait until ' . $lastDonationDate->addMonths(3)->toDateString() . ' before donating again.',
                        ], 400);
                        
                    } else {

                        BloodBag::create([
                            'user_id' =>$validatedData['user_id'],
                            'serial_no' => $validatedData['serial_no'],
                            'date_donated' => $validatedData['date_donated'],
                            'venue' => $validatedData['venue'],
                            'bled_by' => $validatedData['bled_by'],
                        ]);
        
                        return response()->json([
                            'status' => 'success',
                            'message' => 'Blood bag added successfully',
                        ], 200);

                    }
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

