<?php
/**
 * User: linzh_000
 * Date: 2016/3/15
 * Time: 15:48
 */
namespace System\Core;

use System\Utils\SEK;

class KbylinException extends \Exception{
    public function __construct(){

        $args = func_get_args();
        $this->message = var_export($args,true);

        $place = SEK::getCallPlace(true,SEK::CALL_PLACE_FORWARD);
        dumpout($place,$args);

    }
}