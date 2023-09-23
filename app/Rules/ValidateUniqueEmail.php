<?php

namespace App\Rules;

use App\Models\User;
use App\Models\UserDetail;

use Illuminate\Contracts\Validation\Rule;


class ValidateUniqueEmail implements Rule
{

    public function passes($attribute, $value)
    {
        $user = User::where('email', $value)->first();

        if ($user) {
            $userId = $user->user_id;
            $userDetails = UserDetail::where('user_id', $userId)->first();

            if ($userDetails) {
                return false; // User details exist
            } else {
                return true; // User details do not exist
            }
        } else {
            return true; // User does not exist
        }
    }

    public function message()
    {
        return 'The :attribute has already been taken ' ;
    }
}
