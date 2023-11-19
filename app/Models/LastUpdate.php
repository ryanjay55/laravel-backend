<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LastUpdate extends Model
{
    use HasFactory;
    protected $primaryKey = 'last_update_id';
    protected $table = 'last_updates';

    protected $fillable = [
        'date_update',
    ];
}
