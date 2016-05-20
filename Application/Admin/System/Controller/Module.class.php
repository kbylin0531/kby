<?php
/**
 * Created by PhpStorm.
 * User: kbylin
 * Date: 5/12/16
 * Time: 11:55 PM
 */
namespace Application\Admin\System\Controller;
use Application\Admin\Library\Adapter\CwebsActionScanner;
use Application\Admin\Library\AdminController;
use Application\Admin\System\Model\ModuleModel;
use System\Utils\Response;

/**
 * Class Module 模块管理
 * @package Application\Admin\System\Controller
 */
class Module extends AdminController{

    /**
     * 模块管理页面
     */
    public function index(){
        $this->displayManagement();
    }

    public function scanCwebsModule(){
        $scaner = new CwebsActionScanner();
        $modules = $scaner->scan(BASE_PATH . 'App/Lib/Action/')->fetchModule();
        dumpout($modules);
    }

    public function scanKbylinModule(){

    }

    /**
     * 获取模块列表
     * @access public
     * @return void
     */
    public function listModule(){
        $moduleModel = new ModuleModel();

        $list = $moduleModel->listModule();
        if(false === $list){
            Response::failed('获取模块列表失败!'.$list);
        }else{
//            $list[0]['id'] = time().$list[0]['id'];//检验数据确实发生了变化
            Response::ajaxBack($list);
        }
    }

    /**
     * 修改模块信息
     * @param $id
     * @param $title
     * @param $description
     * @param $order
     * @param $status
     */
    public function updateModule($id,$title,$description,$order,$status){
        $moduleModel = new ModuleModel();
        $result = $moduleModel->updateModule([
            'title'     => $title,
            'description'    => $description,
            'order'     => $order,
            'status'    => $status,
        ],$id);
        if(false === $result){
            Response::failed("更新出错:".$moduleModel->error());
        }else{
            Response::success('修改成功');
        }
    }


}