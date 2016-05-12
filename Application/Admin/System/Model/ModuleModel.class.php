<?php
/**
 * Created by PhpStorm.
 * User: kbylin
 * Date: 5/4/16
 * Time: 11:39 AM
 */

namespace Application\Admin\Model;
use System\Library\Model;
use System\Utils\SEK;

/**
 * Class ModuleModel 模块模型
 * @package Application\Admin\Model
 */
class ModuleModel extends Model{

    const TABLE_NAME = 'kl_module';
    const TABLE_FIELDS = [
        'id'        => null,
        'title'     => null,
        'parent'    => null,
        'description' => null,
        'order'     => null,
        'code'      => null,
        'status'    => null,
    ];
    //默认倒序
    const TABLE_ORDER = '[order] desc';


    /**
     * 获取模块列表
     * @return array|bool
     * @throws \System\Core\KbylinException
     */
    public function listModule(){
        return $this->select();
    }

    /**
     * 更新模块
     * @param array $info
     * @param int $id
     * @return bool
     * @throws \System\Core\KbylinException
     */
    public function updateModule(array $info,$id){
//        dumpout($info,$id);

        $fields = SEK::ghostArray($info,[
            'title','description','order','status' //镜像这些数据防止对多余的部分进行修改
        ]);
        if(empty($fields) or empty($id)){
            $this->setError('没有指定更新的字段!');
            return false;
        }

        return $this->fields($fields)->where(['id'=>$id])->update();
    }

}