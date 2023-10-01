<?php

namespace App\Rules;

use App\Models\User;
use App\Models\UserDetail;

use Illuminate\Contracts\Validation\Rule;


class MobileUpdateProfile implements Rule
{

    public function passes($attribute, $value)
    {
        $userId = request('user_id');
        $user = User::find($userId);
        if ($user && $user->mobile === $value) {
            return true; // Validation passes
        }

        $existingUser = User::where('mobile', $value)->first();
        if (!$existingUser) {
            return true; // Validation passes
        }

        if ($existingUser->id !== $userId) {
            return false; // Validation fails
        }
        
        return true; 
    }


    public function message()
    {
        return 'The :attribute has already been taken ' ;
    }
}
