<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2015/8/12
 * Time: 13:30
 */
class CourseModel extends CommonModel{

    /**
     * 获取课程信息
     * @param $courseno
     * @return array|int|string
     */
    public function getCourseInfo($courseno){
        return $this->getTable('COURSES',array(
            'COURSENO'   => substr($courseno,0,7),
        ));
    }

    /**
     * 更新课程
     * @param $courseno
     * @param $fields
     * @return int|string
     */
    public function updateCourse($courseno,$fields){
        return $this->updateRecords('COURSES',$fields,array(
            'COURSENO' => $courseno,
        ));
    }

}