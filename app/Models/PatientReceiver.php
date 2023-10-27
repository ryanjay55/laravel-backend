<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PatientReceiver extends Model
{
    use HasFactory;

    protected $primaryKey = 'patient_receivers_id ';
    protected $table = 'patient_receivers';


    protected $fillable = [
        'user_id',
        'first_name',
        'middle_name',
        'last_name',
        'dob',
        'sex',
        'blood_type',
        'diagnosis',
        'hospital',
        'payment',
        'status'
    ];
}
