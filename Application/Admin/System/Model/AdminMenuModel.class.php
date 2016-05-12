<?php
/**
 * Created by PhpStorm.
 * User: kbylin
 * Date: 5/12/16
 * Time: 4:18 PM
 */

namespace Application\Admin\Model;
use System\Library\Model;

/**
 * Class AdminMenuModel 系统菜单管理
 * @package Application\Admin\Model
 */
class AdminMenuModel extends Model{

    const TABLE_NAME = 'kl_config_menu';
    const TABLE_FIELDS = [
        'id'        => null,
        'title'     => '[untitled]',
        'value'     => '[]',
        'order'     => 0,
        'icon'      => null,
    ];

    /**
     * 获取顶级菜单设置
     * @return string|null 序列化的配置值,如果配置缺失,则返回null表示空的配置
     */
    public function getTopMenuConfig(){

    }

    /**
     * 写入修改后的顶级菜单设置
     * @param string $config 写入序列化的配置
     * @return bool
     */
    public function setTopMenuConfig($config){

    }

    /**
     * 获取次级菜单配置
     * @param int $id 次级菜单ID,如果为null表示获取全部的次级菜单(以数组的形式返回,id作为键位)
     * @return string|array
     */
    public function getJuniorMenu($id=null){

    }

    /**
     * @param int $id 次级菜单ID
     * @param string $config 次级菜单配置
     */
    public function setJuniorMenu($id,$config){

    }



}