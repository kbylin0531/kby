<?php
/**
 * Created by PhpStorm.
 * User: kbylin
 * Date: 20/05/16
 * Time: 10:11
 */

namespace Application\Admin\System\Controller;
use Application\Admin\Library\AdminController;
use Application\Admin\System\Model\ActionModel;
use Application\Admin\System\Model\ActionGroupModel;
use Application\Admin\System\Model\ModuleModel;
use System\Core\KbylinException;
use System\Core\Storage;
use System\Utils\Response;

/**
 * Class Scanner
 * fucntion:scan the aplication and create data in database
 * @package Application\Admin\System\Controller
 */
class Scanner extends AdminController{

    public function index(){
        $this->displayManagement();
    }

    /**
     * 扫描目录
     * @var string
     */
    private $scandir = null;

    /**
     * scan the application dir and fetch actions
     * @throws KbylinException
     */
    public function scan(){
        $result = $this->scanCwebs();
        $result2 = $this->scanKbylin();
        Response::ajaxBack([
            $result,$result2
        ]);
    }

    private function scanCwebs($scandir=null){
        $scandir or $scandir = BASE_PATH.'App/Lib/Action';
        $this->scandir = rtrim($scandir,'/').'/';
        if(!is_dir($this->scandir)){
            throw new KbylinException('Invalid path for Action Scanner!');
        }

        $results = [];
        include_once BASE_PATH.'ThinkPHP/Lib/Core/Action.class.php';
        include_once $scandir.'/CommonAction.class.php';
        include_once $scandir.'/RightAction.class.php';

        $files = Storage::read($this->scandir,false);
        foreach ($files as $modulename=>$mpdulepath){
            if(is_dir($mpdulepath)){
                $modulename = trim($modulename,'/');
                $results[$modulename] = [];

                $controllers = Storage::read($mpdulepath);
                foreach ($controllers as $conreollername=>$controllerpath){
                    if(is_file($controllerpath)) {
                        $conreollername = trim(strstr($conreollername,'.class.php',true),'/');
                        $results[$modulename][$conreollername] = [];


                        //replace name and output to temp dir
                        $content = Storage::read($controllerpath);
                        false === $content and KbylinException::throwing("{$controllerpath} not found!");
                        $tempclassname = "{$modulename}_{$conreollername}";
                        $tempfile = RUNTIME_PATH."scan_tmp/{$tempclassname}.php";
                        $newcontent = str_replace($conreollername,$tempclassname,$content);
                        if(!Storage::write($tempfile,$newcontent)) throw new KbylinException('Write failed!');

                        include_once $tempfile;
                        class_exists($tempclassname) or
                        KbylinException::throwing('Invalid module and controller!',$modulename,$conreollername,$tempfile);
                        $results[$modulename][$conreollername] = $this->fetchPublicMethods($tempclassname);
                    }
                }
            }
        }
        //dumpout($results);
        return $results;
    }

    private function fetchPublicMethods($classname){
        $array = [];
        $classname = rtrim( str_replace('/','\\',$classname ),'\\');
        class_exists($classname) or KbylinException::throwing("'$classname' not found!");
        $instance = new \ReflectionClass($classname);
        $methods = $instance->getMethods(\ReflectionMethod::IS_PUBLIC);
        foreach ($methods as $method){
            $name = $method->getName();
            if(0 === strpos($name,'_')) continue;//以单下划线或者双下划线开头直接忽略
            $array[] = $name;
        }
        return $array;
    }

    private static $_skipdir = [
        'Controller','Model','View','Library','Assets',
    ];
    private function scanKbylin($scandir=null){
        $scandir or $scandir = BASE_PATH.'Application';

        return $this->scanKbylinModule($scandir);
    }
    private function scanKbylinModule($scandir,$parentModuleName=''){
        $modules = [];
        $result = Storage::read($scandir);
        foreach ($result as $modulename=>$modulepath){
            $modulename = trim($modulename,'/\\ ');
            if(in_array($modulename, self::$_skipdir)) continue;
            $modulename = $parentModuleName.$modulename;
            if(is_dir($modulepath)){
                $modules[$modulename] = [
                    'controller'    => [],
                    'children'      => $this->scanKbylinModule($modulepath,$modulename.'/'),
                ];
            }
            $ctlerpath = rtrim($modulepath,'/\\').'/Controller';
            if(is_dir($ctlerpath)){
                $cresult = Storage::read($ctlerpath);
                foreach ($cresult as $ctlername=>$ctlerpath){
                    if(is_file($ctlerpath)){
                        $ctlername = trim(strstr($ctlername,'.class.php',true),'/');
                        $modules[$modulename]['controller'][$ctlername] = [];
                        $fullname = "Application/{$modulename}/Controller/{$ctlername}";
                        $modules[$modulename]['controller'][$ctlername] = $this->fetchPublicMethods($fullname);
                    }
                }
            }

        }
        return $modules;
    }

    public function writein($modules){
        $actions = explode('*',$modules);

        $result = [
            'm' => [
                's' => 0,
                'f' => 0,
            ],
            'c' => [
                's' => 0,
                'f' => 0,
            ],
            'a' => [
                's' => 0,
                'f' => 0,
            ],
        ];
        $_modules = [];
        $_controllers = [];

        $moduleModel = new ModuleModel();
        $moduleModel->clean();
        $controllerModel = new ActionGroupModel();
        $controllerModel->clean();
        $actionModel = new ActionModel();
        $actionModel->clean();
        
        
        foreach ($actions as $action){
            $action = explode('@',$action );
            $module = $action[0];
            $controller = $action[1];
            $action = $action[2];

//            dumpout($module,$controller,$action);
            if(!isset($_modules[$module])){
                $_modules[$module] = true;
                $rst = $moduleModel->createModule($module);
                if(false === $rst) KbylinException::throwing('Module insert failed!'.$moduleModel->error());
                $rst?$result['m']['s']++:$result['m']['f']++;
            }

            $mckey = $module.'@'.$controller;
            if(!isset($_controllers[$mckey])){
                $_controllers[$mckey] = true;
                $rst = $controllerModel->createActionGroup($controller, $module);
                if(false === $rst) KbylinException::throwing('ActionGroup insert failed!'.$moduleModel->error());
                $rst?$result['c']['s']++:$result['c']['f']++;
            }
            $rst = $actionModel->createAction($action,$mckey);
            if(false === $rst) KbylinException::throwing('Action insert failed!'.$moduleModel->error());
            $rst?$result['a']['s']++:$result['a']['f']++;
        }
        Response::ajaxBack($result);
    }

}