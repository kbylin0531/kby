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
use System\Core\Storage;
use System\Utils\Response;

/**
 * Class Menu 菜单管理控制器
 * @package Application\Admin\System\Controller
 */
class Menu extends AdminController {

    /**
     * 顶级菜单(顶部的菜单项)和次级菜单(侧边栏菜单)管理
     */
    public function Management(){
        $this->displayManagement();
    }

    /**
     * get the menu-config list
     * @throws \System\Core\KbylinException
     */
    public function listMenuConfig(){
        $menuModel = new MenuModel();
        $config  = $menuModel->getMenuConfig();
        false === $config and Response::failed('Failed to get menu config!'.$menuModel->error());
        Response::ajaxBack($config);//直接返回文本
    }

    /**
     * update the side-config
     * @param string $sideset
     * @param int $id
     */
    public function saveSidebarMenu($sideset,$id){
        $sideset = json_decode($sideset);
        if(!is_array($sideset)) Response::failed('无法解析前台传递的序列化的信息!');
        $menuItemModel = new MenuItemModel();
        $menuItemModel->beginTransaction();

        if(!$this->_setMenuConfigIgnoreExtence($sideset,$menuItemModel,true)){
            Response::failed('设置出错:'.$menuItemModel->error());
        }

        //更新菜单配置
        $menuModel = new MenuModel();
        if(!$menuModel->setSideMenu($sideset,$id)){
            $menuItemModel->rollBack();
            Response::failed('更新顶部配置时出现了错误!'.$menuModel->error());
        }
        $menuItemModel->commit();

        Storage::unlink(RUNTIME_PATH.'Template/');
        Response::success('成功更新了了'.$this->_success['u'].'条数据,添加了'.$this->_success['c'].'条数据!');
    }
    /**
     * 需要注意的是,更新过之后需要刷新模板引擎的缓存
     * @param $topset
     */
    public function saveHeaderConfig($topset){
        $topset = json_decode($topset);
        is_array($topset) or Response::failed('无法解析前台传递的序列化的信息!');
        $menuItemModel = new MenuItemModel();
        $menuItemModel->beginTransaction();

        if(!$this->_setMenuConfigIgnoreExtence($topset,$menuItemModel,true)){
            Response::failed('设置出错:'.$menuItemModel->error());
        }

        //更新菜单配置
        $menuModel = new MenuModel();
        if(!$menuModel->setTopMenu($topset)){
            $menuItemModel->rollBack();
            Response::failed('更新顶部配置时出现了错误!'.$menuModel->error());
        }
        $menuItemModel->commit();

        Storage::unlink(RUNTIME_PATH.'Template/');
        Response::success('成功更新了了'.$this->_success['u'].'条数据,添加了'.$this->_success['c'].'条数据!');
        //unlink the temp to refresh
    }

    /**
     * be shared of result
     * @var array
     */
    private $_success = [];
    /**
     * 设置菜单项,包括修改和添加
     * to set menu config ignore if is the menu config has setup brefore
     * it will create if not exit but modified if exist
     * @param array $configs
     * @param MenuItemModel $model one transaction in it
     * @param bool $reset used by recursion
     * @return bool it will return false immediately if error occur ,true will return if all right
     */
    private function _setMenuConfigIgnoreExtence($configs, $model, $reset=true){
        $reset and $this->_success =  ['c'=>0,'u'=>0];
        foreach ($configs as $object){
            if(empty($object->id) or empty($object->title)){
                Response::failed('Id/Title should not be empty!');
            }
            $object->href = isset($object->href)?$object->href:'#';
            $object->icon = isset($object->icon)?$object->icon:'';

            //检查ID是否存在
            $count = $model->hasMenuItemById($object->id);
            if(false === $count) Response::failed('failed to query whether if id exist!'.$model->error());

//            dump($count,[$object->id,$object->title,$object->href,$object->icon]);
            $result = call_user_func_array([$model,$count?'updateMenuItem':'createMenuItem'], [$object->id,$object->title,$object->href,$object->icon]);

            if(false === $result) {
                $model->rollBack();
                Response::failed(($count?'修改失败':'添加失败').$model->error());
            }else{
                $this->_success[$count?'u':'c']++;
            }

            //递归执行
            if(!empty($object->children)){
                if(false === $this->_setMenuConfigIgnoreExtence($object->children,$model,false)){
                    $model->rollBack();
                    Response::failed('设置子菜单失败');
                }
            }
        }
        return true;
    }

    /**
     * update the menu item
     * @param $id
     * @param $title
     * @param $icon
     * @param $href
     */
    public function updateMenuItem($id,$title,$icon,$href){
        $menuItemModel = new MenuItemModel();
        if(false === $menuItemModel->updateMenuItemById($id, $title, $icon, $href)){
            Response::failed('Failed to update the menu item!'.$menuItemModel->error());
        }else{
            Response::success('Success to update the menu item');
        }
    }

    /**
     * delete menu item by id
     * @param string|int $id
     */
    public function deleteMenuItem($id){
        $menuItemModel = new MenuItemModel();
        if(false === $menuItemModel->deleteMenuItemById($id)){
            Response::failed('Failed to update the menu item!'.$menuItemModel->error());
        }else{
            Response::success('Success to update the menu item');
        }
    }


}