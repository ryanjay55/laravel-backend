<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BloodRequest extends Model
{
    use HasFactory;

    protected $primaryKey = 'blood_request_id';
    protected $table = 'blood_request';

    protected $fillable = [
        'user_id',
        'blood_units',
        'blood_component_id',
        'hospital',
        'diagnosis',
        'schedule',
        'isAccommodated',
        'status',
    ];
}
