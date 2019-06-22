<?php
/**
 * Created by PhpStorm.
 * User: slinger
 * Date: 6/22/2019
 * Time: 1:03 PM
 */

namespace App\Classes\System;

/**
 * This class is used in order to inherit Serialize class.
 * When it is used from controller - extension is already used.
 *
 * Class SerializeExtention
 * @package App\Classes\System
 */
class SerializeExtention extends Serialize
{
    public function __construct()
    {
    }
}