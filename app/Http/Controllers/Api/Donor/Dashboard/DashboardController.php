<?php

namespace App\Http\Controllers\Api\Donor\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Galloner;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function getBadge()
    {

        $user = getAuthenticatedUserId();
        $userId = $user->user_id;

        $galloners = Galloner::where('user_id', $userId)->first();

        return response()->json([
            'donation_count' => $galloners->donate_qty,
            'badge' => $galloners->badge
        ]); 
    }
}
