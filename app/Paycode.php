<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Paycode extends Model
{
    //
    public static function generatePID()
    {
        $tid = rand(1000000000, 9999999999);
        $t = Paycode::where(['pay_id' => $tid])->first();
        if($t == null || count($t) < 1)
            return $tid;
        else
            self::generatePID();
    }
    public static function findByID($pid){
        return Paycode::where('pay_id', $pid)->first();
    }
}
