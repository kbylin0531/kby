<?php
/**
 * User: linzh
 * Date: 2016/3/14
 * Time: 20:50
 */
const PAGE_TRACE_ON = true;
const DEBUG_MODE_ON = true;

include '../System/Kbylin.class.php';
(new Kbylin())->liten(false)->inspect(false)->start();