<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditTrail;
use App\Models\BloodBag;
use App\Models\Deferral;
use App\Models\DeferralCategory;
use App\Models\User;
use App\Models\UserDetail;
use App\Rules\EmailUpdateProfile;
use App\Rules\MobileUpdateProfile;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Dompdf\Dompdf;
use Illuminate\Support\Facades\DB;
use App\Rules\ValidateUniqueEmail;
use App\Rules\ValidateUniqueMobile;
use Illuminate\Support\Facades\Hash;
use App\Models\Galloner;
use Illuminate\Support\Facades\Mail;
use App\Mail\RegistrationMail;

class UserListController extends Controller
{
    public function getUserDetails()
    {
        $userDetails = DB::table('user_details')
            ->join('users', 'user_details.user_id', '=', 'users.user_id')
            ->join('galloners', 'user_details.user_id', '=', 'galloners.user_id')
            ->join('region', 'user_details.region', '=', 'region.regCode')
            ->join('province', 'user_details.province', '=', 'province.provCode')
            ->join('municipality', 'user_details.municipality', '=', 'municipality.citymunCode')
            ->join('barangay', 'user_details.barangay', '=', 'barangay.brgyCode')
            ->leftJoin('blood_bags', 'user_details.user_id', '=', 'blood_bags.user_id')
            ->leftJoin(DB::raw('(SELECT user_id, MAX(created_at) AS latest_timestamp FROM blood_bags GROUP BY user_id) AS latest_blood_bags'), function ($join) {
                $join->on('blood_bags.user_id', '=', 'latest_blood_bags.user_id')
                    ->on('blood_bags.created_at', '=', 'latest_blood_bags.latest_timestamp');
            })
            ->select(
                'users.user_id',
                'users.mobile',
                'users.email',
                'user_details.donor_no',
                'user_details.first_name',
                'user_details.middle_name',
                'user_details.last_name',
                'user_details.blood_type',
                'user_details.sex',
                'user_details.street',
                'user_details.region',
                'user_details.province',
                'user_details.municipality',
                'user_details.barangay',
                'user_details.dob',
                'user_details.remarks',
                'galloners.badge',
                'galloners.donate_qty',
                'region.regDesc',
                'province.provDesc',
                'municipality.citymunDesc',
                'barangay.brgyDesc',
                DB::raw('IFNULL(MAX(blood_bags.date_donated), NULL) AS latest_date_donated')
            )
            ->groupBy(
                'users.user_id',
                'users.mobile',
                'users.email',
                'user_details.donor_no',
                'user_details.first_name',
                'user_details.middle_name',
                'user_details.last_name',
                'user_details.blood_type',
                'user_details.sex',
                'user_details.street',
                'user_details.region',
                'user_details.province',
                'user_details.municipality',
                'user_details.barangay',
                'user_details.dob',
                'user_details.remarks',
                'galloners.badge',
                'galloners.donate_qty',
                'region.regDesc',
                'province.provDesc',
                'municipality.citymunDesc',
                'barangay.brgyDesc',
            )
            ->where('user_details.status', 0)
            ->where('users.isAdmin', 0)
            ->orderBy('user_details.user_id', 'desc')
            ->get();

        if ($userDetails->isEmpty()) {
            return response()->json([
                'status' => 'success',
                'message' => 'No donor found.'
            ], 200);
        } else {
            return response()->json([
                'status' => 'success',
                'data' => $userDetails
            ], 200);
        }
    }


