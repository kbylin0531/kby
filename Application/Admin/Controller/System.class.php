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