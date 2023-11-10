<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class PatientReceiver extends Model
{
    use HasFactory;

    protected $primaryKey = 'patient_receivers_id ';
    protected $table = 'patient_receivers';


    protected $fillable = [
        'user_id',
        'first_name',
        'middle_name',
        'last_name',
        'dob',
        'sex',
        'blood_type',
        'diagnosis',
        'hospital',
        'payment',
        'status'
    ];

    public function countReceivedBlood($userId)
    {
        $sql = 'SELECT COUNT(*) as count FROM patient_receivers pr 
        JOIN blood_bags as bb ON bb.patient_receivers_id = pr.patient_receivers_id
        WHERE pr.user_id = :userId';
    
        $result = DB::connection('mysql')->selectOne($sql, [
            'userId' => $userId,
        ]);
    
        return $result->count;
    }
   public function getDispensedList($serialNo) {
      $sql = "SELECT pr.first_name, pr.middle_name, pr.last_name, pr.blood_type, pr.sex, pr.dob, pr.diagnosis, pr.hospital, pr.payment, pr.created_at, GROUP_CONCAT(bb.serial_no) AS serial_numbers
      FROM patient_receivers pr
      JOIN blood_bags as bb ON bb.patient_receivers_id = pr.patient_receivers_id
      WHERE pr.patient_receivers_id IN (
          SELECT patient_receivers_id
          FROM blood_bags
          WHERE serial_no LIKE CONCAT('%', :serial_no, '%')
      )
      GROUP BY pr.first_name, pr.middle_name, pr.last_name, pr.blood_type, pr.sex, pr.dob, pr.diagnosis, pr.hospital, pr.payment, pr.created_at";

       $result = DB::connection('mysql')->select($sql, [
           'serial_no' => $serialNo,
       ]);
       
       return $result;
   }

   public function getDonorWhoDonate($serialNumbers) {
       $userDetails = [];
   
       if (!is_null($serialNumbers) && (is_array($serialNumbers) || is_object($serialNumbers))) {
           foreach ($serialNumbers as $serialNo) {
               $sql = "SELECT * FROM user_details ud
                       JOIN blood_bags AS bb ON bb.user_id = ud.user_id
                       WHERE bb.serial_no = :serial_no";
   
               $result = DB::connection('mysql')->select($sql, [
                   'serial_no' => $serialNo,
               ]);
   
               if (!empty($result)) {
                   // Add user details to the array
                   $userDetails[] = $result;
               }
           }
       }
   
       return $userDetails;
   }

}