    public function exportUserDetailsAsPdf(Request $request)
    {
        $user = getAuthenticatedUserId();
        $userId = $user->user_id;

        $userDetails = DB::table('user_details')
            ->join('users', 'user_details.user_id', '=', 'users.user_id')
            ->join('galloners', 'user_details.user_id', '=', 'galloners.user_id')
            ->leftJoin('blood_bags', 'user_details.user_id', '=', 'blood_bags.user_id')
            ->leftJoin(DB::raw('(SELECT user_id, MAX(created_at) AS latest_timestamp FROM blood_bags GROUP BY user_id) AS latest_blood_bags'), function ($join) {
                $join->on('blood_bags.user_id', '=', 'latest_blood_bags.user_id')
                    ->on('blood_bags.created_at', '=', 'latest_blood_bags.latest_timestamp');
            })
            ->select(
                'users.user_id',
                'users.mobile',
                'users.email',
                'user_details.donor_no',
                'user_details.first_name',
                'user_details.middle_name',
                'user_details.last_name',
                'user_details.blood_type',
                'user_details.sex',
                'user_details.street',
                'user_details.region',
                'user_details.province',
                'user_details.municipality',
                'user_details.barangay',
                'user_details.dob',
                'user_details.remarks',
                'galloners.badge',
                'galloners.donate_qty',
                DB::raw('IFNULL(MAX(blood_bags.date_donated), NULL) AS latest_date_donated')
            )
            ->groupBy(
                'users.user_id',
                'users.mobile',
                'users.email',
                'user_details.donor_no',
                'user_details.first_name',
                'user_details.middle_name',
                'user_details.last_name',
                'user_details.blood_type',
                'user_details.sex',
                'user_details.street',
                'user_details.region',
                'user_details.province',
                'user_details.municipality',
                'user_details.barangay',
                'user_details.dob',
                'user_details.remarks',
                'galloners.badge',
                'galloners.donate_qty'
            )
            ->where('user_details.status', 0)
            ->where('users.isAdmin', 0)
            ->orderBy('user_details.user_id', 'desc')
            ->get();

        $ip = file_get_contents('https://api.ipify.org');
        $ch = curl_init('http://ipwho.is/' . $ip);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);

        $ipwhois = json_decode(curl_exec($ch), true);

        curl_close($ch);
        AuditTrail::create([
            'user_id'    => $userId,
            'module'     => 'User List',
            'action'     => 'Export Users List as PDF',
            'status'     => 'success',
            'ip_address' => $ipwhois['ip'],
            'region'     => $ipwhois['region'],
            'city'       => $ipwhois['city'],
            'postal'     => $ipwhois['postal'],
            'latitude'   => $ipwhois['latitude'],
            'longitude'  => $ipwhois['longitude'],
        ]);

        $totalUserDetails = $userDetails->count();
        $dateNow = new \DateTime();
        $formattedDate = $dateNow->format('F j, Y g:i A');

        $pdf = new Dompdf();
        $pdf->setPaper('A4', 'landscape');
        $html = view('user-details', ['userDetails' => $userDetails, 'totalUsers' => $totalUserDetails, 'dateNow' => $formattedDate])->render();
        $pdf->loadHtml($html);
        $pdf->render();

        // Return the PDF as a response
        return response($pdf->output(), 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="user-details.pdf"');
    }

