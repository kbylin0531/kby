<?php
/**
 * Created by PhpStorm.
 * User: kbylin
 * Date: 5/4/16
 * Time: 2:38 PM
 */

namespace Application\Admin\Model;
use System\Library\Model;

class ActionModel extends Model {

    protected $_table = 'kl_action';

    protected $_fields = [
        'title'     => null,
        'mccode'    => null,
        'description' => null,
        'order'     => null,
        'code'      => null,
        'status'    => null,
    ];

}