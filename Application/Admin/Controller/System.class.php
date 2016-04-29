<?php
/**
 * Created by PhpStorm.
 * User: Zhonghuang
 * Date: 2016/4/15
 * Time: 18:46
 */
namespace Application\Admin\Controller;
use Application\Admin\AdminController;
use Application\Admin\Model\SystemModel;
use Application\Admin\Utils\TemplateTool;
use System\Utils\Response;

class System extends AdminController{


    /**
     * 显示系统管理页面默认主页
     */
    public function index(){
        $this->display();
    }

    /**
     * 显示菜单分组
     * @throws \System\Core\KbylinException
     */
    public function menugroup(){
        $indexModel = new SystemModel();
        $menus = $indexModel->listMenus();
        if(false === $menus){
            Response::ajaxBack(['type'=>'error']);
        }
        $this->assign('menus',TemplateTool::translate($menus));
        $this->display();
    }

    public function updateMenuGroup(array $list){
        $list or Response::failed('You gave the empty message!');
        $indexModel = new SystemModel();
        foreach($list as $item){
            $result = $indexModel->updateMenuItem($item);
        }
        Response::success('修改成功！');
    }

}