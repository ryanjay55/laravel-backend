<?php

namespace App\Http\Controllers\Api\Admin\Inventory;

use App\Http\Controllers\Controller;
use App\Models\AuditTrail;
use App\Models\Deferral;
use App\Models\LastUpdate;
use App\Models\Hospital;
use App\Models\PatientReceiver;
use App\Models\UserDetail;
use App\Models\User;

use Illuminate\Http\Request;
use Dompdf\Dompdf;
use App\Models\BloodBag;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Mail\DispensedEmail;
use Illuminate\Support\Facades\Mail; 
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
            $ch = curl_init('http://ipwho.is/' . $ip);
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
                $bloodBag->update(['date_stored' => Carbon::now()]);

                LastUpdate::updateOrInsert(
                    [],
                    ['date_update' => now()]
                );


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
            $ch = curl_init('http://ipwho.is/' . $ip);
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
                    $bloodBag->update(['isTested' => 1]);
                    $bloodBag->update(['date_stored' => Carbon::now()]);

                    LastUpdate::updateOrInsert(
                        [],
                        ['date_update' => now()]
                    );
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
            ->get();

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
                ->where('blood_bags.isDisposed', 0)
                ->where('blood_bags.isUsed', 0)
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
                ->where('blood_bags.isUsed', 0)
                ->select('blood_bags.blood_bags_id', 'blood_bags.serial_no', 'user_details.first_name', 'user_details.last_name', 'user_details.blood_type', 'user_details.donor_no', 'blood_bags.date_donated', 'blood_bags.expiration_date')
                ->orderBy('blood_bags.expiration_date')
                ->where(function ($query) use ($searchInput) {
                    $query->where('blood_bags.serial_no', 'LIKE', '%' . $searchInput . '%')
                        // ->orWhere('user_details.first_name', 'LIKE', '%' . $searchInput . '%')
                        // ->orWhere('user_details.last_name', 'LIKE', '%' . $searchInput . '%')
                        ->orWhere('user_details.blood_type', 'LIKE', '%' . $searchInput . '%')
                        ->orWhere('user_details.donor_no', 'LIKE', '%' . $searchInput . '%')
                        ->orWhere('blood_bags.date_donated', 'LIKE', '%' . $searchInput . '%')
                        ->orWhere('blood_bags.expiration_date', 'LIKE', '%' . $searchInput . '%');
                })
                ->paginate(8);
            // dd($stocks);

            if ($stocks->isEmpty()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No blood bag in inventory',
                    'total_count' => 0
                ]);
            } else {
                $stocks->each(function ($bloodBag) {
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
                    ->where('blood_bags.isDisposed', 0)
                    ->where('blood_bags.isUsed', 0)
                    ->count();

                return response()->json([
                    'status' => 'success',
                    'data' => $stocks,
                    'total_count' => $totalCount
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

    public function exportStocksAsPdf(Request $request)
    {
        $bloodType = $request->input('blood_type');
        $startDate = $request->input('startDate');
        $endDate = $request->input('endDate');

        $user = getAuthenticatedUserId();
        $userId = $user->user_id;

        $inventory = BloodBag::join('user_details', 'blood_bags.user_id', '=', 'user_details.user_id')
            ->where('blood_bags.isStored', 1)
            ->where('blood_bags.isExpired', 0)
            ->where('user_details.remarks', 0)
            ->where('blood_bags.isDisposed', 0)
            ->where('blood_bags.isUsed', 0);

        // Apply additional filters based on request parameters
        if ($bloodType !== 'All') {
            $inventory->where('user_details.blood_type', $bloodType);
        }

        if (!empty($startDate)) {
            $inventory->where('blood_bags.expiration_date', '>=', $startDate);
        }

        if (!empty($endDate)) {
            $inventory->where('blood_bags.expiration_date', '<=', $endDate);
        }

        $inventory = $inventory->select('blood_bags.blood_bags_id', 'blood_bags.serial_no', 'user_details.first_name', 'user_details.last_name', 'user_details.blood_type', 'user_details.donor_no', 'blood_bags.date_donated', 'blood_bags.expiration_date')
            ->orderBy('blood_bags.expiration_date')
            ->get();
        //dd($inventory);

        $totalCount = $inventory->count();

        $ip = file_get_contents('https://api.ipify.org');
        $ch = curl_init('http://ipwho.is/' . $ip);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);

        $ipwhois = json_decode(curl_exec($ch), true);

        curl_close($ch);

        AuditTrail::create([
            'user_id'    => $userId,
            'module'     => 'Inventory',
            'action'     => 'Export Blood Stocks as PDF',
            'status'     => 'success',
            'ip_address' => $ipwhois['ip'],
            'region'     => $ipwhois['region'],
            'city'       => $ipwhois['city'],
            'postal'     => $ipwhois['postal'],
            'latitude'   => $ipwhois['latitude'],
            'longitude'  => $ipwhois['longitude'],
        ]);

        $totalBloodBags = $totalCount;
        $dateNow = new \DateTime();
        $formattedDate = $dateNow->format('F j, Y g:i A');

        $pdf = new Dompdf();
        $pdf->setPaper('A4', 'landscape');
        $html = view('stocks-details', ['inventory' => $inventory, 'totalBloodBags' => $totalBloodBags, 'dateNow' => $formattedDate])->render();
        $pdf->loadHtml($html);
        $pdf->render();

        // Return the PDF as a response
        return response($pdf->output(), 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="donor-list.pdf"');
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
                ->where('blood_bags.isUsed', 0)
                ->select('blood_bags.blood_bags_id', 'blood_bags.serial_no', 'blood_bags.priority', 'blood_bags.remaining_days', 'user_details.first_name', 'user_details.last_name', 'user_details.blood_type', 'user_details.donor_no', 'blood_bags.date_donated', 'blood_bags.expiration_date');

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
                ->select('blood_bags.blood_bags_id', 'blood_bags.serial_no', 'blood_bags.priority', 'blood_bags.remaining_days', 'user_details.first_name', 'user_details.last_name', 'user_details.blood_type', 'user_details.donor_no', 'blood_bags.date_donated', 'blood_bags.expiration_date');

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

    public function moveToCollected(Request $request)
    {
        $user = getAuthenticatedUserId();
        $userId = $user->user_id;

        try {

            $validatedData = $request->validate([
                'serial_no'     => 'required',
            ]);

            $ip = file_get_contents('https://api.ipify.org');
            $ch = curl_init('http://ipwho.is/' . $ip);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HEADER, false);

            $ipwhois = json_decode(curl_exec($ch), true);
            curl_close($ch);

            $bloodBag = BloodBag::where('serial_no', $validatedData['serial_no'])->first();
            $bloodBag->update(['isStored' => 0]);

            $lastUpdate = LastUpdate::first();
            $lastUpdate->date_update = Carbon::now();
            $lastUpdate->save();

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


    public function expiredBlood()
    {
        $expiredBlood = BloodBag::join('user_details', 'blood_bags.user_id', '=', 'user_details.user_id')
            ->where('blood_bags.isExpired', 1)
            ->where('blood_bags.isDisposed', 0)
            ->where('user_details.remarks', 0)
            ->select('blood_bags.blood_bags_id', 'blood_bags.serial_no', 'user_details.donor_no', 'user_details.blood_type', 'user_details.first_name', 'user_details.last_name', 'blood_bags.date_donated', 'blood_bags.expiration_date')
            ->orderBy('blood_bags.expiration_date', 'desc')
            ->paginate(8);

        if ($expiredBlood->isEmpty()) {
            return response()->json([
                'status' => 'success',
                'message' => 'No expired blood bag',
            ]);
        } else {

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

    public function searchExpiredBlood(Request $request)
    {
        try {
            $request->validate([
                'searchInput' => 'required',
            ]);

            $searchInput = str_replace(' ', '', $request->input('searchInput'));

            $expiredBlood = BloodBag::join('user_details', 'blood_bags.user_id', '=', 'user_details.user_id')
                ->where('blood_bags.isExpired', 1)
                ->where('blood_bags.isDisposed', 0)
                ->where('user_details.remarks', 0)
                ->select('blood_bags.blood_bags_id', 'blood_bags.serial_no', 'user_details.donor_no', 'user_details.blood_type', 'user_details.first_name', 'user_details.last_name', 'blood_bags.date_donated', 'blood_bags.expiration_date')
                ->where(function ($query) use ($searchInput) {
                    $query->where('blood_bags.serial_no', 'LIKE', '%' . $searchInput . '%')
                        // ->orWhere('user_details.first_name', 'LIKE', '%' . $searchInput . '%')
                        // ->orWhere('user_details.last_name', 'LIKE', '%' . $searchInput . '%')
                        ->orWhere('user_details.blood_type', 'LIKE', '%' . $searchInput . '%')
                        ->orWhere('user_details.donor_no', 'LIKE', '%' . $searchInput . '%')
                        ->orWhere('blood_bags.date_donated', 'LIKE', '%' . $searchInput . '%')
                        ->orWhere('blood_bags.expiration_date', 'LIKE', '%' . $searchInput . '%');
                })
                ->paginate(8);

            if ($expiredBlood->isEmpty()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'No expired blood bag',
                ]);
            } else {

                return response()->json([
                    'status' => 'success',
                    'data' => $expiredBlood,
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

    public function exportExpiredAsPdf(Request $request)
    {
        $bloodType = $request->input('blood_type');
        $startDate = $request->input('startDate');
        $endDate = $request->input('endDate');

        $user = getAuthenticatedUserId();
        $userId = $user->user_id;

        $expiredBlood = BloodBag::join('user_details', 'blood_bags.user_id', '=', 'user_details.user_id')
            ->where('blood_bags.isExpired', 1)
            ->where('blood_bags.isDisposed', 0)
            ->where('user_details.remarks', 0);

        // Apply additional filters based on request parameters
        if ($bloodType !== 'All') {
            $expiredBlood->where('user_details.blood_type', $bloodType);
        }

        if (!empty($startDate)) {
            $expiredBlood->where('blood_bags.expiration_date', '>=', $startDate);
        }

        if (!empty($endDate)) {
            $expiredBlood->where('blood_bags.expiration_date', '<=', $endDate);
        }

        $inventory = $expiredBlood->select('blood_bags.blood_bags_id', 'blood_bags.serial_no', 'user_details.donor_no', 'user_details.blood_type', 'user_details.first_name', 'user_details.last_name', 'blood_bags.date_donated', 'blood_bags.expiration_date')
            ->orderBy('blood_bags.expiration_date', 'desc')
            ->get();
        //dd($inventory);

        $totalCount = $inventory->count();

        $ip = file_get_contents('https://api.ipify.org');
        $ch = curl_init('http://ipwho.is/' . $ip);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);

        $ipwhois = json_decode(curl_exec($ch), true);

        curl_close($ch);

        AuditTrail::create([
            'user_id'    => $userId,
            'module'     => 'Inventory',
            'action'     => 'Export Blood Stocks as PDF',
            'status'     => 'success',
            'ip_address' => $ipwhois['ip'],
            'region'     => $ipwhois['region'],
            'city'       => $ipwhois['city'],
            'postal'     => $ipwhois['postal'],
            'latitude'   => $ipwhois['latitude'],
            'longitude'  => $ipwhois['longitude'],
        ]);

        $totalBloodBags = $totalCount;
        $dateNow = new \DateTime();
        $formattedDate = $dateNow->format('F j, Y g:i A');

        $pdf = new Dompdf();
        $pdf->setPaper('A4', 'landscape');
        $html = view('expired-bag-details', ['inventory' => $inventory, 'totalBloodBags' => $totalBloodBags, 'dateNow' => $formattedDate])->render();
        $pdf->loadHtml($html);
        $pdf->render();

        // Return the PDF as a response
        return response($pdf->output(), 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="donor-list.pdf"');
    }

    public function getTempDeferralBloodBag()
    {

        $inventory = BloodBag::join('reactive_blood_bags', 'reactive_blood_bags.blood_bags_id', '=', 'blood_bags.blood_bags_id')
            ->join('user_details', 'blood_bags.user_id', '=', 'user_details.user_id')
            ->join('reactive_remarks', 'reactive_remarks.reactive_remarks_id', '=', 'reactive_blood_bags.reactive_remarks_id')
            ->where('blood_bags.isExpired', 0)
            ->where('blood_bags.isDisposed', 0)
            ->where('blood_bags.separate', 1)
            ->paginate(8);


        if ($inventory->isEmpty()) {
            return response()->json([
                'status' => 'success',
                'message' => 'deferral blood bag',
            ]);
        } else {

            $totalCount = $inventory->count();

            return response()->json([
                'status' => 'success',
                'data' => $inventory,
                'total_count' => $totalCount
            ]);
        }
    }

    //TEMPDEF = REACTIVE
    public function filterBloodTypeTempDeferral(Request $request)
    {
        try {
            $bloodType = $request->input('blood_type');
            $startDate = $request->input('startDate');
            $remarks = $request->input('remarks');
            $endDate = $request->input('endDate');

            $inventory = BloodBag::join('reactive_blood_bags', 'reactive_blood_bags.blood_bags_id', '=', 'blood_bags.blood_bags_id')
                ->join('user_details', 'blood_bags.user_id', '=', 'user_details.user_id')
                ->join('reactive_remarks', 'reactive_remarks.reactive_remarks_id', '=', 'reactive_blood_bags.reactive_remarks_id')
                ->where('blood_bags.isExpired', 0)
                ->where('blood_bags.isDisposed', 0)
                ->where('blood_bags.separate', 1);

            if ($bloodType == 'All') {
                if ($startDate && $endDate) {
                    $inventory->whereBetween('blood_bags.date_donated', [$startDate, $endDate]);
                }
                if ($remarks && $remarks != 'All') {
                    $inventory->where('reactive_remarks.reactive_remarks_desc', $remarks);
                }
                $totalCount = $inventory->count();
                $inventory = $inventory->orderBy('reactive_blood_bags.created_at')->paginate(8);
            } else {
                $inventory->where('user_details.blood_type', $bloodType);
                if ($startDate && $endDate) {
                    $inventory->whereBetween('blood_bags.date_donated', [$startDate, $endDate]);
                }
                if ($remarks && $remarks != 'All') {
                    $inventory->where('reactive_remarks.reactive_remarks_desc', $remarks);
                }
                $totalCount = $inventory->count();
                $inventory = $inventory->orderBy('reactive_blood_bags.created_at')->paginate(8);
            }

            return response()->json([
                'status' => 'success',
                'data' => ($inventory),
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

    public function searchRbb(Request $request)
    {
        try {
            $request->validate([
                'searchInput' => 'required',
            ]);

            $searchInput = str_replace(' ', '', $request->input('searchInput'));

            $inventory = BloodBag::join('reactive_blood_bags', 'reactive_blood_bags.blood_bags_id', '=', 'blood_bags.blood_bags_id')
                ->join('user_details', 'blood_bags.user_id', '=', 'user_details.user_id')
                ->join('reactive_remarks', 'reactive_remarks.reactive_remarks_id', '=', 'reactive_blood_bags.reactive_remarks_id')
                ->where('blood_bags.isExpired', 0)
                ->where('blood_bags.isDisposed', 0)
                ->where('blood_bags.separate', 1)
                ->select('blood_bags.blood_bags_id', 'blood_bags.serial_no', 'user_details.donor_no', 'user_details.blood_type', 'user_details.first_name', 'user_details.last_name', 'blood_bags.date_donated', 'blood_bags.expiration_date')
                ->where(function ($query) use ($searchInput) {
                    $query->where('blood_bags.serial_no', 'LIKE', '%' . $searchInput . '%')
                        ->orWhere('user_details.first_name', 'LIKE', '%' . $searchInput . '%')
                        ->orWhere('user_details.last_name', 'LIKE', '%' . $searchInput . '%')
                        ->orWhere('user_details.blood_type', 'LIKE', '%' . $searchInput . '%')
                        ->orWhere('reactive_remarks.reactive_remarks_desc', 'LIKE', '%' . $searchInput . '%')
                        ->orWhere('user_details.donor_no', 'LIKE', '%' . $searchInput . '%')
                        ->orWhere('blood_bags.date_donated', 'LIKE', '%' . $searchInput . '%')
                        ->orWhere('blood_bags.expiration_date', 'LIKE', '%' . $searchInput . '%');
                })
                ->paginate(8);

            if ($inventory->isEmpty()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'No expired blood bag',
                ]);
            } else {

                return response()->json([
                    'status' => 'success',
                    'data' => $inventory,
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

    public function exportRbb(Request $request)
    {
        $bloodType = $request->input('blood_type');
        $remarks = $request->input('remarks');
        $startDate = $request->input('startDate');
        $endDate = $request->input('endDate');

        $user = getAuthenticatedUserId();
        $userId = $user->user_id;

        $inventory = BloodBag::join('reactive_blood_bags', 'reactive_blood_bags.blood_bags_id', '=', 'blood_bags.blood_bags_id')
            ->join('user_details', 'blood_bags.user_id', '=', 'user_details.user_id')
            ->join('reactive_remarks', 'reactive_remarks.reactive_remarks_id', '=', 'reactive_blood_bags.reactive_remarks_id')
            ->where('blood_bags.isExpired', 0)
            ->where('blood_bags.isDisposed', 0)
            ->where('blood_bags.separate', 1);

        // Apply additional filters based on request parameters
        if ($bloodType !== 'All') {
            $inventory->where('user_details.blood_type', $bloodType);
        }

        if ($remarks !== 'All') {
            $inventory->where('reactive_remarks.reactive_remarks_desc', $remarks);
        }

        if (!empty($startDate)) {
            $inventory->where('blood_bags.date_donated', '>=', $startDate);
        }

        if (!empty($endDate)) {
            $inventory->where('blood_bags.date_donated', '<=', $endDate);
        }

        $inventory = $inventory->select('blood_bags.blood_bags_id', 'blood_bags.serial_no', 'user_details.donor_no', 'user_details.blood_type', 'user_details.first_name', 'user_details.last_name', 'blood_bags.date_donated', 'blood_bags.expiration_date', 'reactive_remarks.reactive_remarks_desc')
            ->orderBy('blood_bags.date_donated', 'desc')
            ->get();
        //dd($inventory);

        $totalCount = $inventory->count();

        $ip = file_get_contents('https://api.ipify.org');
        $ch = curl_init('http://ipwho.is/' . $ip);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);

        $ipwhois = json_decode(curl_exec($ch), true);

        curl_close($ch);

        AuditTrail::create([
            'user_id'    => $userId,
            'module'     => 'Inventory',
            'action'     => 'Export Reactive Blood Bag as PDF',
            'status'     => 'success',
            'ip_address' => $ipwhois['ip'],
            'region'     => $ipwhois['region'],
            'city'       => $ipwhois['city'],
            'postal'     => $ipwhois['postal'],
            'latitude'   => $ipwhois['latitude'],
            'longitude'  => $ipwhois['longitude'],
        ]);

        $totalBloodBags = $totalCount;
        $dateNow = new \DateTime();
        $formattedDate = $dateNow->format('F j, Y g:i A');

        $pdf = new Dompdf();
        $pdf->setPaper('A4', 'landscape');
        $html = view('rbb-details', ['inventory' => $inventory, 'totalBloodBags' => $totalBloodBags, 'dateNow' => $formattedDate])->render();
        $pdf->loadHtml($html);
        $pdf->render();

        // Return the PDF as a response
        return response($pdf->output(), 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="donor-list.pdf"');
    }

    public function getPermaDeferralBloodBag()
    {
        $tempExpiredBlood = BloodBag::join('user_details', 'blood_bags.user_id', '=', 'user_details.user_id')
            ->where('blood_bags.isExpired', 0)
            ->where('blood_bags.isDisposed', 0)
            ->where('blood_bags.separate', 1)
            ->select('blood_bags.blood_bags_id', 'blood_bags.serial_no', 'user_details.donor_no', 'user_details.blood_type', 'user_details.first_name', 'user_details.last_name', 'blood_bags.date_donated', 'blood_bags.expiration_date')
            ->paginate(8);

        if ($tempExpiredBlood->isEmpty()) {
            return response()->json([
                'status' => 'success',
                'message' => 'deferral blood bag',
            ]);
        } else {
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
            $remarks = $request->input('remarks');
            $startDate = $request->input('startDate');
            $endDate = $request->input('endDate');

            $inventory = BloodBag::join('spoiled_blood_bags', 'spoiled_blood_bags.blood_bags_id', '=', 'blood_bags.blood_bags_id')
                ->join('user_details', 'blood_bags.user_id', '=', 'user_details.user_id')
                ->join('spoiled_remarks', 'spoiled_remarks.spoiled_remarks_id', '=', 'spoiled_blood_bags.spoiled_remarks_id')
                ->where('blood_bags.isExpired', 0)
                ->where('blood_bags.isDisposed', 0)
                ->where('blood_bags.separate', 1);

            if ($bloodType == 'All') {
                if ($startDate && $endDate) {
                    $inventory->whereBetween('blood_bags.date_donated', [$startDate, $endDate]);
                }
                if ($remarks && $remarks != 'All') {
                    $inventory->where('spoiled_remarks.spoiled_remarks_desc', $remarks);
                }
                $totalCount = $inventory->count();
                $inventory = $inventory->orderBy('spoiled_blood_bags.created_at')->paginate(8);
            } else {
                $inventory->where('user_details.blood_type', $bloodType);
                if ($startDate && $endDate) {
                    $inventory->whereBetween('blood_bags.date_donated', [$startDate, $endDate]);
                }
                if ($remarks && $remarks != 'All') {
                    $inventory->where('spoiled_remarks.spoiled_remarks_desc', $remarks);
                }
                $totalCount = $inventory->count();
                $inventory = $inventory->orderBy('spoiled_blood_bags.created_at')->paginate(8);
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

    public function searchSbb(Request $request)
    {
        try {
            $request->validate([
                'searchInput' => 'required',
            ]);

            $searchInput = str_replace(' ', '', $request->input('searchInput'));

            $inventory = BloodBag::join('spoiled_blood_bags', 'spoiled_blood_bags.blood_bags_id', '=', 'blood_bags.blood_bags_id')
                ->join('user_details', 'blood_bags.user_id', '=', 'user_details.user_id')
                ->join('spoiled_remarks', 'spoiled_remarks.spoiled_remarks_id', '=', 'spoiled_blood_bags.spoiled_remarks_id')
                ->where('blood_bags.isExpired', 0)
                ->where('blood_bags.isDisposed', 0)
                ->where('blood_bags.separate', 1)
                ->where(function ($query) use ($searchInput) {
                    $query->where('blood_bags.serial_no', 'LIKE', '%' . $searchInput . '%')
                        ->orWhere('user_details.first_name', 'LIKE', '%' . $searchInput . '%')
                        ->orWhere('user_details.last_name', 'LIKE', '%' . $searchInput . '%')
                        ->orWhere('user_details.blood_type', 'LIKE', '%' . $searchInput . '%')
                        ->orWhere('user_details.donor_no', 'LIKE', '%' . $searchInput . '%')
                        ->orWhere('spoiled_remarks.spoiled_remarks_desc', 'LIKE', '%' . $searchInput . '%')
                        ->orWhere('blood_bags.date_donated', 'LIKE', '%' . $searchInput . '%')
                        ->orWhere('blood_bags.expiration_date', 'LIKE', '%' . $searchInput . '%');
                })
                ->paginate(8);

            if ($inventory->isEmpty()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'No expired blood bag',
                ]);
            } else {

                return response()->json([
                    'status' => 'success',
                    'data' => $inventory,
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

    public function exportSbb(Request $request)
    {
        $bloodType = $request->input('blood_type');
        $remarks = $request->input('remarks');
        $startDate = $request->input('startDate');
        $endDate = $request->input('endDate');

        $user = getAuthenticatedUserId();
        $userId = $user->user_id;

        $inventory = BloodBag::join('spoiled_blood_bags', 'spoiled_blood_bags.blood_bags_id', '=', 'blood_bags.blood_bags_id')
            ->join('user_details', 'blood_bags.user_id', '=', 'user_details.user_id')
            ->join('spoiled_remarks', 'spoiled_remarks.spoiled_remarks_id', '=', 'spoiled_blood_bags.spoiled_remarks_id')
            ->where('blood_bags.isExpired', 0)
            ->where('blood_bags.isDisposed', 0)
            ->where('blood_bags.separate', 1);

        // Apply additional filters based on request parameters
        if ($bloodType !== 'All') {
            $inventory->where('user_details.blood_type', $bloodType);
        }

        if ($remarks !== 'All') {
            $inventory->where('spoiled_remarks.spoiled_remarks_desc', $remarks);
        }

        if (!empty($startDate)) {
            $inventory->where('blood_bags.date_donated', '>=', $startDate);
        }

        if (!empty($endDate)) {
            $inventory->where('blood_bags.date_donated', '<=', $endDate);
        }

        $inventory = $inventory->select('blood_bags.blood_bags_id', 'blood_bags.serial_no', 'user_details.donor_no', 'user_details.blood_type', 'user_details.first_name', 'user_details.last_name', 'blood_bags.date_donated', 'blood_bags.expiration_date', 'spoiled_remarks.spoiled_remarks_desc')
            ->orderBy('blood_bags.date_donated', 'desc')
            ->get();
        //dd($inventory);

        $totalCount = $inventory->count();

        $ip = file_get_contents('https://api.ipify.org');
        $ch = curl_init('http://ipwho.is/' . $ip);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);

        $ipwhois = json_decode(curl_exec($ch), true);

        curl_close($ch);

        AuditTrail::create([
            'user_id'    => $userId,
            'module'     => 'Inventory',
            'action'     => 'Export Spoiled Blood Bag as PDF',
            'status'     => 'success',
            'ip_address' => $ipwhois['ip'],
            'region'     => $ipwhois['region'],
            'city'       => $ipwhois['city'],
            'postal'     => $ipwhois['postal'],
            'latitude'   => $ipwhois['latitude'],
            'longitude'  => $ipwhois['longitude'],
        ]);

        $totalBloodBags = $totalCount;
        $dateNow = new \DateTime();
        $formattedDate = $dateNow->format('F j, Y g:i A');

        $pdf = new Dompdf();
        $pdf->setPaper('A4', 'landscape');
        $html = view('sbb-details', ['inventory' => $inventory, 'totalBloodBags' => $totalBloodBags, 'dateNow' => $formattedDate])->render();
        $pdf->loadHtml($html);
        $pdf->render();

        // Return the PDF as a response
        return response($pdf->output(), 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="donor-list.pdf"');
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
            $ch = curl_init('http://ipwho.is/' . $ip);
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
                    $bloodBag->update(['disposed_date' => Carbon::now()]);
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
            $ch = curl_init('http://ipwho.is/' . $ip);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HEADER, false);

            $ipwhois = json_decode(curl_exec($ch), true);
            curl_close($ch);

            $patientReceiverId = '';
            if ($user_id == null) {
                PatientReceiver::create([
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
                PatientReceiver::create([
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
                //blood bag
                $bloodBag = BloodBag::where('blood_bags_id', $bloodBagId)->first();
                $bloodBag->patient_receivers_id =  $patientReceiverId;
                $bloodBag->isUsed = 1;
                $bloodBag->dispensed_date = now();
                $bloodBag->save();

                //email
                $user_id = $bloodBag->user_id;
                $user = User::where('user_id', $user_id)->first();
                $email = $user->email;
    
                Mail::to($email)->send(new DispensedEmail($user));


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

    public function getRegisteredUsers()
    {
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

    public function getHospitals()
    {
        $hospitals = app(Hospital::class)->getAllHospital();

        return response()->json([
            'status'    => 'success',
            'hospitals'   => $hospitals,
        ]);
    }
}
