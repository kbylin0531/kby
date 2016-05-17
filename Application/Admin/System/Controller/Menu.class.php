<?php
/**
 * Created by PhpStorm.
 * User: kbylin
 * Date: 5/12/16
 * Time: 4:36 PM
 */
namespace Application\Admin\System\Controller;
use Application\Admin\Library\AdminController;
use Application\Admin\System\Model\MenuItemModel;
use Application\Admin\System\Model\MenuModel;
use System\Core\Dao;
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
        $config  = $menuModel->getTopMenuSetting();
        if(false === $config) Response::failed('获取顶部菜单失败!'.$menuModel->error());
        Response::ajaxBack($config);//直接返回文本
    }
    /**
     * 需要注意的是,更新过之后需要刷新模板引擎的缓存
     * @param $topset
     */
    public function saveTopMenu($topset){
        $topset = json_decode($topset);
        if(!is_array($topset)){
            Response::failed('无法解析前台传递的序列化的信息!');
        }
        $menuItemModel = new MenuItemModel();
        $menuItemModel->beginTransaction();

        if(!$this->_setMenu($topset,$menuItemModel,true)){
            Response::failed('设置出错:'.$menuItemModel->error());
        }

        //更新菜单配置
        $menuModel = new MenuModel();
        if(!$menuModel->setTopMenu($topset)){
            $menuItemModel->rollBack();
            Response::failed('更新顶部配置时出现了错误!'.$menuModel->error());
        }

        $menuItemModel->commit();

        Response::success('成功更新了了'.$this->_success['u'].'条数据,添加了'.$this->_success['c'].'条数据!');
    }

    private $_success =null;
    /**
     * 设置菜单项,包括修改和添加
     * @param array $topset
     * @param MenuItemModel $model
     * @param bool $reset
     * @return bool
     */
    private function _setMenu($topset,$model,$reset=false){
        $reset and $this->_success =  ['c'=>0,'u'=>0];
        foreach ($topset as $object){
            if(empty($object->id) or empty($object->title)){
                Response::failed('Id/Title should not be empty!');
            }
            $object->href = isset($object->href)?$object->href:'#';
            $object->icon = isset($object->icon)?$object->icon:'';

            //检查ID是否存在
            $count = $model->hasMenuItemById($object->id);
            if(false === $count) Response::failed('failed to query whether if id exist!');

//            dump($count,[$object->id,$object->title,$object->href,$object->icon]);
            $result = call_user_func_array([$model,$count?'updateMenuItem':'createMenuItem'], [$object->id,$object->title,$object->href,$object->icon]);

            if(false === $result) {
                $model->rollBack();
                Response::failed(($count?'修改失败':'添加失败').$model->getError());
            }else{
                $this->_success[$count?'u':'c']++;
            }

            //递归执行
            if(!empty($object->children)){
                if(false === $this->_setMenu($object->children,$model)){
                    $model->rollBack();
                    Response::failed('设置子菜单失败');
                }
            }
        }
        return true;
    }


}