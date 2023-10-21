<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeferralCategory extends Model
{
    use HasFactory;

    protected $primaryKey = 'categories_id ';
    protected $table = 'categories';
}
