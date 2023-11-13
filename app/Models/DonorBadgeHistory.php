<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DonorBadgeHistory extends Model
{
    use HasFactory;
    protected $primaryKey = 'donor_badge_history_id';
    protected $table = 'donor_badge_history';

    protected $fillable = [
        'user_id',
        'badge_id',
        'achieved_date',
    ];
}
