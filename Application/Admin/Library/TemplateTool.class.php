<?php
/**
 * Created by PhpStorm.
 * User: Zhonghuang
 * Date: 2016/4/15
 * Time: 14:46
 */
namespace Application\Admin\Utils;

class TemplateTool {

    /**
     * 将嵌套的菜单配置数组转换成HTML
     * @param array $menus
     * @param bool $sortableListsOpen 是否默认开放
     * @return string
     */
    public static function translate($menus,$sortableListsOpen=true){
        static $string = '';
        foreach($menus as $item){
            self::_translate($item,$string,$sortableListsOpen);
        }
//        exit($string);
        return $string;
    }
    private static function _translate($item,&$str,$sortableListsOpen=true){
        $open = $sortableListsOpen?"class='sortableListsOpen'":'';
        $str .= "<li id='{$item['item']['id']}' {$open}><div><span class='clickable'>{$item['item']['title']}</span></div>";
        if(isset($item['children']) and $item['children']){//有孩子的情况下当作循环嵌套
            $str .= '<ul>';
            foreach($item['children'] as $subitem){
                self::_translate($subitem,$str);
            }
            $str .= '</ul>';
        }
        $str .= '</li>';
    }


}