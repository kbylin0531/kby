<?php
/**
 * Created by PhpStorm.
 * User: kbylin
 * Date: 5/4/16
 * Time: 11:39 AM
 */

namespace Application\Admin\Model;
use System\Library\Model;

/**
 * Class ModuleModel 模块模型
 * @package Application\Admin\Model
 */
class ModuleModel extends Model{

    protected $_table = 'kl_module';

    protected $_fields = [
        'title'     => null,
        'parent'    => null,
        'description' => null,
        'order'     => null,
        'code'      => null,
        'status'    => null,
    ];

}