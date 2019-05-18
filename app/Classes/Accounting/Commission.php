<?php
namespace App\Classes\Accounting;
use Illuminate\Support\Facades\DB;

class Commission
{
    public static function accumulate($botSettings){
        DB::table($botSettings['botTitle'])
            ->where('id', DB::table($botSettings['botTitle'])->orderBy('id', 'desc')->first()->id)
            ->update([
                'accumulated_commission' => DB::table($botSettings['botTitle'])->sum('trade_commission')
            ]);
    }
}