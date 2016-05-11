<?php
/**
 * Created by PhpStorm.
 * User: linzh_000
 * Date: 2016/3/16
 * Time: 16:52
 */

function json_format_protect(&$val, $key, $type = 'encode')
{
    if (!empty($val) && true !== $val) {
        $val = 'decode' == $type ? urldecode($val) : urlencode($val);
    }
}

function array_map_recursive($filter, $data) {
    $result = array();
    foreach ($data as $key => $val) {
        $result[$key] = is_array($val)
            ? array_map_recursive($filter, $val)
            : call_user_func($filter, $val);
    }
    return $result;
}