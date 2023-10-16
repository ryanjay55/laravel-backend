<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BledBy extends Model
{
    use HasFactory;
    protected $primaryKey = 'bled_by_id';
    protected $table = 'bled_by';

    protected $fillable = [
        'user_details_id',
        'status',
    ];

    public function userDetail()
    {
        return $this->belongsTo(UserDetail::class, 'user_details_id');
    }
}
