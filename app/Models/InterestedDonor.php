<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InterestedDonor extends Model
{
    use HasFactory;
    protected $primaryKey = 'interested_donor_id';
    protected $table = 'interested_donors';

    protected $fillable = [
        'user_id',
        'blood_request_id',
        'status'
    ];

}
