<?php

//todo:班级选课管理
class course_countAction extends RightAction
{

    private $md;        //存放模型对象
    private $base;      //路径

    public function __construct(){
        parent::__construct();
        $this->md=new Sqlsrvmodel();
        $this->assign('schools',getSchoolList());
        $this->base='course_count/';
    }


    public function one(){
        $this->display();
    }

    public function two(){
        $this->display();
    }

    public function three(){
        $this->display();
    }

    public function four(){
        $this->display();
    }

    public function five(){
        $this->display();
    }

    public function six(){
        $this->display();
    }

    public function seven(){
        $this->display();
    }






}