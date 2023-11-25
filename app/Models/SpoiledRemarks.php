<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class SpoiledRemarks extends Model
{
    use HasFactory;
    protected $primaryKey = 'spoiled_remarks_id';
    protected $table = 'spoiled_remarks';

    protected $fillable = [
        'spoiled_remarks_desc',
        'status',
    ];

    public function getSpoiledRemarks(){
        $sql = "SELECT spoiled_remarks_id,spoiled_remarks_desc from spoiled_remarks";

        $result = DB::connection('mysql')->select($sql);

        return $result;
    }
}
