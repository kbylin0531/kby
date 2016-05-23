<?php

/**
 * Created by PhpStorm.
 * User: kbylin
 * Date: 23/05/16
 * Time: 10:09
 */
namespace Application\Admin\Member\Model;
use System\Library\Model;

class MemberModel extends Model {

    const TABLE_NAME = 'users';

    public function getUser($username){
        $result = $this->fields('username,password,roles')->where(['username'=>$username])->find();
//        dumpout($result);
        return $result;
    }

}