<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SpoiledBloodBag extends Model
{
    use HasFactory;
    protected $primaryKey = 'spoiled_blood_bag_id';
    protected $table = 'spoiled_blood_bags';

    protected $fillable = [
        'blood_bags_id',
        'remarks',
    ];
}
