<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class BloodRequest extends Model
{
    use HasFactory;

    protected $primaryKey = 'blood_request_id';
    protected $table = 'blood_request';

    protected $fillable = [
        'request_id_number',
        'user_id',
        'blood_units',
        'blood_component_id',
        'hospital',
        'diagnosis',
        'schedule',
        'isAccommodated',
        'remarks',
        'isCancelled',
        'cancel_reason',
        'status',
    ];

    public function getBloodRequest()
    {
        $sql = "SELECT * FROM blood_request br
        join blood_components as bc on br.blood_component_id = bc.blood_component_id
        join user_details as ud on br.user_id = ud.user_id";

        $result = DB::connection('mysql')->select($sql);

        return $result;
    }

    public function getAllRequestId()
    {
        $sql = "SELECT ud.first_name, ud.middle_name, ud.last_name, br.request_id_number, br.blood_request_id
        FROM blood_request br
        JOIN user_details AS ud ON br.user_id = ud.user_id
        WHERE br.isAccommodated = 0 AND br.status = 0
        AND NOT EXISTS (
            SELECT 1
            FROM admin_posts ap
            WHERE ap.blood_request_id = br.blood_request_id
            AND ap.status = 0 
        );
        ";

        $result = DB::connection('mysql')->select($sql);

        return $result;
    }
}
