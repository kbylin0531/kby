<?php
/**
 * Created by PhpStorm.
 * User: linzh_000
 * Date: 2016/2/23
 * Time: 15:20
 */
class MoralModel extends CommonModel{


    public function deleteById($id){
        return $this->deleteRecords('moral_info',array('id' => $id));
    }


    public function select($studentno)
    {
        $sql = 'select * from MORAL_INFO WHERE  studentno = :stu';
        $list = $this->doneQuery($this->sqlQuery($sql, array(':stu' => $studentno)));
        if(is_string($list)){
            return $list;
        }
        return array(
            'total' => count($list),
            'rows'  => $list,
        );
    }

}