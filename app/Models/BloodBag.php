<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class BloodBag extends Model
{
    use HasFactory;
    protected $primaryKey = 'blood_bags_id';
    protected $table = 'blood_bags';

    protected $fillable = [
        'user_id',
        'patient_receivers_id',
        'serial_no',
        'date_donated',
        'venue',
        'bled_by',
        'isCollected',
        'isStored',
        'date_stored',
        'expiration_date',
        'isExpired',
        'isDisposed',
        'disposed_date',
        'dispensed_date',
        'remaining_days',
        'donation_type_id',
        'unsafe',
    ];

    public function getAllSerialNo(){
        $sql = "select bb.serial_no, pr.created_at from blood_bags bb
        join patient_receivers as pr on pr.patient_receivers_id = bb.patient_receivers_id 
        ORDER BY pr.created_at DESC";

        $result = DB::connection('mysql')->select($sql);

        return $result;
    }

    public function countDispensedBlood($userId)
    {
        $sql = 'SELECT COUNT(*) as count FROM blood_bags bb 
        JOIN users as u ON u.user_id = bb.user_id
        WHERE bb.user_id = :userId AND bb.isUsed = 1';
    
        $result = DB::connection('mysql')->selectOne($sql, [
            'userId' => $userId,
        ]);
    
        return $result->count;
    }


   public function getManPower($venue, $startDate, $endDate) {
       $sql = "SELECT COUNT(DISTINCT bled_by) as man_power
               FROM blood_bags
               WHERE venue = :venue
               AND date_donated BETWEEN :startDate AND :endDate";
       
       $result = DB::connection('mysql')->select($sql, [
           'venue' => $venue,
           'startDate' => $startDate,
           'endDate' => $endDate
       ]);
   
       return $result[0]->man_power;
   }

   public function ListOfManPower($venue, $startDate, $endDate) {
       $sql = "SELECT DISTINCT bled_by as man_power
               FROM blood_bags
               WHERE venue = :venue
               AND date_donated BETWEEN :startDate AND :endDate";
   
       $result = DB::connection('mysql')->select($sql, [
           'venue' => $venue,
           'startDate' => $startDate,
           'endDate' => $endDate
       ]);
   
       $manPowerList = [];
       foreach ($result as $row) {
           $manPowerList[] = $row->man_power;
       }
   
       return $manPowerList;
   }

   public function bloodCollection($venue, $startDate, $endDate) {
       $bloodTypes = ['A', 'B', 'O', 'AB'];
   
       $sql = "SELECT
           all_blood_types.blood_type,
           COALESCE(COUNT(ud.blood_type), 0) AS Count
       FROM (
           SELECT :bloodType1 AS blood_type
           UNION ALL
           SELECT :bloodType2
           UNION ALL
           SELECT :bloodType3
           UNION ALL
           SELECT :bloodType4
       ) AS all_blood_types
       LEFT JOIN user_details AS ud ON all_blood_types.blood_type = REPLACE(REPLACE(ud.blood_type, '+', ''), '-', '')
       LEFT JOIN blood_bags AS bb ON ud.user_id = bb.user_id
       WHERE venue = :venue
           AND date_donated BETWEEN :startDate AND :endDate
           AND donation_type_id = 1
       GROUP BY all_blood_types.blood_type";
   
       $result = DB::connection('mysql')->select($sql, [
           'bloodType1' => $bloodTypes[0],
           'bloodType2' => $bloodTypes[1],
           'bloodType3' => $bloodTypes[2],
           'bloodType4' => $bloodTypes[3],
           'venue' => $venue,
           'startDate' => $startDate,
           'endDate' => $endDate
       ]);
   
       // Create an associative array to hold the blood type counts
       $bloodCollection = [];
       foreach ($result as $row) {
           $bloodCollection[] = [
               'blood_type' => $row->blood_type,
               'Count' => $row->Count
           ];
       }
   
       // Fill in missing blood types with count 0
       foreach ($bloodTypes as $bloodType) {
           $found = false;
           foreach ($bloodCollection as $entry) {
               if ($entry['blood_type'] === $bloodType) {
                   $found = true;
                   break;
               }
           }
           if (!$found) {
               $bloodCollection[] = [
                   'blood_type' => $bloodType,
                   'Count' => 0
               ];
           }
       }
   
       return $bloodCollection;
   }
  
   public function getTotalMaleandFemale($venue, $startDate, $endDate){
       $sql = "SELECT
           genders.sex,
           COALESCE(COUNT(ud.sex), 0) AS total_count
           FROM
           (SELECT 'Male' AS sex
           UNION ALL
           SELECT 'Female') AS genders
           LEFT JOIN user_details AS ud ON genders.sex = ud.sex
           LEFT JOIN blood_bags AS bb ON ud.user_id = bb.user_id
           WHERE
           venue = :venue
           AND date_donated BETWEEN :startDate AND :endDate
           AND bb.donation_type_id = 1
           GROUP BY
           genders.sex";
   
       $result = DB::select($sql, [
           'venue' => $venue,
           'startDate' => $startDate,
           'endDate' => $endDate,
       ]);
   
       // Append the count of males if it is missing in the result set
       $hasMale = false;
       foreach ($result as $row) {
           if ($row->sex === 'Male') {
               $hasMale = true;
               break;
           }
       }
       if (!$hasMale) {
           $result[] = (object) ['sex' => 'Male', 'total_count' => 0];
       }
   
       return $result;
   }

   public function getDonorType($venue, $startDate, $endDate){
        $sql = "SELECT
            sex,
            SUM(CASE WHEN donor_type_desc = 'First Time' THEN 1 ELSE 0 END) AS 'First Time',
            SUM(CASE WHEN donor_type_desc = 'Regular' THEN 1 ELSE 0 END) AS 'Regular',
            SUM(CASE WHEN donor_type_desc = 'Lapsed' THEN 1 ELSE 0 END) AS 'Lapsed'
        FROM user_details AS ud
        JOIN donor_types AS dt ON ud.donor_types_id = dt.donor_types_id
        JOIN blood_bags AS bb ON ud.user_id = bb.user_id
        WHERE venue = :venue
        AND date_donated BETWEEN :startDate AND :endDate
        AND bb.donation_type_id = 1
        GROUP BY sex";

        $result = DB::select($sql, [
            'venue' => $venue,
            'startDate' => $startDate,
            'endDate' => $endDate,
        ]);

        return $result;
    }

    public function getDonateFrequency($venue, $startDate, $endDate){
        $sql = "SELECT
        ud.sex AS 'Gender',
        SUM(CASE WHEN g.donate_qty = 1 THEN 1 ELSE 0 END) AS '1x',
        SUM(CASE WHEN g.donate_qty = 2 THEN 1 ELSE 0 END) AS '2x',
        SUM(CASE WHEN g.donate_qty = 3 THEN 1 ELSE 0 END) AS '3x',
        SUM(CASE WHEN g.donate_qty = 4 THEN 1 ELSE 0 END) AS '4x',
        SUM(CASE WHEN g.donate_qty = 5 THEN 1 ELSE 0 END) AS '5x',
        SUM(CASE WHEN g.donate_qty >= 6 THEN 1 ELSE 0 END) AS '>=6x'
    FROM user_details AS ud
    JOIN blood_bags AS bb ON ud.user_id = bb.user_id
    JOIN galloners AS g ON ud.user_id = g.user_id
    WHERE bb.venue = :venue
        AND bb.date_donated BETWEEN :startDate AND :endDate
        AND bb.donation_type_id = 1
    GROUP BY ud.sex";
    
        $result = DB::select($sql, [
            'venue' => $venue,
            'startDate' => $startDate,
            'endDate' => $endDate,
        ]);
    
        // Append the count of males if it is missing in the result set
        $hasMale = false;
        $hasFemale = false;
        foreach ($result as $row) {
            if (property_exists($row, 'Gender')) {
                if ($row->Gender === 'Male') {
                    $hasMale = true;
                } elseif ($row->Gender === 'Female') {
                    $hasFemale = true;
                }
            }
        }
        if (!$hasMale) {
            $result[] = (object) [
                'Gender' => 'Male',
                '1x' => '0',
                '2x' => '0',
                '3x' => '0',
                '4x' => '0',
                '5x' => '0',
                '>=6x' => '0',
            ];
        }
        if (!$hasFemale) {
            $result[] = (object) [
                'Gender' => 'Female',
                '1x' => '0',
                '2x' => '0',
                '3x' => '0',
                '4x' => '0',
                '5x' => '0',
                '>=6x' => '0',
            ];
        }
    
        return $result;
    }

   public function getAgeDistributionLeft($venue, $startDate, $endDate)
   {
    $col1 = "SELECT
            sex,
            COUNT(*) AS count,
            SUM(CASE WHEN age >= 16 AND age <= 17 THEN 1 ELSE 0 END) AS \"16-17\",
            SUM(CASE WHEN age >= 18 AND age <= 20 THEN 1 ELSE 0 END) AS \"18-20\",
            SUM(CASE WHEN age >= 21 AND age <= 30 THEN 1 ELSE 0 END) AS \"21-30\",
            SUM(CASE WHEN age >= 31 AND age <= 40 THEN 1 ELSE 0 END) AS \"31-40\",
            SUM(CASE WHEN age >= 41 AND age <= 50 THEN 1 ELSE 0 END) AS \"41-50\",
            SUM(CASE WHEN age >= 51 AND age <= 60 THEN 1 ELSE 0 END) AS \"51-60\",
            SUM(CASE WHEN age >= 61 AND age <= 65 THEN 1 ELSE 0 END) AS \"61-65\",
            SUM(CASE WHEN age > 65 THEN 1 ELSE 0 END) AS \">65\"
        FROM (
            SELECT
                ud.sex,
                TIMESTAMPDIFF(YEAR, ud.dob, CURDATE()) AS age
            FROM user_details ud
            JOIN blood_bags bb ON ud.user_id = bb.user_id
            WHERE bb.venue = :venue
            AND bb.date_donated BETWEEN :startDate AND :endDate
            AND bb.donation_type_id = 1
        ) AS age_data
        GROUP BY sex";
   
       $result = DB::select($col1, [
           'venue' => $venue,
           'startDate' => $startDate,
           'endDate' => $endDate,
       ]);
   
       return $result;
   }

   public function getAgeDistributionRight($venue, $startDate, $endDate)
    {
        $col1 = "SELECT
            sex,
            SUM(CASE WHEN age >= 18 AND age <= 24 THEN 1 ELSE 0 END) AS '18-24',
            SUM(CASE WHEN age >= 25 AND age <= 44 THEN 1 ELSE 0 END) AS '25-44',
            SUM(CASE WHEN age >= 45 AND age <= 64 THEN 1 ELSE 0 END) AS '45-64',
            SUM(CASE WHEN age >= 65 THEN 1 ELSE 0 END) AS '>=65'
            FROM (
            SELECT
            ud.sex,
            TIMESTAMPDIFF(YEAR, ud.dob, CURDATE()) AS age
            FROM user_details ud
            JOIN blood_bags bb ON ud.user_id = bb.user_id
            WHERE bb.venue = :venue
            AND bb.date_donated BETWEEN :startDate AND :endDate
            AND bb.donation_type_id = 1
            ) AS age_data
            GROUP BY sex";

        $result = DB::select($col1, [
            'venue' => $venue,
            'startDate' => $startDate,
            'endDate' => $endDate,
        ]);

        return $result;
    }


   public function getTempCategoriesDeferral($venue, $startDate, $endDate)
   {
       $sql = "SELECT
                   genders.sex,
                   COALESCE(SUM(category_counts.count), 0) AS count,
                   COALESCE(SUM(category_counts.history), 0) AS history,
                   COALESCE(SUM(category_counts.low_hgb), 0) AS low_hgb,
                   COALESCE(SUM(category_counts.others), 0) AS others
               FROM (
                   SELECT 'Male' AS sex
                   UNION ALL
                   SELECT 'Female' AS sex
               ) AS genders
               LEFT JOIN (
                   SELECT
                       ud.sex,
                       COALESCE(SUM(CASE WHEN d.categories_id = 1 THEN 1 ELSE 0 END), 0) AS history,
                       COALESCE(SUM(CASE WHEN d.categories_id = 2 THEN 1 ELSE 0 END), 0) AS low_hgb,
                       COALESCE(SUM(CASE WHEN d.categories_id = 3 THEN 1 ELSE 0 END), 0) AS others,
                       COALESCE(COUNT(*), 0) AS count
                   FROM user_details ud
                   LEFT JOIN deferrals AS d ON ud.user_id = d.user_id
                   LEFT JOIN deferral_types AS dt ON d.deferral_type_id = dt.deferral_type_id
                   WHERE d.venue = :venue
                   AND d.date_deferred BETWEEN :startDate AND :endDate
                   AND d.categories_id IN (1, 2, 3)
                   AND d.donation_type_id = 1
                   GROUP BY ud.sex
               ) AS category_counts ON genders.sex = category_counts.sex
               GROUP BY genders.sex";
   
       $result = DB::select($sql, [
           'venue' => $venue,
           'startDate' => $startDate,
           'endDate' => $endDate,
       ]);
   
       // Append the count of males and females if they are missing in the result set
       $hasMale = false;
       $hasFemale = false;
       foreach ($result as $row) {
           if ($row->sex === 'Male') {
               $hasMale = true;
           } elseif ($row->sex === 'Female') {
               $hasFemale = true;
           }
       }
       if (!$hasMale) {
           $result[] = (object) ['sex' => 'Male', 'count' => 0, 'history' => 0, 'low_hgb' => 0, 'others' => 0];
       }
       if (!$hasFemale) {
           $result[] = (object) ['sex' => 'Female', 'count' => 0, 'history' => 0, 'low_hgb' => 0, 'others' => 0];
       }
   
       return $result;
   }

    public function countDeferral($venue, $startDate, $endDate)
    {
        $sql = "SELECT
                    genders.sex,
                    COALESCE(SUM(category_counts.count), 0) AS count,
                    COALESCE(SUM(category_counts.temporary), 0) AS temporary,
                    COALESCE(SUM(category_counts.permanent), 0) AS permanent
                FROM (
                    SELECT 'Male' AS sex
                    UNION ALL
                    SELECT 'Female' AS sex
                ) AS genders
                LEFT JOIN (
                    SELECT
                        ud.sex,
                        COALESCE(SUM(CASE WHEN d.deferral_type_id = 1 THEN 1 ELSE 0 END), 0) AS temporary,
                        COALESCE(SUM(CASE WHEN d.deferral_type_id = 2 THEN 1 ELSE 0 END), 0) AS permanent,
                        COALESCE(COUNT(*), 0) AS count
                    FROM user_details ud
                    LEFT JOIN deferrals AS d ON ud.user_id = d.user_id
                    LEFT JOIN deferral_types AS dt ON d.deferral_type_id = dt.deferral_type_id
                    WHERE d.venue = :venue
                    AND d.date_deferred BETWEEN :startDate AND :endDate
                    AND d.deferral_type_id IN (1, 2)
                    AND d.donation_type_id = 1
                    GROUP BY ud.sex
                ) AS category_counts ON genders.sex = category_counts.sex
                GROUP BY genders.sex";
  
    
        $result = DB::select($sql, [
            'venue' => $venue,
            'startDate' => $startDate,
            'endDate' => $endDate,
        ]);
    
        return $result;
    }

    public function numberOfUnitsCollected($venue, $startDate, $endDate){
        $sql = "SELECT COUNT(*) AS unit_count FROM blood_bags
                WHERE venue = :venue
                AND date_donated BETWEEN :startDate AND :endDate";
    
        $result = DB::select($sql, [
            'venue' => $venue,
            'startDate' => $startDate,
            'endDate' => $endDate,
        ]);
    
        return $result[0]->unit_count;
    }

    public function countDeferredDonors($venue, $startDate, $endDate){
        $sql = "SELECT COUNT(*) as deferred_count
        FROM deferrals
        WHERE date_deferred BETWEEN :startDate AND :endDate
        AND venue = :venue
        AND (deferral_type_id = 1 OR deferral_type_id = 2)";
        
        $result = DB::select($sql, [
            'venue' => $venue,
            'startDate' => $startDate,
            'endDate' => $endDate,
        ]);
    
        return $result[0]->deferred_count;
    }


    //PATIENT DIRECTED
    public function getTempCategoriesDeferralPD($venue, $startDate, $endDate)
   {
       $sql = "SELECT
                   genders.sex,
                   COALESCE(SUM(category_counts.count), 0) AS count,
                   COALESCE(SUM(category_counts.history), 0) AS history,
                   COALESCE(SUM(category_counts.low_hgb), 0) AS low_hgb,
                   COALESCE(SUM(category_counts.others), 0) AS others
               FROM (
                   SELECT 'Male' AS sex
                   UNION ALL
                   SELECT 'Female' AS sex
               ) AS genders
               LEFT JOIN (
                   SELECT
                       ud.sex,
                       COALESCE(SUM(CASE WHEN d.categories_id = 1 THEN 1 ELSE 0 END), 0) AS history,
                       COALESCE(SUM(CASE WHEN d.categories_id = 2 THEN 1 ELSE 0 END), 0) AS low_hgb,
                       COALESCE(SUM(CASE WHEN d.categories_id = 3 THEN 1 ELSE 0 END), 0) AS others,
                       COALESCE(COUNT(*), 0) AS count
                   FROM user_details ud
                   LEFT JOIN deferrals AS d ON ud.user_id = d.user_id
                   LEFT JOIN deferral_types AS dt ON d.deferral_type_id = dt.deferral_type_id
                   WHERE d.venue = :venue
                   AND d.date_deferred BETWEEN :startDate AND :endDate
                   AND d.categories_id IN (1, 2, 3)
                   AND d.donation_type_id = 2
                   GROUP BY ud.sex
               ) AS category_counts ON genders.sex = category_counts.sex
               GROUP BY genders.sex";
   
       $result = DB::select($sql, [
           'venue' => $venue,
           'startDate' => $startDate,
           'endDate' => $endDate,
       ]);
   
       // Append the count of males and females if they are missing in the result set
       $hasMale = false;
       $hasFemale = false;
       foreach ($result as $row) {
           if ($row->sex === 'Male') {
               $hasMale = true;
           } elseif ($row->sex === 'Female') {
               $hasFemale = true;
           }
       }
       if (!$hasMale) {
           $result[] = (object) ['sex' => 'Male', 'count' => 0, 'history' => 0, 'low_hgb' => 0, 'others' => 0];
       }
       if (!$hasFemale) {
           $result[] = (object) ['sex' => 'Female', 'count' => 0, 'history' => 0, 'low_hgb' => 0, 'others' => 0];
       }
   
       return $result;
   }

   public function countDeferralPD($venue, $startDate, $endDate)
    {
        $sql = "SELECT
                    genders.sex,
                    COALESCE(SUM(category_counts.count), 0) AS count,
                    COALESCE(SUM(category_counts.temporary), 0) AS temporary,
                    COALESCE(SUM(category_counts.permanent), 0) AS permanent
                FROM (
                    SELECT 'Male' AS sex
                    UNION ALL
                    SELECT 'Female' AS sex
                ) AS genders
                LEFT JOIN (
                    SELECT
                        ud.sex,
                        COALESCE(SUM(CASE WHEN d.deferral_type_id = 1 THEN 1 ELSE 0 END), 0) AS temporary,
                        COALESCE(SUM(CASE WHEN d.deferral_type_id = 2 THEN 1 ELSE 0 END), 0) AS permanent,
                        COALESCE(COUNT(*), 0) AS count
                    FROM user_details ud
                    LEFT JOIN deferrals AS d ON ud.user_id = d.user_id
                    LEFT JOIN deferral_types AS dt ON d.deferral_type_id = dt.deferral_type_id
                    WHERE d.venue = :venue
                    AND d.date_deferred BETWEEN :startDate AND :endDate
                    AND d.deferral_type_id IN (1, 2)
                    AND d.donation_type_id = 2
                    GROUP BY ud.sex
                ) AS category_counts ON genders.sex = category_counts.sex
                GROUP BY genders.sex";
  
    
        $result = DB::select($sql, [
            'venue' => $venue,
            'startDate' => $startDate,
            'endDate' => $endDate,
        ]);
    
        return $result;
    }

    public function bloodCollectionPD($venue, $startDate, $endDate) {
        $result = DB::table('user_details as ud')
            ->leftJoin('blood_bags as bb', 'ud.user_id', '=', 'bb.user_id')
            ->join('donor_types as dt', 'ud.donor_types_id', '=', 'dt.donor_types_id')
            ->select(
                DB::raw("CASE WHEN ud.sex = 'Female' THEN 'Female' ELSE 'Male' END AS Gender"),
                DB::raw("SUM(CASE WHEN REPLACE(REPLACE(ud.blood_type, '+', ''), '-', '') = 'O' THEN 1 ELSE 0 END) AS O"),
                DB::raw("SUM(CASE WHEN REPLACE(REPLACE(ud.blood_type, '+', ''), '-', '') = 'A' THEN 1 ELSE 0 END) AS A"),
                DB::raw("SUM(CASE WHEN REPLACE(REPLACE(ud.blood_type, '+', ''), '-', '') = 'B' THEN 1 ELSE 0 END) AS B"),
                DB::raw("SUM(CASE WHEN REPLACE(REPLACE(ud.blood_type, '+', ''), '-', '') = 'AB' THEN 1 ELSE 0 END) AS AB"),
                DB::raw("MIN(TIMESTAMPDIFF(YEAR, ud.dob, CURDATE())) AS MinAge"),
                DB::raw("MAX(TIMESTAMPDIFF(YEAR, ud.dob, CURDATE())) AS MaxAge"),
                DB::raw("SUM(CASE WHEN dt.donor_type_desc = 'First Time' THEN 1 ELSE 0 END) AS 'first_time'"),
                DB::raw("SUM(CASE WHEN dt.donor_type_desc = 'Regular' THEN 1 ELSE 0 END) AS 'regular'"),
                DB::raw("SUM(CASE WHEN dt.donor_type_desc = 'Lapsed' THEN 1 ELSE 0 END) AS 'lapsed'")
            )
            ->where('venue', $venue)
            ->whereBetween('date_donated', [$startDate, $endDate])
            ->where('bb.donation_type_id', 2)
            ->groupBy('Gender')
            ->get();
    
        return $result;
    }

    public function totalUnit($venue, $startDate, $endDate)
    {
        $sql = "SELECT COUNT(*) AS total FROM blood_bags
                WHERE venue = :venue
                AND date_donated BETWEEN :startDate AND :endDate";
    
        $result = DB::select($sql, [
            'venue' => $venue,
            'startDate' => $startDate,
            'endDate' => $endDate,
        ]);
    
        return $result[0]->total;
    }
    
}



