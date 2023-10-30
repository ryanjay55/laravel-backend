<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReactiveBloodBag extends Model
{
    use HasFactory;

    protected $primaryKey = 'reactive_blood_bag_id';
    protected $table = 'reactive_blood_bags';

    protected $fillable = [
        'blood_bags_id',
        'remarks',
        'reactive_remarks_id'
    ];
}
