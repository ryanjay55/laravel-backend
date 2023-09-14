<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BloodBag extends Model
{
    use HasFactory;
    protected $primaryKey = 'blood_bags_id';
    protected $table = 'blood_bags';

    protected $fillable = [
        'user_id',
        'serial_no',
        'date_donated',
        'venue',
        'bled_by',
        'isStored'
    ];

    
}

