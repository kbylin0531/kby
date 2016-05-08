<?php
/**
 * Created by PhpStorm.
 * User: Zhonghuang
 * Date: 2016/4/15
 * Time: 18:46
 */
namespace Application\Admin\Library;

use System\Library\Controller;
use System\Utils\Assets;
use System\Utils\SEK;

class AdminController extends Controller{


    public function __construct(){
        $this->checkLogin() or $this->redirectLoginPage();
    }

    protected function displayManagement($template = null, $cache_id = null, $compile_id = null, $parent = null){
        //加载模块和菜单

        $this->assign('page',json_encode($this->getPageInfo()));
        $this->assign('user',json_encode($this->getUserInfo()));
        $this->assign('modules',$this->getModules());
        $this->assign('actions',$this->getActions());

        //获取调用自己的函数
        null === $template and $template = SEK::getCallPlace(SEK::CALL_ELEMENT_FUNCTION,SEK::CALL_PLACE_FORWARD)[SEK::CALL_ELEMENT_FUNCTION];
        $this->display($template , $cache_id , $compile_id, $parent);
    }

    protected function getUserInfo(){
        return Assets::load('_user');
    }
    /**
     * 分配管理员页面信息
     */
    protected function assignAdminPage(){
        $this->assignUserInfoList();
        $this->assignModulesList();
        $this->assignActionsList();
    }

    private function getPageInfo(){
        return Assets::load('_page');
    }

    private function getModules(){
        return Assets::load('_menu');
    }

    private function getActions(){
        return Assets::load('_action');
    }

    /**
     * 分配用户信息列表
     */
    protected function assignUserInfoList(){}

    /**
     *  分配模块信息列表
     */
    protected function assignModulesList(){}

    /**
     * 分配模块下的操作信息列表
     */
    protected function assignActionsList(){}

    private function checkLogin(){
        return true;
    }

    private function redirectLoginPage(){
        echo '你还未登录系统';
    }

}