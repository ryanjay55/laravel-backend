<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AuditTrail;

class AuditTrailController extends Controller
{
    public function getAuditTrail()
    {
        $logs = AuditTrail::select('action', 'status', 'ip_address', 'created_at')
            ->orderBy('audit_trails_id', 'desc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $logs,
        ], 200);
    }
}
