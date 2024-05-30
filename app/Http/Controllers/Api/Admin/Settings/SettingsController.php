<?php

namespace App\Http\Controllers\Api\Admin\Settings;

use App\Http\Controllers\Controller;
use App\Models\AuditTrail;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth;

use App\Models\Setting;
use App\Models\Venue;
use App\Models\Hospital;
use App\Models\BledBy;
use App\Models\UserDetail;
use App\Models\ReactiveRemarks;
use App\Models\SpoiledRemarks;
use App\Models\Category;
use App\Models\PersonalAccessToken;
use Illuminate\Support\Facades\DB;

class SettingsController extends Controller
{
    public function createSecurityPin(Request $request)
    {

        try {
            $request->validate([
                'security_pin' => ['required', 'min:8'],
            ], [
                'security_pin.min' => 'Security pin must be at least 8 characters or digits.',
            ]);

            $securityPin = $request->input('security_pin');
            $hashedPin = password_hash($securityPin, PASSWORD_DEFAULT);

            $pin = Setting::where('setting_desc', 'security_pin')->first();
            $pin->setting_value = $hashedPin;
            $pin->save();

            // dd($hashedPin);

            return response()->json([
                'status' => 'success',
                'message' => 'Security pin saved successfully',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->validator->errors(),
            ], 400);
        }
    }

