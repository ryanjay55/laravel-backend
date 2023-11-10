<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NetworkPost extends Model
{
    use HasFactory;
    protected $primaryKey = 'network_post_id';
    protected $table = 'network_posts';

    protected $fillable = [
        'request_id_number',
        'donation_date',
        'venue',
        'body',
        'blood_components',
        'status',
    ];
}
