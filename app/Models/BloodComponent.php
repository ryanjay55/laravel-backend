<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BloodComponent extends Model
{
    use HasFactory;
    protected $primaryKey = 'blood_component_id';
    protected $table = 'blood_components';
}