    public function searchUsers(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'searchInput' => 'required',
            ]);

            $searchInput = str_replace(' ', '', $request->input('searchInput'));
            $userDetails = DB::table('user_details')
                ->join('users', 'user_details.user_id', '=', 'users.user_id')
                ->join('galloners', 'user_details.user_id', '=', 'galloners.user_id')
                ->leftJoin('blood_bags', 'user_details.user_id', '=', 'blood_bags.user_id')
                ->leftJoin(DB::raw('(SELECT user_id, MAX(created_at) AS latest_timestamp FROM blood_bags GROUP BY user_id) AS latest_blood_bags'), function ($join) {
                    $join->on('blood_bags.user_id', '=', 'latest_blood_bags.user_id')
                        ->on('blood_bags.created_at', '=', 'latest_blood_bags.latest_timestamp');
                })
                ->select(
                    'users.user_id',
                    'users.mobile',
                    'users.email',
                    'user_details.donor_no',
                    'user_details.first_name',
                    'user_details.middle_name',
                    'user_details.last_name',
                    'user_details.blood_type',
                    'user_details.sex',
                    'user_details.street',
                    'user_details.region',
                    'user_details.province',
                    'user_details.municipality',
                    'user_details.barangay',
                    'user_details.dob',
                    'user_details.remarks',
                    'galloners.badge',
                    'galloners.donate_qty',
                    DB::raw('IFNULL(MAX(blood_bags.date_donated), NULL) AS latest_date_donated')
                )
                ->groupBy(
                    'users.user_id',
                    'users.mobile',
                    'users.email',
                    'user_details.donor_no',
                    'user_details.first_name',
                    'user_details.middle_name',
                    'user_details.last_name',
                    'user_details.blood_type',
                    'user_details.sex',
                    'user_details.street',
                    'user_details.region',
                    'user_details.province',
                    'user_details.municipality',
                    'user_details.barangay',
                    'user_details.dob',
                    'user_details.remarks',
                    'galloners.badge',
                    'galloners.donate_qty'
                )
                ->where('user_details.status', 0)
                ->where('users.isAdmin', 0)
                ->where(function ($query) use ($searchInput) {
                    $query->where('users.mobile', 'LIKE', '%' . $searchInput . '%')
                        ->orWhere('users.email', 'LIKE', '%' . $searchInput . '%')
                        ->orWhere('user_details.donor_no', 'LIKE', '%' . $searchInput . '%')
                        ->orWhere('user_details.first_name', 'LIKE', '%' . $searchInput . '%')
                        ->orWhere('user_details.middle_name', 'LIKE', '%' . $searchInput . '%')
                        ->orWhere('user_details.last_name', 'LIKE', '%' . $searchInput . '%')
                        ->orWhere('user_details.blood_type', 'LIKE', '%' . $searchInput . '%')
                        ->orWhere('user_details.dob', 'LIKE', '%' . $searchInput . '%');
                })
                ->orderBy('user_details.user_id', 'desc')
                ->paginate(8);


            if ($userDetails->isEmpty()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'No user found.'
                ], 200);
            } else {
                return response()->json([
                    'status' => 'success',
                    'data' => $userDetails
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

    public function getDeferralCategories()
    {
        $tempCategories = DeferralCategory::where('deferral_type_id', 1)->get();
        $permaCategories = DeferralCategory::where('deferral_type_id', 2)->get();

        return response()->json([
            'status' => 'success',
            'tempCategories' => $tempCategories,
            'permaCategories' => $permaCategories
        ]);
    }

    public function moveToDeferral(Request $request)
    {
        $user = getAuthenticatedUserId();
        $userId = $user->user_id;


        try {
            $validatedData = $request->validate([
                'user_id'           => 'required',
                'deferral_type_id'  => 'required',
                'categories_id'     => 'required',
                'remarks'           => '',
                'duration'          => ['numeric', 'min:1'],
                'venue'             => 'required',
                'date_deferred'     => 'required',
                'donation_type'     => 'required'
            ], [
                'duration.min' => 'Minimum duration is 1 day.',
            ]);

            $date_deferred = Carbon::parse($validatedData['date_deferred']);
            $ip = file_get_contents('https://api.ipify.org');
            $ch = curl_init('http://ipwho.is/' . $ip);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HEADER, false);
            $ipwhois = json_decode(curl_exec($ch), true);
            curl_close($ch);

            $user_detail = UserDetail::where('user_id', $validatedData['user_id'])->first();
            $blood_bag = BloodBag::where('user_id', $validatedData['user_id'])->first();

            $lastRecord = BloodBag::where('user_id', $validatedData['user_id'])->latest('date_donated')->first();

            if ($lastRecord) {
                $lastDonationDate = Carbon::parse($lastRecord->date_donated);
                $currentDonationDate = Carbon::parse($validatedData['date_deferred']);
                $minDonationInterval = Carbon::parse($lastDonationDate)->addDays(90)->format('Y-m-d');


                if ($currentDonationDate <= $lastDonationDate) {
                    AuditTrail::create([
                        'user_id'    => $userId,
                        'module'     => 'User List',
                        'action'     => 'Move to Deferral | serial no: ' .  $user_detail->donor_no,
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
                        'message'      => 'The donor cannot be deferred as they are currently ineligible to donate.',
                    ], 400);
                } elseif ($currentDonationDate < $minDonationInterval) {

                    AuditTrail::create([
                        'user_id'    => $userId,
                        'module'     => 'User List',
                        'action'     => 'Move to Deferral | donor no: ' . $user_detail->donor_no,
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
                        'message'      => 'The donor cannot be deferred as they are currently ineligible to donate.',
                    ], 400);
                } else {

                    if ($user_detail->remarks == 1 || $user_detail->remarks == 2) {
                        return response()->json([
                            'status' => 'error',
                            'message' => 'User already has a deferral.'
                        ], 400);
                    } else {

                        if ($validatedData['deferral_type_id'] === '1') {
                            $user_detail->remarks = 1;
                            $user_detail->save();

                            // $deferredStartDate = now();
                            $deferredDuration = $validatedData['duration'];

                            $endDateOfDeferral = Carbon::parse($date_deferred)
                                ->addDays($deferredDuration)
                                ->addDay()
                                ->toDateString();

                            Deferral::create([
                                'user_id'           => $validatedData['user_id'],
                                'categories_id'     => $validatedData['categories_id'],
                                'deferral_type_id'   => $validatedData['deferral_type_id'],
                                'deferred_duration' => $validatedData['duration'],
                                'date_deferred'     => $validatedData['date_deferred'],
                                'venue'             => $validatedData['venue'],
                                'end_date'          => $endDateOfDeferral,
                                'donation_type_id'     => $validatedData['donation_type']
                            ]);

                            AuditTrail::create([
                                'user_id'    => $userId,
                                'module'     => 'Donor List',
                                'action'     => 'Move to Temporary Deferral | donor no: ' . $user_detail->donor_no,
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
                                'message' => 'User added to temporary deferral.',
                            ]);
                        } else {
                            $user_detail->remarks = 2;
                            $user_detail->save();

                            Deferral::create([
                                'user_id'           => $validatedData['user_id'],
                                'categories_id'     => $validatedData['categories_id'],
                                'deferral_type_id'   => $validatedData['deferral_type_id'],
                                'venue'             => $validatedData['venue'],
                                'date_deferred'     => $validatedData['date_deferred'],
                                'donation_type_id'     => $validatedData['donation_type']

                            ]);

                            AuditTrail::create([
                                'user_id'    => $userId,
                                'module'     => 'Donor List',
                                'action'     => 'Move to Permanent Deferral | donor no: ' . $user_detail->donor_no,
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
                                'message' => 'User added to permanent deferral.',
                            ]);
                        }
                    }
                }
            } else {
                if ($user_detail->remarks == 1 || $user_detail->remarks == 2) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'User already has a deferral.'
                    ], 400);
                } else {

                    if ($validatedData['deferral_type_id'] === '1') {
                        $user_detail->remarks = 1;
                        $user_detail->save();

                        // $deferredStartDate = now();
                        $deferredDuration = $validatedData['duration'];

                        $endDateOfDeferral = Carbon::parse($date_deferred)
                            ->addDays($deferredDuration)
                            ->addDay()
                            ->toDateString();

                        Deferral::create([
                            'user_id'           => $validatedData['user_id'],
                            'categories_id'     => $validatedData['categories_id'],
                            'deferral_type_id'   => $validatedData['deferral_type_id'],
                            'deferred_duration' => $validatedData['duration'],
                            'date_deferred'     => $validatedData['date_deferred'],
                            'venue'             => $validatedData['venue'],
                            'end_date'          => $endDateOfDeferral,
                            'donation_type_id'     => $validatedData['donation_type']
                        ]);

                        AuditTrail::create([
                            'user_id'    => $userId,
                            'module'     => 'Donor List',
                            'action'     => 'Move to Temporary Deferral | donor no: ' . $user_detail->donor_no,
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
                            'message' => 'User added to temporary deferral.',
                        ]);
                    } else {
                        $user_detail->remarks = 2;
                        $user_detail->save();

                        Deferral::create([
                            'user_id'           => $validatedData['user_id'],
                            'categories_id'     => $validatedData['categories_id'],
                            'deferral_type_id'   => $validatedData['deferral_type_id'],
                            'venue'             => $validatedData['venue'],
                            'date_deferred'     => $validatedData['date_deferred'],
                            'donation_type_id'     => $validatedData['donation_type']

                        ]);

                        AuditTrail::create([
                            'user_id'    => $userId,
                            'module'     => 'Donor List',
                            'action'     => 'Move to Permanent Deferral | donor no: ' . $user_detail->donor_no,
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
                            'message' => 'User added to permanent deferral.',
                        ]);
                    }
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



    public function getTemporaryDeferral(Request $request)
    {
        try {
            $category = $request->input('category');
            $remarks = $request->input('remarks');

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

            $userDetails = UserDetail::join('users', 'user_details.user_id', '=', 'users.user_id')
                ->join('deferrals', 'user_details.user_id', '=', 'deferrals.user_id')
                ->join('categories', 'categories.categories_id', '=', 'deferrals.categories_id')
                ->where('deferrals.status', 0)
                ->where('user_details.remarks', 1)
                ->where('user_details.status', 0)
                ->select('users.mobile', 'users.email', 'user_details.*', 'deferrals.*', 'categories.*');


            if ($userDetails->count() === 0) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'No donor has been temporarily deferred.'
                ], 200);
            } else {

                if ($category == 'All' && $remarks == 'All') {
                    $totalCount = $userDetails->count();
                    $userDetails = $userDetails->orderBy('deferrals.date_deferred')->get();
                } elseif ($category == 'All') {
                    if ($remarks) {
                        $userDetails->where('categories.remarks', $remarks);
                    }
                    $totalCount = $userDetails->count();
                    $userDetails = $userDetails->orderBy('deferrals.date_deferred')->get();
                } elseif ($remarks == 'All') {
                    $userDetails->where('categories.category_desc', $category);
                    $totalCount = $userDetails->count();
                    $userDetails = $userDetails->orderBy('deferrals.date_deferred')->get();
                } else {
                    $userDetails->where('categories.category_desc', $category);
                    $userDetails->where('categories.remarks', $remarks);
                    $totalCount = $userDetails->count();
                    $userDetails = $userDetails->orderBy('deferrals.date_deferred')->get();
                }

                return response()->json([
                    'status' => 'success',
                    'data' => $userDetails,
                    'total_count' => $totalCount
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

    public function exportTemporaryDeferral(Request $request)
    {
        $user = getAuthenticatedUserId();
        $userId = $user->user_id;
        try {
            $category = $request->input('category');
            $remarks = $request->input('remarks');

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

            $userDetails = UserDetail::join('users', 'user_details.user_id', '=', 'users.user_id')
                ->join('deferrals', 'user_details.user_id', '=', 'deferrals.user_id')
                ->join('categories', 'categories.categories_id', '=', 'deferrals.categories_id')
                ->where('deferrals.status', 0)
                ->where('user_details.remarks', 1)
                ->where('user_details.status', 0)
                ->select('users.mobile', 'users.email', 'user_details.*', 'deferrals.*', 'categories.*');


            if ($userDetails->count() === 0) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'No donor has been temporarily deferred.'
                ], 200);
            } else {

                if ($category == 'All' && $remarks == 'All') {
                    $totalCount = $userDetails->count();
                    $userDetails = $userDetails->orderBy('deferrals.date_deferred')->get();
                } elseif ($category == 'All') {
                    if ($remarks) {
                        $userDetails->where('categories.remarks', $remarks);
                    }
                    $totalCount = $userDetails->count();
                    $userDetails = $userDetails->orderBy('deferrals.date_deferred')->get();
                } elseif ($remarks == 'All') {
                    $userDetails->where('categories.category_desc', $category);
                    $totalCount = $userDetails->count();
                    $userDetails = $userDetails->orderBy('deferrals.date_deferred')->get();
                } else {
                    $userDetails->where('categories.category_desc', $category);
                    $userDetails->where('categories.remarks', $remarks);
                    $totalCount = $userDetails->count();
                    $userDetails = $userDetails->orderBy('deferrals.date_deferred')->get();
                }

                $ip = file_get_contents('https://api.ipify.org');
                $ch = curl_init('http://ipwho.is/' . $ip);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HEADER, false);

                $ipwhois = json_decode(curl_exec($ch), true);

                curl_close($ch);
                AuditTrail::create([
                    'user_id'    => $userId,
                    'module'     => 'Deferral List',
                    'action'     => 'Export Temporary Deferral as PDF',
                    'status'     => 'success',
                    'ip_address' => $ipwhois['ip'],
                    'region'     => $ipwhois['region'],
                    'city'       => $ipwhois['city'],
                    'postal'     => $ipwhois['postal'],
                    'latitude'   => $ipwhois['latitude'],
                    'longitude'  => $ipwhois['longitude'],
                ]);

                $totalUserDetails = $userDetails->count();
                $dateNow = new \DateTime();
                $formattedDate = $dateNow->format('F j, Y g:i A');

                $pdf = new Dompdf();
                $pdf->setPaper('A4', 'landscape');
                $html = view('temporary-deferral', ['userDetails' => $userDetails, 'totalUsers' => $totalUserDetails, 'dateNow' => $formattedDate])->render();
                $pdf->loadHtml($html);
                $pdf->render();

                // Return the PDF as a response
                return response($pdf->output(), 200)
                    ->header('Content-Type', 'application/pdf')
                    ->header('Content-Disposition', 'attachment; filename="temporary-deferral.pdf"');
            }
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->validator->errors(),
            ], 400);
        }
    }


    public function getPermanentDeferral(Request $request)
    {
        try {
            $category = $request->input('category');

            $userDetails = UserDetail::join('users', 'user_details.user_id', '=', 'users.user_id')
                ->join('deferrals', 'user_details.user_id', '=', 'deferrals.user_id')
                ->join('categories', 'categories.categories_id', '=', 'deferrals.categories_id')
                ->where('user_details.remarks', 2)
                ->where('user_details.status', 0)
                ->select('users.mobile', 'users.email', 'user_details.*', 'deferrals.*', 'categories.*');

            if ($category != 'All') {
                $userDetails->where('categories.category_desc', $category);
            }

            $userDetails = $userDetails->get();

            // if ($userDetails->isEmpty()) {
            //     return response()->json([
            //         'status' => 'success',
            //         'message' => 'No donor has been permanent deferred.'
            //     ], 200);
            // } else {
            return response()->json([
                'status' => 'success',
                'data' => $userDetails
            ], 200);
            // }
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->validator->errors(),
            ], 400);
        }
    }

    public function exportPermanentDeferral(Request $request)
    {
        $user = getAuthenticatedUserId();
        $userId = $user->user_id;

        try {
            $category = $request->input('category');

            $userDetails = UserDetail::join('users', 'user_details.user_id', '=', 'users.user_id')
                ->join('deferrals', 'user_details.user_id', '=', 'deferrals.user_id')
                ->join('categories', 'categories.categories_id', '=', 'deferrals.categories_id')
                ->where('user_details.remarks', 2)
                ->where('user_details.status', 0)
                ->select('users.mobile', 'users.email', 'user_details.*', 'deferrals.*', 'categories.*');

            if ($category != 'All') {
                $userDetails->where('categories.category_desc', $category);
            }

            $userDetails = $userDetails->get();


            $ip = file_get_contents('https://api.ipify.org');
            $ch = curl_init('http://ipwho.is/' . $ip);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HEADER, false);

            $ipwhois = json_decode(curl_exec($ch), true);

            curl_close($ch);
            AuditTrail::create([
                'user_id'    => $userId,
                'module'     => 'Deferral List',
                'action'     => 'Export Permanent Deferral as PDF',
                'status'     => 'success',
                'ip_address' => $ipwhois['ip'],
                'region'     => $ipwhois['region'],
                'city'       => $ipwhois['city'],
                'postal'     => $ipwhois['postal'],
                'latitude'   => $ipwhois['latitude'],
                'longitude'  => $ipwhois['longitude'],
            ]);

            $totalUserDetails = $userDetails->count();
            $dateNow = new \DateTime();
            $formattedDate = $dateNow->format('F j, Y g:i A');

            $pdf = new Dompdf();
            $pdf->setPaper('A4', 'landscape');
            $html = view('permanent-deferral', ['userDetails' => $userDetails, 'totalUsers' => $totalUserDetails, 'dateNow' => $formattedDate])->render();
            $pdf->loadHtml($html);
            $pdf->render();

            return response($pdf->output(), 200)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="temporary-deferral.pdf"');

        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->validator->errors(),
            ], 400);
        }
    }



    public function editUserDetails(Request $request)
    {
        $user = getAuthenticatedUserId();
        $userId = $user->user_id;

        try {
            $validatedData = $request->validate([
                'user_id'               => ['required', 'exists:users,user_id'],
                'first_name'            => ['required', 'string'],
                'middle_name'           => ['nullable', 'string'],
                'last_name'             => ['required', 'string'],
                'email'                 => ['required', 'string', new EmailUpdateProfile],
                'mobile'                => ['required', 'string', new MobileUpdateProfile],
                'sex'                   => ['required'],
                'dob'                   => ['required'],
                'blood_type'            => ['required'],
                'street'                => ['required', 'string'],
                'region'                => ['required'],
                'province'              => ['required'],
                'municipality'          => ['required'],
                'barangay'              => ['required'],
            ]);

            $ip = file_get_contents('https://api.ipify.org');
            $ch = curl_init('http://ipwho.is/' . $ip);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HEADER, false);

            $ipwhois = json_decode(curl_exec($ch), true);

            curl_close($ch);

            $userDetails = UserDetail::where('user_id', $validatedData['user_id'])->first();
            $userDetails->first_name = ucwords(strtolower($validatedData['first_name']));
            $userDetails->middle_name = ucwords(strtolower($validatedData['middle_name']));
            $userDetails->last_name = ucwords(strtolower($validatedData['last_name']));
            $userDetails->sex = $validatedData['sex'];
            $userDetails->dob = $validatedData['dob'];
            $userDetails->blood_type = $validatedData['blood_type'];
            $userDetails->street = ucwords(strtolower($validatedData['street']));
            $userDetails->region = $validatedData['region'];
            $userDetails->province = $validatedData['province'];
            $userDetails->municipality = $validatedData['municipality'];
            $userDetails->barangay = $validatedData['barangay'];
            $userDetails->update();

            $userAuthDetails = User::where('user_id', $validatedData['user_id'])->first();
            $userAuthDetails->mobile = $validatedData['mobile'];
            $userAuthDetails->email = $validatedData['email'];
            $userAuthDetails->update();

            AuditTrail::create([
                'user_id'    => $userId,
                'module'     => 'User List',
                'action'     => 'Edit User Details | donor no: ' . $userDetails->donor_no,
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

    public function addUsers(Request $request)
    {
        $user = getAuthenticatedUserId();
        $userId = $user->user_id;

        try {
            $request->validate([
                'email'                 => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
                'mobile'                => ['required', 'numeric', 'digits:11', 'unique:users,mobile'],
                'first_name'            => ['required', 'string'],
                'middle_name'           => ['required', 'string'],
                'last_name'             => ['required', 'string'],
                'dob'                   => ['required', 'date', 'before_or_equal:' . now()->subYears(16)->format('Y-m-d')],
                'sex'                   => ['required'],
                'blood_type'            => ['required'],
                'occupation'            => ['required', 'string'],
                'street'                => ['required', 'string'],
                'region'                => ['required'],
                'province'              => ['required'],
                'municipality'          => ['required'],
                'barangay'              => ['required'],
                'postalcode'            => ['required', 'integer'],
            ], [
                'mobile.digits' => 'Invalid mobile number',
                'before_or_equal'   => 'You must at least 17 years old to register',
            ]);

            $email = $request->email;
            $mobile = $request->mobile;
            $carbonDob = Carbon::parse($request->dob);
            $password = strtolower($request->last_name) . $carbonDob->format('mdY');

            $user = User::create([
                'email' => $email,
                'mobile' => $mobile,
                'isAdmin' => 0,
                'password' => Hash::make($password),
            ]);

            $user_id = $user->user_id;
            $donorNo = mt_rand(10000000, 99999999);

            // Ensure the generated donor number is unique in the database
            while (UserDetail::where('donor_no', $donorNo)->exists()) {
                $donorNo = mt_rand(10000000, 99999999); // Regenerate if the number already exists
            }
            UserDetail::create([
                'user_id' => $user_id,
                'donor_no' => $donorNo,
                'first_name' => ucwords(strtolower($request->first_name)),
                'middle_name' => ucwords(strtolower($request->middle_name)),
                'last_name' => ucwords(strtolower($request->last_name)),
                'sex' => $request->sex,
                'dob' => $request->dob,
                'blood_type' => $request->blood_type,
                'occupation' => ucwords(strtolower($request->occupation)),
                'street' => ucwords(strtolower($request->street)),
                'region' => $request->region,
                'province' => $request->province,
                'municipality' => $request->municipality,
                'barangay' => $request->barangay,
                'postalcode' => $request->postalcode,
            ]);

            Galloner::create([
                'user_id'    => $user_id,
            ]);

            // Send email notification
            // Mail::to($user->email)->send(new RegistrationMail($user));

            return response()->json([
                'status'            => 'success',
                'message'           => 'Registration Complete.',
                'next_step'         => '0',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'status'    => 'error',
                'message'   => 'Validation failed',
                'errors'    => $e->validator->errors(),
            ], 400);
        }
    }
}
