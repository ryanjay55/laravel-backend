<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class ValidateDateDonated implements Rule
{
    public function passes($attribute, $value)
    {
        $inputDate = \DateTime::createFromFormat('Y-m-d', $value);
        $currentDate = new \DateTime();

        return $inputDate <= $currentDate;
    }

    public function message()
    {
        return 'The :attribute must not be a date in the future. Please select a valid date.';
    }
}
