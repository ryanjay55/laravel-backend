<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DonorType extends Model
{
    use HasFactory;

    protected $primaryKey = 'donor_types_id';
    protected $table = 'donor_types';
}
