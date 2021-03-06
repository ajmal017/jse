<?php
/**
 * Created by PhpStorm.
 * User: slinger
 * Date: 6/22/2019
 * Time: 12:53 PM
 */

namespace App\Classes\System;

/**
 * Used for decoding Laravel job payload strong.
 * Some fields can not be proceeded regular way.
 * This output is used in Que.vue
 *
 * Class Serialize
 * @package App\Console\Commands
 */
abstract class Serialize {
    public function getAvoidedFields() {
        return array ();
    }
    public function toArray($command) {
        $temp = (array)$command;
        $array = array ();

        foreach ( $temp as $k => $v ) {
            $k = preg_match ( '/^\x00(?:.*?)\x00(.+)/', $k, $matches ) ? $matches [1] : $k;
            if (in_array ( $k, $this->getAvoidedFields () )) {
                $array [$k] = "";
            } else {

                // if it is an object recursive call
                if (is_object ( $v ) && $v instanceof Serialize) {
                    $array [$k] = $v->toArray();
                }
                // if its an array pass por each item
                if (is_array ( $v )) {

                    foreach ( $v as $key => $value ) {
                        if (is_object ( $value ) && $value instanceof Serialize) {
                            $arrayReturn [$key] = $value->toArray();
                        } else {
                            $arrayReturn [$key] = $value;
                        }
                    }
                    $array [$k] = $arrayReturn;
                }
                // if it is not a array and a object return it
                if (! is_object ( $v ) && !is_array ( $v )) {
                    $array [$k] = $v;
                }
            }
        }
        return $array;
    }
}