<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Deferral extends Model
{
    use HasFactory;

    protected $primaryKey = 'deferrals_id';
    protected $table = 'deferrals';

    protected $fillable = [
        'user_id',
        'categories_id',
        'deferral_type_id',
        'venue',
        'date_deferred',
        'deferred_duration',
        'end_date',
        'donation_type_id'
    ];

}
