<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserDetail extends Model
{
    use HasFactory;
    protected $primaryKey = 'user_details_id';
    protected $table = 'user_details';

    protected $fillable = [
        'user_id',
        'donor_no',
        'first_name',
        'middle_name',
        'last_name',
        'dob',
        'sex',
        'blood_type',
        'occupation',
        'street',
        'region',
        'province',
        'municipality',
        'barangay',
        'postalcode',
    ];


    
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
