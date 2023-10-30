<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Hospital extends Model
{
    use HasFactory;

    protected $primaryKey = 'hospitals';
    protected $table = 'hospitals_id';

    protected $fillable = [
        'user_id',
        'hospital_desc',
    ];


    public function getAllHospital(){
        $sql = "SELECT hospital_desc FROM hospitals";

        $result = DB::select($sql);

        return $result;
    }
}
