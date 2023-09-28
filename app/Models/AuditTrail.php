<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuditTrail extends Model
{
    use HasFactory;
    protected $primaryKey = 'audit_trails_id';
    protected $table = 'audit_trails';

    protected $fillable = [
        'user_id',
        'module',
        'action',
        'status',
        'ip_address',
        'region',
        'city',
        'postal',
        'latitude',
        'longitude',
    ];
}
