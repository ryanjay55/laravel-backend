<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class AdminPost extends Model
{
    use HasFactory;

    protected $primaryKey = 'admin_post_id';
    protected $table = 'admin_posts';

    protected $fillable = [
        'user_id',
        'blood_request_id',
        'blood_needs',
        'body',
        'venue',
        'donation_date',
        'status',
    ];

    public function getPost(){
        $sql = 'SELECT blood_request_id, venue, donation_date, blood_needs, body, created_at FROM admin_posts';

        $result = DB::connection('mysql')->select($sql);

        return $result; 
    }


}
