<?php
/**
 * Created by PhpStorm.
 * User: Zhonghuang
 * Date: 2016/4/15
 * Time: 18:46
 */
namespace Application\Admin\Library;

use Application\Admin\Model\AuthorityModel;
use System\Traits\Controller\Render;
use System\Utils\Assets;

class AdminController {

    use Render;

    public function __construct(){

        dumpout(Assets::load('menu'));
        $authModel = new AuthorityModel();
        $modules = $authModel->getAccessableModules();
        $this->assign('modules',$modules);

        $actions = $authModel->getAccessableActions(1);
        $this->assign('actions',$actions);

        $this->checkLogin() or $this->redirectLoginPage();
    }

    /**
     * 分配管理员页面信息
     */
    protected function assignAdminPage(){
        $this->assignUserInfoList();
        $this->assignModulesList();
        $this->assignActionsList();
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