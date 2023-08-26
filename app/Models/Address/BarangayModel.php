<?php

namespace App\Models\Address;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BarangayModel extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $table = 'barangay';    
}
