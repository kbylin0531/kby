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
    public function getHeaderMenuConfig(){
        $config = $this->where('id = 1 and parent = 1')->select();
        if($config){
            $config =  $this->applyMenuItem($config);
            if (isset($config[1]['config'])) {
                return $config[1]['config'];
            }
        }
        return false;
    }

    /**
     * @return array|bool
     */
    public function getSidebarMenuConfig(){
        $configs = $this->where('id != 1 and parent != 1')->select();

        if($configs){
            return $this->applyMenuItem($configs);
        }
        return false;
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
            return $this->applyMenuItem($configs);
        }
    }

    private function applyMenuItem(array $menuconfigs){
        $newconf = [];
        if($menuconfigs){
            //menu item
            $menuItemModel = new MenuItemModel();
            $items = $menuItemModel->listMenuItems(true);

            if(empty($items)) return false;
//            dumpout($configs,$items);
            foreach ($menuconfigs as &$config){
                $parent = $config['parent'];
                $title  = $config['title'];

                if(!empty($config['value'])){
                    $config = unserialize($config['value']);
                    $this->_arrangeMenu($config, $items);
                }else{
                    $config = [];
                }
                $newconf[$parent] = [
                    'title' => $title,
                    'config'=> $config,
                ];
            }
        }
        return $newconf;
    }

    /**
     * apply menuitem to menu config
     * @param array $config
     * @param array $items
     */
    private function _arrangeMenu(array &$config,array $items){
        foreach ($config as &$configitem){
            $id = $configitem['id'];
            if(!isset($items[$id])) continue;
            $configitem = array_merge($configitem,$items[$id]);
            if(isset($configitem['children'])){
                $this->_arrangeMenu($configitem['children'],$items);
            }
        }
    }

    /**
     * @param array $sideset
     * @param int $id
     * @return bool
     */
    public function setSideMenu($sideset,$id){
        if(is_string($sideset)) $sideset = json_decode($sideset);
        is_array($sideset) or KbylinException::throwing('Menu setting should be array/string(json)');

        $config = $this->_travelThrough($sideset);
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

        $config = $this->_travelThrough($topset);
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

    /**
     * @param array $topset
     * @return array
     */
    private function _travelThrough(array $topset){
        $result = [];
        foreach ($topset as $object){
            $item = [];
            $item['id'] = $object->id;
            if(isset($object->children)){
                $item['children'] = $this->_travelThrough($object->children);
            }
            $result[] = $item;
        }
        return $result;
    }

}