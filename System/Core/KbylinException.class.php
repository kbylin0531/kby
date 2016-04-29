<?php
/**
 * User: linzh_000
 * Date: 2016/3/15
 * Time: 15:48
 */
namespace System\Core;

class KbylinException extends \Exception{
    public function __construct(){
        $args = func_get_args();
        $this->message = var_export($args,true);
    }
}