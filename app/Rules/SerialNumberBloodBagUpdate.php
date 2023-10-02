<?php

namespace App\Rules;

use App\Models\BloodBag;
use App\Models\User;
use App\Models\UserDetail;

use Illuminate\Contracts\Validation\Rule;


class SerialNumberBloodBagUpdate implements Rule
{

    public function passes($attribute, $value)
    {
        $bloodBagId = request('blood_bags_id');
        $bag = BloodBag::find($bloodBagId);
        if ($bag && $bag->serial_no === $value) {
            return true; // Validation passes
        }

        $existingBag = BloodBag::where('serial_no', $value)->first();
        if (!$existingBag) {
            return true; // Validation passes
        }

        if ($existingBag->blood_bags_id !== $bloodBagId) {
            return false; // Validation fails
        }
        
        return true; 
    }


    public function message()
    {
        return 'The :attribute has already been taken ' ;
    }
}
