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

    //Accommodated = 0  -> pending
    //Accommodated = 1  -> Accomodated
    //Accommodated = 2  -> Declined
    //Accommodated = 3  -> Cancelled

    public function getPendingPost(){
        $sql = 'SELECT br.blood_request_id, IF(ap.donation_date >= NOW(), 2, br.isAccommodated) as isAccommodated, ap.venue, ap.donation_date, ap.blood_needs, ap.body, ap.created_at
        FROM admin_posts ap
        JOIN blood_request as br on br.blood_request_id = ap.blood_request_id
        WHERE br.isAccommodated = 0 OR br.isAccommodated = 3
        ORDER BY ap.donation_date ASC';

        $result = DB::connection('mysql')->select($sql);

        return $result;
    }

    public function getAccomodatedPost(){
        $sql = 'SELECT br.blood_request_id, br.isAccommodated, ap.venue, ap.donation_date, ap.blood_needs, ap.body, ap.created_at FROM admin_posts ap
        join blood_request as br on br.blood_request_id = ap.blood_request_id where br.isAccommodated = 1';

        $result = DB::connection('mysql')->select($sql);

        return $result;
    }

    public function getDeclinedPost(){
        $sql = 'SELECT br.blood_request_id, br.isAccommodated, ap.venue, ap.donation_date, ap.blood_needs, ap.body, ap.created_at FROM admin_posts ap
        join blood_request as br on br.blood_request_id = ap.blood_request_id where br.isAccommodated = 2';

        $result = DB::connection('mysql')->select($sql);

        return $result;
    }


}
