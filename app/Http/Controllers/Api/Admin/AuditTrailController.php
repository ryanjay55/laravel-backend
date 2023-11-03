<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AuditTrail;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AuditTrailController extends Controller
{
  public function getAuditTrail(Request $request)
  {
      $module = $request->input('module');
      $startDate = $request->input('startDate');
      $endDate = $request->input('endDate');
  
      $logs = AuditTrail::join('user_details', 'user_details.user_id', '=', 'audit_trails.user_id')
          ->select('audit_trails.*', 'user_details.first_name', 'user_details.last_name')
          ->when($module !== 'All', function ($query) use ($module) {
              return $query->where('audit_trails.module', $module);
          })
          ->when($startDate && $endDate, function ($query) use ($startDate, $endDate) {
              return $query->whereBetween(DB::raw('DATE(audit_trails.created_at)'), [$startDate, $endDate]);
          })
          ->paginate(8);
  
      if ($logs->isEmpty()) {
          return response()->json([
              'status' => 'success',
              'data' => [],
          ], 200);
      }
  
      return response()->json([
          'status' => 'success',
          'data' => $logs,
      ], 200);
  }

    // public function getAuditTrail(Request $request)
    // {
    //     try {
    //         $module = $request->input('module');
    //         $startDate = $request->input('startDate');
    //         $endDate = $request->input('endDate');
    
    //         $auditTrails = AuditTrail::join('user_details', 'user_details.user_id', '=', 'audit_trails.user_id')
    //             ->select('audit_trails.*', 'user_details.*')
    //             ->when($module !== 'All', function ($query) use ($module) {
    //                 return $query->where('audit_trails.module', $module);
    //             })
    //             ->when($startDate && $endDate, function ($query) use ($startDate, $endDate) {
    //                 return $query->whereBetween('audit_trails.created_at', [$startDate, $endDate]);
    //             })
    //             ->paginate(8);
    
    //         return response()->json([
    //             'status' => 'success',
    //             'auditTrails' => $auditTrails,
    //         ]);
    
    //     } catch (ValidationException $e) {
    
    //         return response()->json([
    //             'status' => 'error',
    //             'errors' => $e->validator->errors(),
    //         ], 422);
    //     }
    // }
}
