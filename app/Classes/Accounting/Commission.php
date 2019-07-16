<?php
namespace App\Classes\Accounting;
use Illuminate\Support\Facades\DB;

class Commission
{
    public static function accumulate($botSettings, $backTestRowId){

        //\App\Classes\LogToFile::add(__FILE__, DB::table($botSettings['botTitle'])->orderBy('id', 'desc')->first()->id);

        DB::table($botSettings['botTitle'])
            ->where('id', $backTestRowId)
            ->update([
                'accumulated_commission' => DB::table($botSettings['botTitle'])->sum('trade_commission')
            ]);
    }
}