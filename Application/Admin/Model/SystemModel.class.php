<?php
/**
 * Created by PhpStorm.
 * User: Zhonghuang
 * Date: 2016/4/14
 * Time: 14:21
 */
namespace Application\Admin\Model;
use System\Library\Model;

class SystemModel extends Model{

    /**
     * @return array
     */
    public function listMenus(){
        $kdao = $this->getDao();
        $list = $kdao->query('select * from kbylin_system_menu_group;');
        if(false === $list) return false;
        return $this->sortMenuGroup($list);
    }

    public function updateMenuItem(array $item){
        $id = $item['id'];
        unset($item['id']);
        return $this->getDao()->update('',[
            'pid'   => $item['parentId'],
        ],['id'=>$id]);

    }

    private function sortMenuGroup(array $groups){
        $map = [];//映射地图
        $sort = [];//整理后的数组
        $maxLevel = 10; // 最大嵌套10级
        $maxCount = []; // 嵌套计数器

        //制作映射地图
        foreach($groups as $key=>$item){
            if($item['pid'] == '0'){
                $id = $item['id'];
                $map[$id] = $id;//设置地图
                //顶层特殊对待
                $sort[$id] = [
                    'item'      => $item,
                    'children'  => [],
                ];
                unset($groups[$key]);
            }
        }
//dumpout($map,$sort);
        while(count($groups)){
            foreach($groups as $key=>$item){
                $pid = $item['pid'];
                $id = $item['id'];
                if(isset($map[$pid])){//如果父ID在地图上标识
                    $map[$id] = "{$map[$pid]},{$id}";//地图标识 OK

                    $paths = explode(',',$map[$pid]);
//                    dump($map[$pid],$paths);
                    $value = &$sort;
                    foreach($paths as $i) $value = &$value[$i]['children'];
                    $value[$id] = [
                        'item'      => $item,
                        'children'  => [],
                    ];
                    unset($groups[$key]);
                }else{
                    //找不到父ID，等待下一次循环
                    isset($maxCount[$id]) or $maxCount[$id] = 0;
                    if($maxCount[$id] ++ > $maxLevel){
                        unset($groups[$key]);//避免陷入死循环，设置最大嵌套数
                    }
                }
            }
        }
        return $sort;
    }

}