<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Otp extends Model
{
    use HasFactory;

    protected $primaryKey = 'id';
    protected $table = 'otps';

    protected $fillable = [
        'email_or_phone',
        'otp', 
        'expires_at', 
        'next_resend_otp'
    ];
}
