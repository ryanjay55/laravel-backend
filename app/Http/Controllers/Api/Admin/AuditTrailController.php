<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AuditTrail;

class AuditTrailController extends Controller
{
    public function getAuditTrail()
    {
        $logs = app(AuditTrail::class)->getAuditTrail();

        return response()->json([
            'status' => 'success',
            'data' => $logs,
        ], 200);
    }


    // public function getAuditTrail(Request $request)
    // {
    //     try {

    //         $validatedData = $request->validate([
    //             'module'     => ['required'], 
    //             'startDate' => ['required'],
    //             'endDate'   => ['required'],
    //         ]);
    
    //         $module = $validatedData['module'];
    //         $startDate = $validatedData['startDate'];
    //         $endDate = $validatedData['endDate'];

    //         $auditTrails = AuditTrail::join('user_details', 'user_details.user_id', '=', 'audit_trails.user_id')
    //             ->select('audit_trails.*')
    //             ->where('module', $validatedData['module'])
    //             ->whereBetween('audit_trails.created_at', [$startDate, $endDate])
    //             ->get();
        

    //             if ($module !== 'All') {
    //                 $auditTrails->where('module.blood_type', $module);
    //             }

    //             if ($startDate && $endDate) {
    //                 $auditTrails->whereBetween('audit_trails.created_at', [$startDate, $endDate]);
    //             }
    
    //         return response()->json([
    //             'status'    => 'success',
    //             'auditTrails' => $auditTrails,
    //         ]);
        
    //     } catch (ValidationException $e) {
    
    //         return response()->json([
    //             'status'        => 'error',
    //             'errors'        => $e->validator->errors(),
    //         ], 422);
    //     }
       
    // }
}
