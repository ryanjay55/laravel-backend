<?php

namespace App\Http\Controllers\Api\Donor\Profile;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserDetail;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ProfileController extends Controller
{
    public function updateProfile(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'user_id'               => ['required', 'exists:users,user_id'], 
                'first_name'            => ['required', 'string'],
                'middle_name'           => ['nullable', 'string'],
                'last_name'             => ['required', 'string'],
                'mobile'                => ['required', 'string'],
                'sex'                   => ['required'],
                'blood_type'            => ['required'],//
                'occupation'            => ['required', 'string'],
                'street'                => ['required', 'string'],
                'region'                => ['required'],
                'province'              => ['required'],
                'municipality'          => ['required'],
                'barangay'              => ['required' ],
                'postalcode'            => ['required', 'integer'],
            ]);
        
                $userDetails = UserDetail::where('user_id', $validatedData['user_id'])->first();
                $userDetails->first_name = $validatedData['first_name'];
                $userDetails->middle_name = $validatedData['middle_name'];
                $userDetails->last_name = $validatedData['last_name'];
                $userDetails->sex = $validatedData['sex'];
                $userDetails->blood_type = $validatedData['blood_type'];
                $userDetails->occupation = $validatedData['occupation'];
                $userDetails->street = $validatedData['street'];
                $userDetails->region = $validatedData['region'];
                $userDetails->province = $validatedData['province'];
                $userDetails->municipality = $validatedData['municipality'];
                $userDetails->barangay = $validatedData['barangay'];
                $userDetails->postalcode = $validatedData['postalcode'];
                $userDetails->update();

                $userAuthDetails = User::where('user_id', $validatedData['user_id'])->first();
                $userAuthDetails->mobile = $validatedData['mobile'];
                $userAuthDetails->update();
           
                return response()->json([
                    'status'    => 'success',
                    'message'   => 'Profile updated',
                ]);
        
        } catch (ValidationException $e) {

            return response()->json([
                'status'        => 'error',
                'errors'        => $e->validator->errors(),
            ], 422);


        } catch (QueryException $e) {

            return response()->json([
                'status'    => 'error',
                'message'   => 'Database error',
                'errors'    => $e->getMessage(),
            ], 500);
            
        }
    }


}
