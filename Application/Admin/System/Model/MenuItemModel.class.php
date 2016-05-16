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
     * 创建菜单项目
     * @param $id
     * @param $title
     * @param string $href
     * @param string $icon
     * @return bool
     */
    public function createMenuItem($id,$title,$href,$icon){
        $result = $this->fields([
            'id'        => $id, // 前台保证唯一
            'title'     => $title,
            'href'     => $href,
            'icon'      => $icon,
        ])->create();
        return $result;
    }

    public function listMenuItems(){
        return $this->select();
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