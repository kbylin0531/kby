<?php
/**
 * Created by PhpStorm.
 * User: kbylin
 * Date: 5/12/16
 * Time: 4:18 PM
 */

namespace Application\Admin\System\Model;
use System\Core\KbylinException;
use System\Library\Model;

/**
 * Class AdminMenuModel 系统菜单管理
 * @package Application\Admin\Model
 */
class MenuModel extends Model{

    const TABLE_NAME = 'kl_config_menu';
    const TABLE_FIELDS = [
        'id'        => null,
        'title'     => null,
        'value'     => null,
        'order'     => null,
        'icon'      => null,
    ];

    /**
     * 获取顶部菜单设置
     * @return array|false 错误发生时返回false
     */
    public function getTopMenuSetting(){
        $config = $this->getTopMenuConfig();
        $config = unserialize($config);
        if(false === $config){
            return $this->error('获取顶级菜单失败!');
        }else{
            $menuItemModel = new MenuItemModel();
            $items = $menuItemModel->listMenuItems(true);
            $this->_arrangeMenu($config, $items);
        }
        return $config;
    }

    /**
     * get all menu config with arranged
     * @return array|bool return array if success while false on failed
     */
    public function getMenuConfig(){
        $configs = $this->select();
        if(false === $configs or empty($configs)){
            return false;
        }else{
            $temp = [];
            //menu item
            $menuItemModel = new MenuItemModel();
            $items = $menuItemModel->listMenuItems(true);
            if(empty($items)) return false;

            foreach ($configs as $config){
                $id = $config['id'];
                $config = unserialize($config['value']);
                $this->_arrangeMenu($config, $items);
                $temp[$id] = $config;
            }
            return $temp;
        }
    }


    private function _arrangeMenu(array &$config,array $items){
        foreach ($config as &$item){
            $id = $item['parent'];
            if(!isset($items[$id])) continue;
            $item = array_merge($item,$items[$id]);
            if(isset($item['children'])){
                $this->_arrangeMenu($item['children'],$items);
            }
        }
    }

    /**
     * 获取顶级菜单设置
     * 注:顶级菜单的ID等于1
     * @return array|bool array中的title和value可能是有用的值,返回false表示发生了错误
     */
    public function getTopMenuConfig(){
        $config = $this->where('id = 1')->select();
//        dumpout($config);
        if(isset($config[0]['value'])){
            return $config[0]['value'];
        }
        return false;
    }

    /**
     * @param array $sideset
     * @param int $id
     * @return bool
     */
    public function setSideMenu($sideset,$id){
        if(is_string($sideset)) $sideset = json_decode($sideset);
        is_array($sideset) or KbylinException::throwing('Menu setting should be array/string(json)');

        $config = $this->travelThrough($sideset);
        if(empty($config)){
            $config = '[]';
        }else{
            $config = serialize($config);
        }

        $where = 'parent = '.intval($id);
        //check if exist
        $result = $this->where($where)->count();
        if(false === $result){
            return false;
        }
        if($result){
            return $this->fields([
                'value' => $config,
            ])->where($where)->update();
        }else{
            return $this->fields([
                'value'     =>  $config,
                'parent'    =>  $id,
            ])->create();
        }
    }

    /**
     * @param array $topset
     * @return bool
     */
    public function setTopMenu($topset){
        if(is_string($topset)) $topset = json_decode($topset);
        is_array($topset) or KbylinException::throwing('Menu setting should be array/string(json)');

        $config = $this->travelThrough($topset);
        if(empty($config)){
            $config = '[]';
        }else{
            $config = serialize($config);
        }
//        dumpout($config);;
        return $this->fields([
            'value' => $config,
        ])->where('id = 1')->update();
    }

    private function travelThrough(array $topset){
        $result = [];
        foreach ($topset as $object){
            $item = [];
            $item['id'] = $object->id;
            if(isset($object->children)){
                $item['children'] = $this->travelThrough($object->children);
            }
            $result[] = $item;
        }
        return $result;
    }

    /**
     * 写入修改后的顶级菜单设置
     * @param string $config 写入序列化的配置
     * @return bool
     */
    public function setTopMenuConfig($config){
        if(!is_string($config)) $config = serialize($config);
        return $this->where('id = 1')->fields([
            'value' => $config,
        ])->update();
    }

}