    public function changeSecurityPin(Request $request)
    {
        $user = Auth::user();
        $userId = $user->user_id;

        try {
            $request->validate([
                'old_security_pin' => ['required'],
                'new_security_pin' => ['required', 'min:8'],
            ], [
                'new_security_pin.min' => 'Security pin must be at least 8 characters or digits.',
            ]);

            $oldSecurityPin = $request->input('old_security_pin');
            $newSecurityPin = $request->input('new_security_pin');
            $hashedPin = password_hash($newSecurityPin, PASSWORD_DEFAULT);

            $ip = file_get_contents('https://api.ipify.org');
            $ch = curl_init('http://ipwho.is/' . $ip);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HEADER, false);

            $ipwhois = json_decode(curl_exec($ch), true);

            curl_close($ch);

            $pin = Setting::where('setting_desc', 'security_pin')->first();

            if (password_verify($oldSecurityPin, $pin->setting_value)) {

                AuditTrail::create([
                    'user_id'    => $userId,
                    'module'     => 'Settings',
                    'action'     => 'Change Security Pin',
                    'status'     => 'success',
                    'ip_address' => $ipwhois['ip'],
                    'region'     => $ipwhois['region'],
                    'city'       => $ipwhois['city'],
                    'postal'     => $ipwhois['postal'],
                    'latitude'   => $ipwhois['latitude'],
                    'longitude'  => $ipwhois['longitude'],
                ]);

                $pin->setting_value = $hashedPin;
                $pin->save();

                return response()->json([
                    'status' => 'success',
                    'message' => 'Security pin has been updated successfully',
                ], 200);
            } else {
                AuditTrail::create([
                    'user_id'    => $userId,
                    'module'     => 'Settings',
                    'action'     => 'Change Security Pin',
                    'status'     => 'failed',
                    'ip_address' => $ipwhois['ip'],
                    'region'     => $ipwhois['region'],
                    'city'       => $ipwhois['city'],
                    'postal'     => $ipwhois['postal'],
                    'latitude'   => $ipwhois['latitude'],
                    'longitude'  => $ipwhois['longitude'],
                ]);

                return response()->json([
                    'status' => 'error',
                    'message' => 'Old security pin is incorrect',
                ], 400);
            }
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->validator->errors(),
            ], 400);
        }
    }

    public function checkSecurityPin(Request $request)
    {
        $user = Auth::user();
        $userId = $user->user_id;

        try {
            $request->validate([
                'security_pin' => ['required'],
            ]);

            $ip = file_get_contents('https://api.ipify.org');
            $ch = curl_init('http://ipwho.is/' . $ip);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HEADER, false);

            $ipwhois = json_decode(curl_exec($ch), true);

            curl_close($ch);

            $securityPin = $request->input('security_pin');
            $setting = Setting::where('setting_desc', 'security_pin')->first();
            $savedSecurityPin = $setting->setting_value;

            if (password_verify($securityPin, $savedSecurityPin)) {
                AuditTrail::create([
                    'user_id'    => $userId,
                    'module'     => 'Deferral List',
                    'action'     => 'Accessed Deferral List',
                    'status'     => 'success',
                    'ip_address' => $ipwhois['ip'],
                    'region'     => $ipwhois['region'],
                    'city'       => $ipwhois['city'],
                    'postal'     => $ipwhois['postal'],
                    'latitude'   => $ipwhois['latitude'],
                    'longitude'  => $ipwhois['longitude'],
                ]);
                return response()->json([
                    'status' => 'success',
                    'message' => 'Security pin is correct',
                ]);
            } else {
                AuditTrail::create([
                    'user_id'    => $userId,
                    'module'     => 'Deferral List',
                    'action'     => 'Accessed Deferral List',
                    'status'     => 'failed',
                    'ip_address' => $ipwhois['ip'],
                    'region'     => $ipwhois['region'],
                    'city'       => $ipwhois['city'],
                    'postal'     => $ipwhois['postal'],
                    'latitude'   => $ipwhois['latitude'],
                    'longitude'  => $ipwhois['longitude'],
                ]);
                return response()->json([
                    'status' => 'error',
                    'message' => 'Security pin is incorrect',
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

    public function addVenue(Request $request)
    {
        $user = Auth::user();
        $userId = $user->user_id;

        try {
            $request->validate([
                'venues_desc' => ['required', 'string', 'max:255', 'unique:venues,venues_desc'],
                'venue_address' => ['required'],
            ]);

            $ip = file_get_contents('https://api.ipify.org');
            $ch = curl_init('http://ipwho.is/' . $ip);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HEADER, false);

            $ipwhois = json_decode(curl_exec($ch), true);

            curl_close($ch);

            $venueDesc = $request->input('venues_desc');
            $venueAddress = $request->input('venue_address');

            Venue::create([
                'venues_desc' => ucwords(strtolower($venueDesc)),
                'venue_address' => ucwords(strtolower($venueAddress))
            ]);


            AuditTrail::create([
                'user_id'    => $userId,
                'module'     => 'Settings',
                'action'     => 'Added Venue | ' . $venueDesc,
                'status'     => 'success',
                'ip_address' => $ipwhois['ip'],
                'region'     => $ipwhois['region'],
                'city'       => $ipwhois['city'],
                'postal'     => $ipwhois['postal'],
                'latitude'   => $ipwhois['latitude'],
                'longitude'  => $ipwhois['longitude'],
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Venue added successfully',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->validator->errors(),
            ], 400);
        }
    }

    public function getVenues()
    {

        $venues = Venue::where('status', 0)->get();

        return response()->json([
            'status' => 'success',
            'message' => 'Venues retrieved successfully',
            'data' => $venues
        ]);
    }

    public function editVenue(Request $request)
    {

        $user = Auth::user();
        $userId = $user->user_id;

        try {
            $request->validate([
                'venues_id' => ['required'],
                'venues_desc' => ['required', 'string', 'max:255', 'unique:venues,venues_desc,' . $request->venues_id . ',venues_id'],
                'venue_address' => ['required'],
            ]);

            $ip = file_get_contents('https://api.ipify.org');
            $ch = curl_init('http://ipwho.is/' . $ip);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HEADER, false);

            $ipwhois = json_decode(curl_exec($ch), true);

            curl_close($ch);

            $venues_id = $request->input('venues_id');
            $venueDesc = $request->input('venues_desc');
            $venueAddress = $request->input('venue_address');

            $oldVenueDesc = Venue::where('venues_id', $venues_id)->first();

            if (!$oldVenueDesc) {

                return response()->json([
                    'status' => 'error',
                    'message' => 'Venue not found',
                ], 400);
            } else {
                AuditTrail::create([
                    'user_id'    => $userId,
                    'module'     => 'Settings',
                    'action'     => 'Edit Venue | ' . $oldVenueDesc->venues_desc,
                    'status'     => 'success',
                    'ip_address' => $ipwhois['ip'],
                    'region'     => $ipwhois['region'],
                    'city'       => $ipwhois['city'],
                    'postal'     => $ipwhois['postal'],
                    'latitude'   => $ipwhois['latitude'],
                    'longitude'  => $ipwhois['longitude'],
                ]);

                $oldVenueDesc->venues_desc = ucwords(strtolower($venueDesc));
                $oldVenueDesc->venue_address = ucwords(strtolower($venueAddress));
                $oldVenueDesc->save();

                return response()->json([
                    'status' => 'success',
                    'message' => 'Venue updated successfully',
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

    public function deleteVenue(Request $request)
    {

        $user = Auth::user();
        $userId = $user->user_id;

        try {
            $request->validate([
                'venues_id' => ['required'],
            ]);

            $ip = file_get_contents('https://api.ipify.org');
            $ch = curl_init('http://ipwho.is/' . $ip);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HEADER, false);

            $ipwhois = json_decode(curl_exec($ch), true);

            curl_close($ch);

            $venues_id = $request->input('venues_id');

            $oldVenueDesc = Venue::where('venues_id', $venues_id)->first();

            if (!$oldVenueDesc) {

                return response()->json([
                    'status' => 'error',
                    'message' => 'Venue not found',
                ], 400);
            } else {

                AuditTrail::create([
                    'user_id'    => $userId,
                    'module'     => 'Settings',
                    'action'     => 'Delete Venue | ' . $oldVenueDesc->venues_desc,
                    'status'     => 'success',
                    'ip_address' => $ipwhois['ip'],
                    'region'     => $ipwhois['region'],
                    'city'       => $ipwhois['city'],
                    'postal'     => $ipwhois['postal'],
                    'latitude'   => $ipwhois['latitude'],
                    'longitude'  => $ipwhois['longitude'],
                ]);

                $oldVenueDesc->status = 1;
                $oldVenueDesc->save();

                return response()->json([
                    'status' => 'success',
                    'message' => 'Successfully removed Venue',
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

    public function addHospital(Request $request)
    {

        $user = Auth::user();
        $userId = $user->user_id;

        try {
            $request->validate([
                'hospital_desc' => ['required', 'string', 'max:255', 'unique:hospitals,hospital_desc'],
                'hospital_address' => ['required'],
            ]);

            $ip = file_get_contents('https://api.ipify.org');
            $ch = curl_init('http://ipwho.is/' . $ip);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HEADER, false);

            $ipwhois = json_decode(curl_exec($ch), true);

            curl_close($ch);

            $hospitalDesc = $request->input('hospital_desc');
            $hospitalAddress = $request->input('hospital_address');

            Hospital::create([
                'hospital_desc' => ucwords(strtolower($hospitalDesc)),
                'hospital_address' => ucwords(strtolower($hospitalAddress))
            ]);


            AuditTrail::create([
                'user_id'    => $userId,
                'module'     => 'Settings',
                'action'     => 'Added Hospital | ' . $hospitalDesc,
                'status'     => 'success',
                'ip_address' => $ipwhois['ip'],
                'region'     => $ipwhois['region'],
                'city'       => $ipwhois['city'],
                'postal'     => $ipwhois['postal'],
                'latitude'   => $ipwhois['latitude'],
                'longitude'  => $ipwhois['longitude'],
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Hospital added successfully',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->validator->errors(),
            ], 400);
        }
    }

    public function getHospitals()
    {

        $hospital = Hospital::where('status', 0)->get();

        return response()->json([
            'status' => 'success',
            'message' => 'Venues retrieved successfully',
            'data' => $hospital
        ]);
    }

    public function editHospital(Request $request)
    {

        $user = Auth::user();
        $userId = $user->user_id;

        try {
            $request->validate([
                'hospitals_id' => ['required'],
                'hospital_desc' => ['required', 'string', 'max:255', 'unique:hospitals,hospital_desc,' . $request->hospitals_id . ',hospitals_id'],
                'hospital_address' => ['required'],
            ]);

            $ip = file_get_contents('https://api.ipify.org');
            $ch = curl_init('http://ipwho.is/' . $ip);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HEADER, false);

            $ipwhois = json_decode(curl_exec($ch), true);

            curl_close($ch);

            $hospitalsId = $request->input('hospitals_id');
            $hospitalDesc = $request->input('hospital_desc');
            $hospitalAddress = $request->input('hospital_address');

            $oldHospitalDesc = Hospital::where('hospitals_id', $hospitalsId)->first();

            if (!$oldHospitalDesc) {

                return response()->json([
                    'status' => 'error',
                    'message' => 'Hospital not found',
                ], 400);
            } else {
                AuditTrail::create([
                    'user_id'    => $userId,
                    'module'     => 'Settings',
                    'action'     => 'Edit Venue | ' . $oldHospitalDesc . ' to ' . $hospitalDesc,
                    'status'     => 'success',
                    'ip_address' => $ipwhois['ip'],
                    'region'     => $ipwhois['region'],
                    'city'       => $ipwhois['city'],
                    'postal'     => $ipwhois['postal'],
                    'latitude'   => $ipwhois['latitude'],
                    'longitude'  => $ipwhois['longitude'],
                ]);

                $oldHospitalDesc->hospital_desc = ucwords(strtolower($hospitalDesc));
                $oldHospitalDesc->hospital_address = ucwords(strtolower($hospitalAddress));
                $oldHospitalDesc->save();

                return response()->json([
                    'status' => 'success',
                    'message' => 'Hospital updated successfully',
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

    public function deleteHospital(Request $request)
    {

        $user = Auth::user();
        $userId = $user->user_id;

        try {
            $request->validate([
                'hospitals_id' => ['required'],
            ]);

            $ip = file_get_contents('https://api.ipify.org');
            $ch = curl_init('http://ipwho.is/' . $ip);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HEADER, false);

            $ipwhois = json_decode(curl_exec($ch), true);

            curl_close($ch);

            $hospitalId = $request->input('hospitals_id');

            $oldHospitalDesc = Hospital::where('hospitals_id', $hospitalId)->first();

            if (!$oldHospitalDesc) {

                return response()->json([
                    'status' => 'error',
                    'message' => 'Venue not found',
                ], 400);
            } else {

                AuditTrail::create([
                    'user_id'    => $userId,
                    'module'     => 'Settings',
                    'action'     => 'Delete Hospital | ' . $oldHospitalDesc->hospital_desc,
                    'status'     => 'success',
                    'ip_address' => $ipwhois['ip'],
                    'region'     => $ipwhois['region'],
                    'city'       => $ipwhois['city'],
                    'postal'     => $ipwhois['postal'],
                    'latitude'   => $ipwhois['latitude'],
                    'longitude'  => $ipwhois['longitude'],
                ]);

                $oldHospitalDesc->status = 1;
                $oldHospitalDesc->save();

                return response()->json([
                    'status' => 'success',
                    'message' => 'Successfully removed Hospital',
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

    public function addBledBy(Request $request)
    {

        $user = Auth::user();
        $userId = $user->user_id;

        try {
            $validatedData = $request->validate([
                'first_name'            => ['required', 'string'],
                'middle_name'           => ['nullable', 'string'],
                'last_name'             => ['required', 'string'],
            ], [
                'user_id.required'  => 'Invalid. Proceed to step 1',
                'user_id.exists'    => 'Invalid. Proceed to step 1',
                'before_or_equal'   => 'You must at least 17 years old to register',
            ]);

            $ip = file_get_contents('https://api.ipify.org');
            $ch = curl_init('http://ipwho.is/' . $ip);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HEADER, false);

            $ipwhois = json_decode(curl_exec($ch), true);

            curl_close($ch);

            $existingUser = BledBy::where('first_name', $request->first_name)
                ->where('middle_name', $request->middle_name)
                ->where('last_name', $request->last_name)
                ->first();

            if ($existingUser) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'User with the same full name already exists',
                ], 400);
            } else {

                BledBy::Create(
                    [
                        'first_name'        => ucwords(strtolower($validatedData['first_name'])),
                        'middle_name'       => ucwords(strtolower($validatedData['middle_name'])),
                        'last_name'         => ucwords(strtolower($validatedData['last_name'])),
                    ]
                );


                AuditTrail::create([
                    'user_id'    => $userId,
                    'module'     => 'Settings',
                    'action'     => 'Added BledBy | ' . $validatedData['first_name'] . ' ' . $validatedData['last_name'],
                    'status'     => 'success',
                    'ip_address' => $ipwhois['ip'],
                    'region'     => $ipwhois['region'],
                    'city'       => $ipwhois['city'],
                    'postal'     => $ipwhois['postal'],
                    'latitude'   => $ipwhois['latitude'],
                    'longitude'  => $ipwhois['longitude'],
                ]);

                return response()->json([
                    'status' => 'success',
                    'message' => 'Bled By added successfully',
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

    public function getBledBy()
    {

        $bledBy = BledBy::where('bled_by.status', 0)->get();

        return response()->json([
            'status' => 'success',
            'message' => 'Bled by retrieved successfully',
            'data' => $bledBy
        ]);
    }

    public function editBledBy(Request $request)
    {

        $user = Auth::user();
        $userId = $user->user_id;

        try {
            $validatedData = $request->validate([
                'bled_by_id'       => ['required'],
                'first_name'            => ['required', 'string'],
                'middle_name'           => ['nullable', 'string'],
                'last_name'             => ['required', 'string'],
            ]);

            $ip = file_get_contents('https://api.ipify.org');
            $ch = curl_init('http://ipwho.is/' . $ip);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HEADER, false);

            $ipwhois = json_decode(curl_exec($ch), true);

            curl_close($ch);

            $oldBledBy = BledBy::where('bled_by_id', $validatedData['bled_by_id'])->first();

            if (!$oldBledBy) {

                return response()->json([
                    'status' => 'error',
                    'message' => 'Bled By Details not found',
                ], 400);
            } else {

                $existingUser = BledBy::where('first_name', $request->first_name)
                    ->where('middle_name', $request->middle_name)
                    ->where('last_name', $request->last_name)
                    ->first();

                if ($existingUser) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'User with the same full name already exists',
                    ], 400);
                } else {
                    AuditTrail::create([
                        'user_id'    => $userId,
                        'module'     => 'Settings',
                        'action'     => 'Edit Bled By Details for | ' . $oldBledBy->first_name . ' ' . $oldBledBy->last_name,
                        'status'     => 'success',
                        'ip_address' => $ipwhois['ip'],
                        'region'     => $ipwhois['region'],
                        'city'       => $ipwhois['city'],
                        'postal'     => $ipwhois['postal'],
                        'latitude'   => $ipwhois['latitude'],
                        'longitude'  => $ipwhois['longitude'],
                    ]);

                    $oldBledBy->first_name = ucwords(strtolower($validatedData['first_name']));
                    $oldBledBy->middle_name = ucwords(strtolower($validatedData['middle_name']));
                    $oldBledBy->last_name = ucwords(strtolower($validatedData['last_name']));
                    $oldBledBy->save();

                    return response()->json([
                        'status' => 'success',
                        'message' => 'Bled By Details updated successfully',
                    ]);
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

    public function deleteBledBy(Request $request)
    {

        $user = Auth::user();
        $userId = $user->user_id;

        try {
            $request->validate([
                'bled_by_id' => ['required'],
            ]);

            $ip = file_get_contents('https://api.ipify.org');
            $ch = curl_init('http://ipwho.is/' . $ip);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HEADER, false);

            $ipwhois = json_decode(curl_exec($ch), true);

            curl_close($ch);

            $bledById = $request->input('bled_by_id');

            $oldUSerDetail = BledBy::where('bled_by_id', $bledById)->first();

            if (!$oldUSerDetail) {

                return response()->json([
                    'status' => 'error',
                    'message' => 'Venue not found',
                ], 400);
            } else {

                AuditTrail::create([
                    'user_id'    => $userId,
                    'module'     => 'Settings',
                    'action'     => 'Delete Bled By | ' . $oldUSerDetail->first_name . ' ' . $oldUSerDetail->last_name,
                    'status'     => 'success',
                    'ip_address' => $ipwhois['ip'],
                    'region'     => $ipwhois['region'],
                    'city'       => $ipwhois['city'],
                    'postal'     => $ipwhois['postal'],
                    'latitude'   => $ipwhois['latitude'],
                    'longitude'  => $ipwhois['longitude'],
                ]);

                $oldUSerDetail->status = 1;
                $oldUSerDetail->save();

                return response()->json([
                    'status' => 'success',
                    'message' => 'Successfully removed Bled By',
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


    public function addReactiveRemarks(Request $request)
    {

        $user = Auth::user();
        $userId = $user->user_id;

        try {
            $request->validate([
                'reactive_remarks_desc' => ['required', 'string', 'max:255', 'unique:reactive_remarks,reactive_remarks_desc'],
            ]);

            $ip = file_get_contents('https://api.ipify.org');
            $ch = curl_init('http://ipwho.is/' . $ip);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HEADER, false);

            $ipwhois = json_decode(curl_exec($ch), true);

            curl_close($ch);

            $reactiveRemarksDesc = $request->input('reactive_remarks_desc');

            ReactiveRemarks::create([
                'reactive_remarks_desc' => ucwords(strtolower($reactiveRemarksDesc))
            ]);


            AuditTrail::create([
                'user_id'    => $userId,
                'module'     => 'Settings',
                'action'     => 'Added Reactive Remarks | ' . $reactiveRemarksDesc,
                'status'     => 'success',
                'ip_address' => $ipwhois['ip'],
                'region'     => $ipwhois['region'],
                'city'       => $ipwhois['city'],
                'postal'     => $ipwhois['postal'],
                'latitude'   => $ipwhois['latitude'],
                'longitude'  => $ipwhois['longitude'],
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Reactive Remarks added successfully',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->validator->errors(),
            ], 400);
        }
    }

    public function getReactiveRemarks(Request $request)
    {

        $reactiveRemarks = ReactiveRemarks::where('status', 0)->get();

        return response()->json([
            'status' => 'success',
            'message' => 'Venues retrieved successfully',
            'data' => $reactiveRemarks
        ]);
    }

    public function editReactiveRemarks(Request $request)
    {

        $user = Auth::user();
        $userId = $user->user_id;

        try {
            $request->validate([
                'reactive_remarks_id' => ['required'],
                'reactive_remarks_desc' => ['required', 'string', 'max:255', 'unique:reactive_remarks,reactive_remarks_desc,' . $request->reactive_remarks_id . ',reactive_remarks_id'],
            ]);

            $ip = file_get_contents('https://api.ipify.org');
            $ch = curl_init('http://ipwho.is/' . $ip);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HEADER, false);

            $ipwhois = json_decode(curl_exec($ch), true);

            curl_close($ch);

            $reactiveRemarksId = $request->input('reactive_remarks_id');
            $reactiveRemarkDesc = $request->input('reactive_remarks_desc');

            $oldReactiveRemarksDesc = ReactiveRemarks::where('reactive_remarks_id', $reactiveRemarksId)->first();

            if (!$oldReactiveRemarksDesc) {

                return response()->json([
                    'status' => 'error',
                    'message' => 'Venue not found',
                ], 400);
            } else {
                AuditTrail::create([
                    'user_id'    => $userId,
                    'module'     => 'Settings',
                    'action'     => 'Edit Reactive Remarks | ' . $oldReactiveRemarksDesc->reactive_remarks_desc . ' to ' . $reactiveRemarkDesc,
                    'status'     => 'success',
                    'ip_address' => $ipwhois['ip'],
                    'region'     => $ipwhois['region'],
                    'city'       => $ipwhois['city'],
                    'postal'     => $ipwhois['postal'],
                    'latitude'   => $ipwhois['latitude'],
                    'longitude'  => $ipwhois['longitude'],
                ]);

                $oldReactiveRemarksDesc->reactive_remarks_desc = ucwords(strtolower($reactiveRemarkDesc));
                $oldReactiveRemarksDesc->save();

                return response()->json([
                    'status' => 'success',
                    'message' => 'Reactive Remarks updated successfully',
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

    public function deleteReactiveRemarks(Request $request)
    {

        $user = Auth::user();
        $userId = $user->user_id;

        try {
            $request->validate([
                'reactive_remarks_id' => ['required'],
            ]);

            $ip = file_get_contents('https://api.ipify.org');
            $ch = curl_init('http://ipwho.is/' . $ip);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HEADER, false);

            $ipwhois = json_decode(curl_exec($ch), true);

            curl_close($ch);

            $reactiveRemarksId = $request->input('reactive_remarks_id');

            $oldReactiveRemarks = ReactiveRemarks::where('reactive_remarks_id', $reactiveRemarksId)->first();

            if (!$oldReactiveRemarks) {

                return response()->json([
                    'status' => 'error',
                    'message' => 'Venue not found',
                ], 400);
            } else {

                AuditTrail::create([
                    'user_id'    => $userId,
                    'module'     => 'Settings',
                    'action'     => 'Delete Reactive Remarks | ' . $oldReactiveRemarks->reactive_remarks_desc,
                    'status'     => 'success',
                    'ip_address' => $ipwhois['ip'],
                    'region'     => $ipwhois['region'],
                    'city'       => $ipwhois['city'],
                    'postal'     => $ipwhois['postal'],
                    'latitude'   => $ipwhois['latitude'],
                    'longitude'  => $ipwhois['longitude'],
                ]);

                $oldReactiveRemarks->status = 1;
                $oldReactiveRemarks->save();

                return response()->json([
                    'status' => 'success',
                    'message' => 'Successfully removed Reactive Remarks',
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

    public function addSpoiledRemarks(Request $request)
    {

        $user = Auth::user();
        $userId = $user->user_id;

        try {
            $request->validate([
                'spoiled_remarks_desc' => ['required', 'string', 'max:255', 'unique:spoiled_remarks,spoiled_remarks_desc'],
            ]);

            $ip = file_get_contents('https://api.ipify.org');
            $ch = curl_init('http://ipwho.is/' . $ip);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HEADER, false);

            $ipwhois = json_decode(curl_exec($ch), true);

            curl_close($ch);

            $spoiledRemarksDesc = $request->input('spoiled_remarks_desc');

            SpoiledRemarks::create([
                'spoiled_remarks_desc' => ucwords(strtolower($spoiledRemarksDesc))
            ]);


            AuditTrail::create([
                'user_id'    => $userId,
                'module'     => 'Settings',
                'action'     => 'Added Spoiled Remarks | ' . $spoiledRemarksDesc,
                'status'     => 'success',
                'ip_address' => $ipwhois['ip'],
                'region'     => $ipwhois['region'],
                'city'       => $ipwhois['city'],
                'postal'     => $ipwhois['postal'],
                'latitude'   => $ipwhois['latitude'],
                'longitude'  => $ipwhois['longitude'],
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Spoiled Remarks added successfully',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->validator->errors(),
            ], 400);
        }
    }

    public function getSpoiledRemarks()
    {

        $spoiledRemarks = SpoiledRemarks::where('status', 0)->get();

        return response()->json([
            'status' => 'success',
            'message' => 'Spoiled Remarks retrieved successfully',
            'data' => $spoiledRemarks
        ]);
    }

    public function editSpoiledRemarks(Request $request)
    {

        $user = Auth::user();
        $userId = $user->user_id;

        try {
            $request->validate([
                'spoiled_remarks_id' => ['required'],
                'spoiled_remarks_desc' => ['required', 'string', 'max:255', 'unique:spoiled_remarks,spoiled_remarks_desc,' . $request->spoiled_remarks_id . ',spoiled_remarks_id'],
            ]);

            $ip = file_get_contents('https://api.ipify.org');
            $ch = curl_init('http://ipwho.is/' . $ip);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HEADER, false);

            $ipwhois = json_decode(curl_exec($ch), true);

            curl_close($ch);

            $spoiledRemarksId = $request->input('spoiled_remarks_id');
            $spoiledRemarkDesc = $request->input('spoiled_remarks_desc');

            $oldSpoiledRemarksDesc = SpoiledRemarks::where('spoiled_remarks_id', $spoiledRemarksId)->first();

            if (!$oldSpoiledRemarksDesc) {

                return response()->json([
                    'status' => 'error',
                    'message' => 'Venue not found',
                ], 400);
            } else {

                AuditTrail::create([
                    'user_id'    => $userId,
                    'module'     => 'Settings',
                    'action'     => 'Edit Reactive Remarks | ' . $oldSpoiledRemarksDesc->spoiled_remarks_desc . ' to ' . $spoiledRemarkDesc,
                    'status'     => 'success',
                    'ip_address' => $ipwhois['ip'],
                    'region'     => $ipwhois['region'],
                    'city'       => $ipwhois['city'],
                    'postal'     => $ipwhois['postal'],
                    'latitude'   => $ipwhois['latitude'],
                    'longitude'  => $ipwhois['longitude'],
                ]);

                $oldSpoiledRemarksDesc->spoiled_remarks_desc = ucwords(strtolower($spoiledRemarkDesc));
                $oldSpoiledRemarksDesc->save();

                return response()->json([
                    'status' => 'success',
                    'message' => 'Reactive Remarks updated successfully',
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

    public function deleteSpoiledRemarks(Request $request)
    {

        $user = Auth::user();
        $userId = $user->user_id;

        try {
            $request->validate([
                'spoiled_remarks_id' => ['required'],
            ]);

            $ip = file_get_contents('https://api.ipify.org');
            $ch = curl_init('http://ipwho.is/' . $ip);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HEADER, false);

            $ipwhois = json_decode(curl_exec($ch), true);

            curl_close($ch);

            $spoiledRemarksId = $request->input('spoiled_remarks_id');

            $oldSpoiledRemarks = SpoiledRemarks::where('spoiled_remarks_id', $spoiledRemarksId)->first();

            if (!$oldSpoiledRemarks) {

                return response()->json([
                    'status' => 'error',
                    'message' => 'Venue not found',
                ], 400);
            } else {

                AuditTrail::create([
                    'user_id'    => $userId,
                    'module'     => 'Settings',
                    'action'     => 'Delete Reactive Remarks | ' . $oldSpoiledRemarks->spoiled_remarks_desc,
                    'status'     => 'success',
                    'ip_address' => $ipwhois['ip'],
                    'region'     => $ipwhois['region'],
                    'city'       => $ipwhois['city'],
                    'postal'     => $ipwhois['postal'],
                    'latitude'   => $ipwhois['latitude'],
                    'longitude'  => $ipwhois['longitude'],
                ]);

                $oldSpoiledRemarks->status = 1;
                $oldSpoiledRemarks->save();

                return response()->json([
                    'status' => 'success',
                    'message' => 'Successfully removed Spoiled Remarks',
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

    public function addPermanentDeferralCategory(Request $request)
    {

        $user = Auth::user();
        $userId = $user->user_id;

        try {
            $request->validate([
                'category_desc' => ['required', 'string', 'max:255', 'unique:categories,category_desc'],
            ]);

            $ip = file_get_contents('https://api.ipify.org');
            $ch = curl_init('http://ipwho.is/' . $ip);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HEADER, false);

            $ipwhois = json_decode(curl_exec($ch), true);

            curl_close($ch);

            $permanentCategory = $request->input('category_desc');

            Category::create([
                'category_desc' => ucwords(strtolower($permanentCategory)),
                'deferral_type_id' => 2
            ]);


            AuditTrail::create([
                'user_id'    => $userId,
                'module'     => 'Settings',
                'action'     => 'Added Permanent Category| ' . $permanentCategory,
                'status'     => 'success',
                'ip_address' => $ipwhois['ip'],
                'region'     => $ipwhois['region'],
                'city'       => $ipwhois['city'],
                'postal'     => $ipwhois['postal'],
                'latitude'   => $ipwhois['latitude'],
                'longitude'  => $ipwhois['longitude'],
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Spoiled Remarks added successfully',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->validator->errors(),
            ], 400);
        }
    }

    public function getPermanentDeferralCategory(Request $request)
    {

        $spoiledRemarks = Category::where('deferral_type_id', 2)
            ->where('status', 0)
            ->select('category_desc', 'categories_id')
            ->get();

        return response()->json([
            'status' => 'success',
            'message' => 'Spoiled Remarks retrieved successfully',
            'data' => $spoiledRemarks
        ]);
    }

    public function editPermanentDeferralCategory(Request $request)
    {

        $user = Auth::user();
        $userId = $user->user_id;

        try {
            $request->validate([
                'categories_id' => ['required'],
                'category_desc' => ['required', 'string', 'max:255', 'unique:categories,category_desc,' . $request->categories_id . ',categories_id'],
            ]);

            $ip = file_get_contents('https://api.ipify.org');
            $ch = curl_init('http://ipwho.is/' . $ip);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HEADER, false);

            $ipwhois = json_decode(curl_exec($ch), true);

            curl_close($ch);

            $categoryId = $request->input('categories_id');
            $categoryDesc = $request->input('category_desc');

            $oldCategoryDesc = Category::where('categories_id', $categoryId)->first();

            if (!$oldCategoryDesc) {

                return response()->json([
                    'status' => 'error',
                    'message' => 'Venue not found',
                ], 400);
            } else {

                AuditTrail::create([
                    'user_id'    => $userId,
                    'module'     => 'Settings',
                    'action'     => 'Edit Permanent Category | ' . $oldCategoryDesc->category_desc . ' to ' . $categoryDesc,
                    'status'     => 'success',
                    'ip_address' => $ipwhois['ip'],
                    'region'     => $ipwhois['region'],
                    'city'       => $ipwhois['city'],
                    'postal'     => $ipwhois['postal'],
                    'latitude'   => $ipwhois['latitude'],
                    'longitude'  => $ipwhois['longitude'],
                ]);

                $oldCategoryDesc->category_desc = ucwords(strtolower($categoryDesc));
                $oldCategoryDesc->save();

                return response()->json([
                    'status' => 'success',
                    'message' => 'Reactive Remarks updated successfully',
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

    public function deletePermanentDeferralCategory(Request $request)
    {

        $user = Auth::user();
        $userId = $user->user_id;

        try {
            $request->validate([
                'categories_id' => ['required'],
            ]);

            $ip = file_get_contents('https://api.ipify.org');
            $ch = curl_init('http://ipwho.is/' . $ip);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HEADER, false);

            $ipwhois = json_decode(curl_exec($ch), true);

            curl_close($ch);

            $categoryId = $request->input('categories_id');

            $oldCategory = Category::where('categories_id', $categoryId)->first();

            if (!$oldCategory) {

                return response()->json([
                    'status' => 'error',
                    'message' => 'Venue not found',
                ], 400);
            } else {

                AuditTrail::create([
                    'user_id'    => $userId,
                    'module'     => 'Settings',
                    'action'     => 'Delete Permanent Category | ' . $oldCategory->categories_id,
                    'status'     => 'success',
                    'ip_address' => $ipwhois['ip'],
                    'region'     => $ipwhois['region'],
                    'city'       => $ipwhois['city'],
                    'postal'     => $ipwhois['postal'],
                    'latitude'   => $ipwhois['latitude'],
                    'longitude'  => $ipwhois['longitude'],
                ]);

                $oldCategory->status = 1;
                $oldCategory->save();

                return response()->json([
                    'status' => 'success',
                    'message' => 'Successfully removed Permanent Category',
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

    public function addTemporaryDeferralCategory(Request $request)
    {

        $user = Auth::user();
        $userId = $user->user_id;

        try {
            $request->validate([
                'category_desc' => ['required', 'string', 'max:255', 'unique:categories,category_desc'],
                'remarks' => ['required', 'string', 'max:255', 'unique:categories,remarks'],
            ]);

            $ip = file_get_contents('https://api.ipify.org');
            $ch = curl_init('http://ipwho.is/' . $ip);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HEADER, false);

            $ipwhois = json_decode(curl_exec($ch), true);

            curl_close($ch);

            $permanentCategory = $request->input('category_desc');
            $permanentRemarks = $request->input('remarks');

            Category::create([
                'deferral_type_id' => 1,
                'category_desc' => ucwords(strtolower($permanentCategory)),
                'remarks' => $permanentRemarks,
            ]);


            AuditTrail::create([
                'user_id'    => $userId,
                'module'     => 'Settings',
                'action'     => 'Added temporary Category| ' . $permanentCategory,
                'status'     => 'success',
                'ip_address' => $ipwhois['ip'],
                'region'     => $ipwhois['region'],
                'city'       => $ipwhois['city'],
                'postal'     => $ipwhois['postal'],
                'latitude'   => $ipwhois['latitude'],
                'longitude'  => $ipwhois['longitude'],
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Temporary added successfully',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->validator->errors(),
            ], 400);
        }
    }

    public function getTemporaryDeferralCategory(Request $request)
    {

        $spoiledRemarks = Category::where('deferral_type_id', 1)
            ->where('status', 0)
            ->select('category_desc', 'categories_id', 'remarks')
            ->get();

        return response()->json([
            'status' => 'success',
            'message' => 'Temporaray Deferral Category retrieved successfully',
            'data' => $spoiledRemarks
        ]);
    }

    public function editTemporaryDeferralCategory(Request $request)
    {

        $user = Auth::user();
        $userId = $user->user_id;

        try {
            $request->validate([
                'categories_id' => ['required'],
                'category_desc' => ['required', 'string', 'max:255', 'unique:categories,category_desc,' . $request->categories_id . ',categories_id'],
                'remarks' => ['required', 'string', 'max:255', 'unique:categories,remarks,' . $request->categories_id . ',categories_id'],
            ]);

            $ip = file_get_contents('https://api.ipify.org');
            $ch = curl_init('http://ipwho.is/' . $ip);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HEADER, false);

            $ipwhois = json_decode(curl_exec($ch), true);

            curl_close($ch);

            $categoryId = $request->input('categories_id');
            $categoryDesc = $request->input('category_desc');
            $remarks = $request->input('remarks');

            $oldCategoryDesc = Category::where('categories_id', $categoryId)->first();

            if (!$oldCategoryDesc) {

                return response()->json([
                    'status' => 'error',
                    'message' => 'Venue not found',
                ], 400);
            } else {

                AuditTrail::create([
                    'user_id'    => $userId,
                    'module'     => 'Settings',
                    'action'     => 'Edit Temporary Category| ' . $oldCategoryDesc->category_desc . ' to ' . $categoryDesc,
                    'status'     => 'success',
                    'ip_address' => $ipwhois['ip'],
                    'region'     => $ipwhois['region'],
                    'city'       => $ipwhois['city'],
                    'postal'     => $ipwhois['postal'],
                    'latitude'   => $ipwhois['latitude'],
                    'longitude'  => $ipwhois['longitude'],
                ]);

                $oldCategoryDesc->category_desc = ucwords(strtolower($categoryDesc));
                $oldCategoryDesc->remarks = $remarks;
                $oldCategoryDesc->save();

                return response()->json([
                    'status' => 'success',
                    'message' => 'Temporary Deferral Category updated successfully',
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

    public function deleteTemporaryDeferralCategory(Request $request)
    {

        $user = Auth::user();
        $userId = $user->user_id;

        try {
            $request->validate([
                'categories_id' => ['required'],
            ]);

            $ip = file_get_contents('https://api.ipify.org');
            $ch = curl_init('http://ipwho.is/' . $ip);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HEADER, false);

            $ipwhois = json_decode(curl_exec($ch), true);

            curl_close($ch);

            $categoryId = $request->input('categories_id');

            $oldCategory = Category::where('categories_id', $categoryId)->first();

            if (!$oldCategory) {

                return response()->json([
                    'status' => 'error',
                    'message' => 'Venue not found',
                ], 400);
            } else {

                AuditTrail::create([
                    'user_id'    => $userId,
                    'module'     => 'Settings',
                    'action'     => 'Delete Temporary Category | ' . $oldCategory->categories_id,
                    'status'     => 'success',
                    'ip_address' => $ipwhois['ip'],
                    'region'     => $ipwhois['region'],
                    'city'       => $ipwhois['city'],
                    'postal'     => $ipwhois['postal'],
                    'latitude'   => $ipwhois['latitude'],
                    'longitude'  => $ipwhois['longitude'],
                ]);

                $oldCategory->status = 1;
                $oldCategory->save();

                return response()->json([
                    'status' => 'success',
                    'message' => 'Successfully removed temporary Category',
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

    public function maintenance(Request $request)
    {
        $user = Auth::user();
        $userId = $user->user_id;

        try {

            $request->validate([
                'toggle' => ['required']
            ]);

            $switch = $request->input('toggle');
            $maintenance = Setting::where('setting_desc', 'maintenance')->first();
            if ($switch == 1) {
                $ip = file_get_contents('https://api.ipify.org');
                $ch = curl_init('http://ipwho.is/' . $ip);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HEADER, false);

                $ipwhois = json_decode(curl_exec($ch), true);

                curl_close($ch);
                AuditTrail::create([
                    'user_id'    => $userId,
                    'module'     => 'Settings',
                    'action'     => 'Switch On Maintenance',
                    'status'     => 'success',
                    'ip_address' => $ipwhois['ip'],
                    'region'     => $ipwhois['region'],
                    'city'       => $ipwhois['city'],
                    'postal'     => $ipwhois['postal'],
                    'latitude'   => $ipwhois['latitude'],
                    'longitude'  => $ipwhois['longitude'],
                ]);

                /// Assuming you are using Laravel Sanctum for token management
                // $currentTokenId = $request->user()->currentAccessToken()->id; // Get the current token ID
                // DB::table('personal_access_tokens') // Use your actual tokens table name
                //     ->where('tokenable_id', '!=', $userId) // All other user tokens
                //     ->orWhere(function ($query) use ($userId, $currentTokenId) {
                //         $query->where('tokenable_id', $userId)
                //             ->where('id', '!=', $currentTokenId); // Other tokens of the current user
                //     })
                //     ->delete(); // Delete the tokens


                $maintenance->setting_value = 1;
                $maintenance->save();

                return response()->json([
                    'status'  => 'success',
                    'message' => 'Maintenance mode is on',
                ]);
            } else {
                $ip = file_get_contents('https://api.ipify.org');
                $ch = curl_init('http://ipwho.is/' . $ip);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HEADER, false);

                $ipwhois = json_decode(curl_exec($ch), true);

                curl_close($ch);
                AuditTrail::create([
                    'user_id'    => $userId,
                    'module'     => 'Settings',
                    'action'     => 'Switch Off Maintenance',
                    'status'     => 'success',
                    'ip_address' => $ipwhois['ip'],
                    'region'     => $ipwhois['region'],
                    'city'       => $ipwhois['city'],
                    'postal'     => $ipwhois['postal'],
                    'latitude'   => $ipwhois['latitude'],
                    'longitude'  => $ipwhois['longitude'],
                ]);

                $maintenance->setting_value = 0;
                $maintenance->save();
                return response()->json([
                    'status'  => 'success',
                    'message' => 'Maintenance mode is off',
                ]);
            }
        } catch (ValidationException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Validation failed',
                'errors'  => $e->validator->errors(),
            ], 400);
        }
    }

    public function getMaintenanceStatus()
    {
        $maintenance = Setting::where('setting_desc', 'maintenance')->first();
        $status = $maintenance->setting_value;

        return response()->json([
            'status' => 'success',
            'maintenance' => $status,
        ]);
    }
}
