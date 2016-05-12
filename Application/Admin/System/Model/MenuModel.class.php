<?php
/**
 * Created by PhpStorm.
 * User: kbylin
 * Date: 5/12/16
 * Time: 4:18 PM
 */

namespace Application\Admin\System\Model;
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
     * 获取顶级菜单设置
     * 注:顶级菜单的ID等于1
     * @return array|bool array中的title和value可能是有用的值,返回false表示发生了错误
     */
    public function getTopMenuConfig(){
        $config = $this->where('id = 1')->select();
        if(isset($config[0]['value'])){
            return $config[0]['value'];
        }
        return false;
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