<?php
/**
 * Created by PhpStorm.
 * User: kbylin
 * Date: 20/05/16
 * Time: 16:07
 */

namespace Application\Admin\System\Model;


use System\Library\Model;

class ActionGroupModel extends Model{

    const TABLE_NAME = 'kl_action_group';
    const TABLE_FIELDS = [
        'id'        => null,
        'title'     => null,
        'description'=> null,
        'order'     => null,
        'mcode'     => null,
        'code'      => null,
        'status'    => null,
    ];
    //默认倒序
    const TABLE_ORDER = '[order] desc';

    public function clean(){
        $sql = "delete from kl_action_group;";
        return $this->exec($sql);
    }

    public function createActionGroup($code,$mcode){
        return $this->fields([
            'code'  => $code,
            'mcode' => $mcode,
        ])->create();
    }

}