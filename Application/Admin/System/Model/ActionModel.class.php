<?php
/**
 * Created by PhpStorm.
 * User: kbylin
 * Date: 5/4/16
 * Time: 2:38 PM
 */

namespace Application\Admin\System\Model;
use System\Library\Model;

class ActionModel extends Model {

    const TABLE_NAME = 'kl_action';//用于指定本模型对应的表,只允许字符串类型
    const TABLE_FIELDS = [
        'title'     => null,
        'mccode'    => null,
        'description' => null,
        'order'     => null,
        'code'      => null,
        'status'    => null,
    ];//用于指定本模型对应的字段列表,键为字段名称,值为字段默认值

    public function clean(){
        return $this->exec("delete from kl_action");
    }
    public function createAction($code,$mccode){
        return $this->fields([
            'code'      => $code,
            'mccode'    => $mccode,
        ])->create();
    }

}