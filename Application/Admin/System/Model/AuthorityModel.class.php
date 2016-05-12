<?php
/**
 * Created by PhpStorm.
 * User: Zhonghuang
 * Date: 2016/4/19
 * Time: 20:43
 */
namespace Application\Admin\Model;
use System\Library\Model;

class AuthorityModel extends Model{

    /**
     * 获取可以访问的模块列表
     * 注：模块是操作（或控制器）划分的单元
     * @return array
     */
    public function getAccessableModules(){
        return array();
    }

    /**
     * 获取模块下能访问的操作列表
     * 注：控制器是操作划分的单元
     * @param int $mid 模块ID
     * @return array
     */
    public function getAccessableActions($mid){
        return array();
    }

}