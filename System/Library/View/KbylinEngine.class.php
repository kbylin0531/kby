<?php
/**
 * Created by PhpStorm.
 * User: kbylin
 * Date: 5/14/16
 * Time: 9:50 AM
 */

namespace System\Library\View;
use System\Core\KbylinException;
use System\Utils\SEK;

/**
 * Class KbylinEngine 自定义模板引擎
 * @package System\Library\View
 */
class KbylinEngine implements ViewEngineInterface{

    /**
     * 模板变量
     * @var array
     */
    protected $_tVars = [];

    public function setContext(array $context)
    {
        // TODO: Implement setContext() method.
    }

    public function registerPlugin($type, $name, $callback, $cacheable = true, $cache_attr = null){}

    public function assign($tpl_var, $value = null, $nocache = false){

    }

    public function display($template = null, $cache_id = null, $compile_id = null, $parent = null)
    {
        // TODO: Implement display() method.

        ob_start();
        ob_implicit_flush(0);

        // 渲染输出
//        $method = $renderContent ? 'display' : 'fetch';
//        $this->engine->$method($template, $vars, $config);

        // 获取并清空缓存
        $content = ob_get_clean();
        // 允许用户自定义模板的字符串替换
        echo $content;
    }

    private function stripReplace($str) {
        return str_replace(
            array('{','}','(',')','|','[',']','-','+','*','.','^','?'),
            array('\{','\}','\(','\)','\|','\[','\]','\-','\+','\*','\.','\^','\?'), $str);
    }


    // 模板变量获取和设置
    public function get($name) {
        return isset($this->_tVars[$name])?$this->_tVars[$name]:null;
    }
    public function set($name,$value) {
        $this->_tVars[$name]= $value;
    }

    protected $_block = [];

    protected function parseExtend($content){
        $find       =   preg_match('/\{extend\s(.+?)\s*?\/\}/is',$content,$matches);
        if(false === $find) KbylinException::throwing('An error occurred when parsing extend!','/\{extend\s(.+?)\s*?\/\}/is',$content,isset($matches)?$matches:'no match!');
        if($find){
            $content    =   str_replace($matches[0],'',$content);
            //因为继承,故这个模板中只有block中的内容是有效的,这里作全部记录
            preg_replace_callback('/\{block\s*name=[\'"](.+?)[\'"]\s*?\}(.*?)\{\/block\}/is', function ($matches){
                $this->_block[$matches[1]]  =   $matches[2];//记录block中的内容
            },$content);

            //获取extend 属性
            $attrs = SEK::parseXmlAttrs($matches[1]);
            $extendContent = $this->parseTemplateName($attrs['file']);//使用file属性找到对应的模板问津啊

        }
    }

    /**
     * 加载公共模板并缓存 和当前模板在同一路径，否则使用相对路径
     * @access private
     * @param string $tmplPublicName  公共模板文件名
     * @param array $vars  要传递的变量列表
     * @return string
     */
    private function parseIncludeItem($tmplPublicName,$vars=array(),$extend){
        // 分析模板文件名并读取内容
        $parseStr = $this->parseTemplateName($tmplPublicName);
        // 替换变量
        foreach ($vars as $key=>$val) {
            $parseStr = str_replace('['.$key.']',$val,$parseStr);
        }
        // 再次对包含文件进行模板分析
        return $this->parseInclude($parseStr,$extend);
    }

    /**
     * 分析加载的模板文件并读取内容 支持多个模板文件读取
     * @access private
     * @param string $templateName  模板文件名
     * @return string
     */
    private function parseTemplateName($templateName){
        $templateName = ltrim($templateName);
        $firstTag = substr($templateName,0,1);
        if( $firstTag === '$'){
            //支持加载变量文件名
            $templateName = $this->get(substr($templateName,1));
        }
        return file_get_contents($templateName);
    }

}