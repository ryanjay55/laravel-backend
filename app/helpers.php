<?php

use Illuminate\Support\Facades\Auth;

function getAuthenticatedUserId() {

    if (Auth::check()) {
        $user = Auth::user();
        return $user;
    }
    return null;
    
}