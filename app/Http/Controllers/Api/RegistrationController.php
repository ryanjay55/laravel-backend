<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Galloner;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use App\Models\UserDetail;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Mail; 
use App\Mail\RegistrationMail;

class RegistrationController extends Controller
{

    public function saveStep1(Request $request)
    {
        try {

            $validatedData = $request->validate([
                'email'     => ['required', 'string', 'email', 'max:255','unique:users'],
                'mobile'    => ['required', 'numeric', 'digits:11','unique:users'],
                'password'  => ['required','confirmed', 'min:6']
            ],[
                'mobile.digits' => 'Invalid mobile number',
            ]);
            
            $user_info = User::where('email', $validatedData['email'])->orWhere('mobile', $validatedData['mobile'])->first();
            
            if(empty($user_info)){

                $user_info = User::updateOrCreate([
                    'email'         => $request->email,
                    'mobile'        => $request->mobile,
                    'password'      => Hash::make($request->password),
                ]);

                $status = 'success';
                $next_step = 2;
                $message = 'User added';

                }else{
                

                    $user_details_info = UserDetail::where('user_id', $user_info->user_id)->first();

                    if(!empty($user_details_info)){

                        $status = 'error';
                        $next_step = 0;
                        $message = 'Already registered. Please login instead.';

                    }else{

                        $status = 'success';
                        $next_step = 2;
                        $message = 'You are in step 2';
                    }

                }

            return response()->json([
                'status'        => $status,
                'next_step'     => $next_step,
                'user_id'       => $user_info->user_id, 
                'mobile'        => $user_info->mobile,
                'email'         => $user_info->email,
                'message'       => $message
            ], 200);

        } catch (ValidationException $e) {
            return response()->json([
                'status'    => 'error',
                'message'   => 'Validation failed',
                'errors'    => $e->validator->errors(),
            ], 400);
        }

    }


    public function saveStep2(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'user_id'               => ['required', 'exists:users,user_id'], 
                'first_name'            => ['required', 'string'],
                'middle_name'           => ['nullable', 'string'],
                'last_name'             => ['required', 'string'],
                'dob'                   => ['required', 'date', 'before_or_equal:' . now()->subYears(16)->format('Y-m-d')],                
                'sex'                   => ['required'],
                'blood_type'            => ['required'],
                'occupation'            => ['required', 'string'],
                'street'                => ['required', 'string'],
                'region'                => ['required'],
                'province'              => ['required'],
                'municipality'          => ['required'],
                'barangay'              => ['required' ],
                'postalcode'            => ['required', 'integer'],
            ],[
                'user_id.required'  => 'Invalid. Proceed to step 1',
                'user_id.exists'    => 'Invalid. Proceed to step 1',
                'before_or_equal'   => 'You must at least 17 years old to register',
            ]);
        
            $user_id = $validatedData['user_id'];
            $user = User::with('userDetails')->find($user_id);
            if (!$user) {
                return response()->json([
                    'status'    => 'error',
                    'message'   => 'Proceed to Step 1',
                ], 404);
            }

            $donorNo = mt_rand(10000000, 99999999); 

            // Ensure the generated donor number is unique in the database
            while (UserDetail::where('donor_no', $donorNo)->exists()) {
                $donorNo = mt_rand(10000000, 99999999); // Regenerate if the number already exists
            }        

           
            $userDetails = UserDetail::updateOrCreate(
                ['user_id' => $user_id],
                [
                    'donor_no'          => $donorNo,
                    'first_name'        => $validatedData['first_name'],
                    'middle_name'       => $validatedData['middle_name'],
                    'last_name'         => $validatedData['last_name'],
                    'dob'               => $validatedData['dob'],
                    'sex'               => $validatedData['sex'],
                    'blood_type'        => $validatedData['blood_type'],
                    'occupation'        => $validatedData['occupation'],
                    'street'            => $validatedData['street'],
                    'region'            => $validatedData['region'],
                    'province'          => $validatedData['province'],
                    'municipality'      => $validatedData['municipality'],
                    'barangay'          => $validatedData['barangay'],
                    'postalcode'        => $validatedData['postalcode'],
                ]
            );

            Galloner::create([
                'user_id'    => $user_id,
            ]);
            
            // Send email notification
            Mail::to($user->email)->send(new RegistrationMail($user));

            return response()->json([
                'status'            => 'success',
                'message'           => 'Registration Complete.',
                'next_step'         => '0',
                'user_id'           => $user->user_id,
                'email'             => $user->email,
                'user_details'      => $userDetails,
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
