<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Hospital extends Model
{
    use HasFactory;

    protected $primaryKey = 'hospitals_id';
    protected $table = 'hospitals';

    protected $fillable = [
        'user_id',
        'hospital_desc',
        'hospital_address',
        'status',
    ];


    public function getAllHospital(){
        $sql = "SELECT hospitals_id,hospital_desc FROM hospitals";

        $result = DB::select($sql);

        return $result;
    }
}
