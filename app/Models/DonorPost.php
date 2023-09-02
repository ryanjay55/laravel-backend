<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class DonorPost extends Model
{
    use HasFactory;

    protected $primaryKey = 'donor_posts_id';
    protected $table = 'donor_posts';

    protected $fillable = [
        'user_id',
        'body',
        'contact',
        'status',
        'isApproved'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function galloner()
{
    return $this->belongsTo(Galloner::class);
}
}
