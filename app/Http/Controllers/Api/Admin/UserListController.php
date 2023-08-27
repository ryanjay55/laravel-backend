<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\UserDetail;
use Illuminate\Http\Request;

class UserListController extends Controller
{
    public function getUserDetails(){
        $userDetails = UserDetail::all();

        return response()->json([
            'status' => 'success',
            'data' => $userDetails
        ], 200);
    }

    
}
