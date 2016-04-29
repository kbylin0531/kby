Core - 彼此之间不存在相互依赖关系的组件成为核心组件


isset(是个很有用的语言就够，它可以测试括号中的变量是否存在，不存在的情况下也不会报错)

<a>元素的target属性
_blank
_parent
_self
_top
framename





引导至本项目
//分解请求参数
if(isset($_REQUEST['_PARAMS_'])){
    $temp = array();
    parse_str($_REQUEST['_PARAMS_'],$temp);
    $_POST = array_merge($_POST,$temp);
    $_REQUEST = array_merge($_REQUEST,$temp);
    $_GET = array_merge($_GET,$temp);
    unset($_REQUEST['_PARAMS_']);
}
//设计了标记跳转
if(isset($_REQUEST['gokbylin']) and $_REQUEST['gokbylin']){
    include '../Kbylin/index.php';
    exit();
}