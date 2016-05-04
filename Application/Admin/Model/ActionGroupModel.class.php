<?php
/**
 * Created by PhpStorm.
 * User: kbylin
 * Date: 5/4/16
 * Time: 12:33 PM
 */

namespace Application\Admin\Model;
use System\Library\Model;

class ActionGroupModel extends Model {

    protected $_table = 'kl_action_group';

    protected $_fields = [
        'title'     => null,
        'mcode'    => null,
        'description' => null,
        'order'     => null,
        'code'      => null,
        'status'    => null,
    ];

}