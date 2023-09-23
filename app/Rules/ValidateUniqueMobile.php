<?php

namespace App\Rules;

use App\Models\User;
use App\Models\UserDetail;
use Illuminate\Contracts\Validation\Rule;

class ValidateUniqueMobile implements Rule
{
    public function passes($attribute, $value)
    {
        $user = User::where('mobile', $value)->first();

        if ($user) {
            $userId = $user->user_id;
            $userDetails = UserDetail::where('user_id', $userId)->first();

            if ($userDetails) {
                return false; 
            } else {
                return true; 
            }
        } else {
            return true; 
        }
    }

    public function message()
    {
        return 'The :attribute has already been taken.';
    }
}