<?php
/**
 * Created by PhpStorm.
 * User: kbylin
 * Date: 5/12/16
 * Time: 4:36 PM
 */
namespace Application\Admin\System\Controller;
use Application\Admin\Library\AdminController;
use Application\Admin\System\Model\MenuModel;
use System\Utils\Response;

/**
 * Class Menu 菜单管理控制器
 * @package Application\Admin\System\Controller
 */
class Menu extends AdminController {

    /**
     * 顶级菜单(顶部的菜单项)和次级菜单(侧边栏菜单)管理
     */
    public function PageManagement(){
        $this->displayManagement();
    }

    /**
     * 获取顶级菜单列表
     */
    public function listTopMenu(){
        $menuModel = new MenuModel();
        $config = $menuModel->getTopMenuConfig();
        if(false === $config){
            Response::failed('获取顶级菜单失败'.$menuModel->getError());
        }else{
            Response::ajaxBack($config,Response::AJAX_STRING);//直接返回文本
        }
    }

    /**
     * @param $title
     * @param null $icon
     */
    public function createMenuItem($title,$icon=null){
        $menuModel = new MenuModel();
        $result = $menuModel->createMenuItem($title,$icon);
        if(false === $result){
            Response::failed('添加菜单项目失败!'.$menuModel->getError());
        }else{
            Response::ajaxBack(['id'=>$result]);
        }
    }


    /**
     * 获取次级菜单列表
     * 列表以菜单项目ID为键
     */
    public function listJuniorMenu(){}

    /**
     * 修改顶级菜单项配置
     * @param string $config 菜单项目序列化的值
     */
    public function updateTopMenu($config){

    }

    /**
     * 修改次级菜单项列表
     * @param int $id 次级菜单项ID
     * @param string $config 次级菜单项配置
     */
    public function updateJuniorMenu($id,$config){

    }

    /**
     * 添加一个次级菜单项
     * @param string $config 次级菜单项配置
     */
    public function createJuniorMenu($config){

    }

    /**
     * 删除次级菜单项列表
     * @param int $id 次级菜单项ID
     */
    public function deleteJuniorMenu($id){

    }

}