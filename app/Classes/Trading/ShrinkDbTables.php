<?php
/**
 * Created by PhpStorm.
 * User: slinger
 * Date: 7/17/2019
 * Time: 2:16 AM
 */

namespace App\Classes\Trading;
use \Illuminate\Support\Facades\DB;
/**
 * Once there more than x bars in tables - delete the first record.
 * We get these table too heavy. The server gets overloaded and front end works slow as well.
 * Called once per bar from CandleMaker.php
 */
class ShrinkDbTables
{
    public static function deleteRow($botSettings){
        if(DB::table($botSettings['botTitle'])->count() > 100){
            DB::table($botSettings['botTitle'])
                ->delete(
                    DB::table($botSettings['botTitle'])
                        ->orderBy('id', 'asc')->take(1)->value('id')
                );
            DB::table('signal_' . substr($botSettings['botTitle'], -1))
                ->delete(
                    DB::table('signal_' . substr($botSettings['botTitle'], -1))
                        ->orderBy('id', 'asc')->take(1)->value('id')
                );
        }
    }
}