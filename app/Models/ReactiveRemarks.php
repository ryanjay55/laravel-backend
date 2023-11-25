<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ReactiveRemarks extends Model
{
    use HasFactory;
    protected $primaryKey = 'reactive_remarks_id';
    protected $table = 'reactive_remarks';

    protected $fillable = [
        'reactive_remarks_desc',
        'status'
    ];

    public function getReactiveRemarks(){
        $sql = "SELECT reactive_remarks_id,reactive_remarks_desc from reactive_remarks";

        $result = DB::connection('mysql')->select($sql);

        return $result;
    }
}
