<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Galloner extends Model
{
    use HasFactory;
    protected $primaryKey = 'galloners_id';
    protected $table = 'galloners';

    protected $fillable = [
        'user_id',
        'serial_no',
        'donate_qty',
        'badge'
    ];
}
