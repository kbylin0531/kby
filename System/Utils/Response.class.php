<?php
/**
 * User: linzh
 * Date: 2016/3/9
 * Time: 16:02
 */
namespace System\Utils;
use System\Core\KbylinException;

/**
 * Class Response 输出控制类
 * @package System\Core
 */
class Response {

    /**
     * 返回的消息类型
     */
    const MESSAGE_TYPE_SUCCESS = 1;
    const MESSAGE_TYPE_FAILURE = 0;

    /**
     * 清空输出缓存
     * @return void
     */
    public static function cleanOutput(){
        ob_get_level() > 0 and ob_end_clean();
    }

    public static function flushOutput(){
        ob_get_level() and ob_end_flush();
    }

    public static function success($message){
        self::ajaxBack([
            '_message'   => $message,
            '_type'      => self::MESSAGE_TYPE_SUCCESS,
        ]);
    }

    public static function failed($message){
        self::ajaxBack([
            '_message'   => $message,
            '_type'      => self::MESSAGE_TYPE_FAILURE,
        ]);
    }

    /**
     * Ajax方式返回数据到客户端
     * 调用改函数将会导致脚本结束
     * @access protected
     * @param mixed $data 要返回的数据
     * @param int $type AJAX返回数据格式
     * @param int $json_option 传递给json_encode的option参数
     * @return void
     * @throws KbylinException
     */
    public static function ajaxBack($data,$type=AJAX_JSON,$json_option=0) {
        self::cleanOutput();
        SEK::disableTrace();
        switch (strtoupper($type)){
            case AJAX_JSON :// 返回JSON数据格式到客户端 包含状态信息
                header('Content-Type:application/json; charset=utf-8');
                exit(json_encode($data,$json_option));
            case AJAX_XML :// 返回xml格式数据
                header('Content-Type:text/xml; charset=utf-8');
                exit(XMLHelper::encodeHtml($data));
            default:
                throw new KbylinException('Invalid output!');
        }
    }

}