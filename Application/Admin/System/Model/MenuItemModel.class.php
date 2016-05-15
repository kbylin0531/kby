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

    public function createMenuItem($fields){
        if($fields instanceof \stdClass){
            $fields['title'] = $fields->title;
            $fields['value'] = $fields->value;
            $fields['icon'] = isset($fields->icon)?$fields->icon:'';
        }
        return $this->fields($fields)->create();
    }
    public function updateMenuItem($fields,$where){
        return $this->where($where)->fields($fields)->update();
    }


}