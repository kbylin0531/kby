<?php
/**
 * Created by PhpStorm.
 * User: Zhonghuang
 * Date: 2016/4/15
 * Time: 18:46
 */
namespace Application\Admin\Controller;
use Application\Admin\Library\ActionManager;
use Application\Admin\Library\Adapter\CwebsActionScannerAdapter;
use Application\Admin\Library\AdminController;
use Application\Admin\Model\ActionGroupModel;
use Application\Admin\Model\ActionModel;
use Application\Admin\Model\ModuleModel;
use Application\Admin\Model\SystemModel;
use System\Utils\Response;

class System extends AdminController{

    /**
     * 显示系统管理页面默认主页
     */
    public function index(){
        $this->displayManagement();
    }

    /**
     * 管理员首页菜单控制
     */
    public function PageAdminMenuConfig(){
        $this->displayManagement();
    }

    /**
     * 模块管理页面
     */
    public function PageModuleManagement(){
        $this->displayManagement();
    }

    /**
     * 操作管理页面
     */
    public function PageActionManagement(){
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
            Response::failed("更新出错:".$moduleModel->getError());
        }else{
            Response::success('修改成功');
        }
    }



    public function scan(){
        $manager = new ActionManager(new CwebsActionScannerAdapter());
        $modules = $manager->scan(BASE_PATH.'App/Lib/Action/')->fetchModule();

        $moduleModel = new ModuleModel();
        $actionGroupModel = new ActionGroupModel();
        $actionModel = new ActionModel();
        $result = [
            'success'   => [
                'm'=>0,
                'c'=>0,
                'a'=>0,
            ],
            'failed'    => [
                'm'=>0,
                'c'=>0,
                'a'=>0,
            ],
            'error' => '',
        ];

        //错误信息

        //清空之前的操作的数据
        $moduleModel->clean();
        $actionGroupModel->clean();
        $actionModel->clean();

        foreach ($modules as $module){
            if($moduleModel->field('code',$module)->create()){
                $result['success']['m']++;
            }else{
                $result['failed']['m']++;
                $error = $actionModel->getError();
                $error and $result['error'] .= "{$module}: {$error} \n";
            }
            $controllers = $manager->fetchController($module);
            foreach ($controllers as $controller=>$controllerpath){
                if($actionGroupModel->fields([
                    'mcode' => $module,
                    'code'  => $controller,
                    ])->create())
                {
                    $result['success']['c']++;
                }else{
                    $result['failed']['c']++;
                    $error = $actionModel->getError();
                    $error and $result['error'] .= "{$module}@{$controller}: {$error} \n";
                }
                $actions = $manager->fetchAction($module,$controller);
//                dump($module,$controller,$actions);//打印模块和控制器名称
                foreach ($actions as $action){
                    if($actionModel->fields([
                        'mccode' => "{$module}@{$controller}",
                        'code'  => $action,
                    ])->create())
                    {
                        $result['success']['a']++;
                    }else{
                        $result['failed']['a']++;
                        $error = $actionModel->getError();
                        $error and $result['error'] .= "{$module}@{$controller}/{$action} : {$error} \n";
                    }
                }
            }
        }
        Response::ajaxBack($result);
    }

    /**
     * 显示菜单分组
     * @throws \System\Core\KbylinException
     */
    public function menugroup(){
//        $indexModel = new SystemModel();
//        $menus = $indexModel->listMenus();
//        if(false === $menus){
//            Response::ajaxBack(['type'=>'error']);
//        }
//        $this->assign('menus',TemplateTool::translate($menus));
        $this->display();
    }

    public function updateMenuGroup(array $list){
        $list or Response::failed('You gave the empty message!');
        $indexModel = new SystemModel();
        foreach($list as $item){
            $result = $indexModel->updateMenuItem($item);
        }
        Response::success('修改成功！');
    }

}