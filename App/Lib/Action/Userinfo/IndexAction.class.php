<?php
/**
 * 系统首页
 * User: cwebs
 * Date: 13-11-23
 * Time: 上午8:47
 */
class IndexAction extends RightAction {
    /**
     * 班级管理首页
     */
    public function index() {
        $model = new CommonModel();
        $this->assign('yearterm',$model->getYearTerm('J'));
        $this->display();
    }

}