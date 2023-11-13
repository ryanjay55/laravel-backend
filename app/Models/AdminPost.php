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

    public function getPendingPost(){
        //check if the donation_date is less than or equal to the current date and time (NOW()). If it is, we set the isAccommodated field to 2; otherwise, we keep the original value from the blood_request table.
        $sql = 'SELECT br.blood_request_id, IF(ap.donation_date <= NOW(), 2, br.isAccommodated) as isAccommodated, ap.venue, ap.donation_date, ap.blood_needs, ap.body, ap.created_at FROM admin_posts ap
        JOIN blood_request as br on br.blood_request_id = ap.blood_request_id WHERE br.isAccommodated = 0';
    
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
