<?php
/**
 * Created by PhpStorm.
 * User: linzh
 * Date: 2016/3/8
 * Time: 20:15
 */
namespace System\Traits\Controller;
use System\Utils\Network;

trait Location{

    /**
     * 页面跳转
     * @param string $compo   形式如'Cms/install/third' 的action定位
     * @param array $params   URL参数
     * @param int $time       等待时间
     * @param string $message 跳转等待提示语
     * @return void
     */
    public function redirect($compo,array $params=[],$time=0,$message=''){
        Network::redirect(Network::url($compo,$params),$time,$message);
    }

    /**
     * 默认跳转操作 支持错误导向和正确跳转
     * 调用模板显示 默认为public目录下面的success页面
     * 提示页面为可配置 支持模板标签
     * @param string $message 提示信息
     * @param string $title 跳转页面标题
     * @param bool $status 页面状态,true为积极的一面，false为消极的一面
     * @param bool $jumpback 页面操作，true时表示返回之前的页面，false时提示完毕后自动关闭窗口
     * @param int $wait 页面等待时间
     * @return void
     * @throws \Exception
     */
    protected static function jump($message,$title='跳转',$status=true,$jumpback=true,$wait=1) {
        Network::sendNocache(true);//保证输出不受静态缓存影响
        $vars = [];
        $vars['wait'] = $wait;
        $vars['title'] = $title;
        $vars['message'] = $message;
        $vars['status'] = $status?1:0;

        $vars['jumpurl'] = $jumpback?
            'javascript:history.back(-1);':
            'javascript:window.close();';

        \Kbylin::loadTemplate('jump',$vars);
    }

    /**
     * 跳转到成功显示页面
     * @param string $message 提示信息
     * @param int $waittime 等待时间
     * @param string $title 显示标题
     * @throws \Exception
     */
    public function success($message,$waittime=1,$title='success'){
        self::jump($message,$title,true,1,$waittime);
    }

    /**
     * 跳转到错误信息显示页面
     * @param string $message 提示信息
     * @param int $waittime 等待时间
     * @param string $title 显示标题
     * @throws \Exception
     */
    public function error($message,$waittime=3,$title='error'){
        self::jump($message,$title,false,1,$waittime);
    }


}