<?php
/**
 * Created by PhpStorm.
 * User: kbylin
 * Date: 5/15/16
 * Time: 12:16 PM
 */

namespace Application\Admin\System\Model;
use System\Library\Model;

/**
 * Class MenuItemModel 菜单项管理
 * @package Application\Admin\System\Model
 */
class MenuItemModel extends Model {

    const TABLE_NAME = 'kl_menu_item';
    const TABLE_FIELDS = [
        'id'        => null,
        'title'     => null,
        'value'     => null,
        'icon'      => null,
    ];

    /**
     * update the menu item by id
     * @param $id
     * @param $title
     * @param $icon
     * @param $href
     * @return bool
     */
    public function updateMenuItemById($id,$title,$icon,$href){
        return $this->fields([
            'title' => $title,
            'icon' => $icon,
            'href' => $href,
        ])->where('id = '.intval($id))->update();
    }

    /**
     * delete menu item by id
     * @param $id
     * @return bool
     */
    public function deleteMenuItemById($id){
        return $this->where('id = '.intval($id))->delete();
    }

    /**
     * 创建菜单项目
     * @param $id
     * @param $title
     * @param string $href
     * @param string $icon
     * @return bool
     */
    public function createMenuItem($id,$title,$href,$icon) {
        $result = $this->fields([
            'id'        => $id, // 前台保证唯一
            'title'     => $title,
            'href'     => $href,
            'icon'      => $icon,
        ])->create();
        return $result;
    }

    /**
     * 获取菜单项列表
     * @param bool $idaskey 是否将id作为键
     * @return array|bool
     */
    public function listMenuItems($idaskey=false){
        $items = $this->select();
        if($idaskey and $items){
            $temp = [];
            foreach ($items as $key=>$item){
                $temp[$item['id']] = $item;
                unset($temp[$item['id']]['id']);
            }
            $items =$temp;
        }
        return $items;
    }

    public function hasMenuItemById($id){
        return $this->where('id = '.intval($id))->count();
    }

    public function updateMenuItem($id,$title,$href,$icon){
        return $this->fields([
            'title'     => $title,
            'href'     => $href,
            'icon'      => $icon,
        ])->where('id='.intval($id))->update();
    }


}