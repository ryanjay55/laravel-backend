<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Venue extends Model
{
    use HasFactory;
    protected $primaryKey = 'venues_id';
    protected $table = 'venues';

    protected $fillable = [
        'venues_desc',
        'status',
    ];

}
