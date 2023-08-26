<?php

namespace App\Models\Address;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CitiesModel extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $table = 'cities';    
}
