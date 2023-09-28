<?php

namespace App\Rules;

use App\Models\User;
use App\Models\UserDetail;

use Illuminate\Contracts\Validation\Rule;


class EmailUpdateProfile implements Rule
{

    public function passes($attribute, $value)
    {
      
    }

    public function message()
    {
        return 'The :attribute has already been taken ' ;
    }
}
