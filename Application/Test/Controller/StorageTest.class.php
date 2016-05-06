<?php
/**
 * Created by PhpStorm.
 * User: Zhonghuang
 * Date: 2016/4/13
 * Time: 20:38
 */
namespace Application\Test\Controller;

use System\Core\Storage;

class StorageTest {

    public function index(){
//        $this->testRead();
//        $this->testHas();
//        $this->testWrite();
//        $this->testMtime();
//        $this->testSize();

//        $this->testMakeDir();
        $this->testTouch();

    }


    public function testRead(){
        dumpout(
            Storage::read(BASE_PATH.'README.md')
            ,Storage::read('D:\\README.txt','GB2312')//读取以GB2312格式的文本文件
            ,Storage::read('D:\\README.txt').'YES IT IS RIGHT'//读取以GB2312格式的文本文件
            ,Storage::read('D:\\README2.txt')//读取默认编码格式(UTF-8)的文本文件
            ,Storage::read('D:\\中文\\README.txt','GB2312')//读取以GB2312格式的文本文件
            ,Storage::read('D:\\中文\\README2.txt')//读取默认编码格式(UTF-8)的文本文件

        //读取目录
            ,Storage::read('D:\\中文',null,true)//读取默认编码格式(UTF-8)的文本文件
        );
    }
    public function testHas(){
        dumpout(Storage::has(RUNTIME_PATH.'才\\才'),Storage::has(RUNTIME_PATH.'才\\才2'));//测试存在和不存在的文件
    }
    public function testMtime(){
        dumpout(Storage::mtime(RUNTIME_PATH.'才\\才',123456),Storage::mtime(RUNTIME_PATH.'才\\才'));
    }
    public function testSize(){
        dumpout(Storage::size('D:/中文/中文文件.txt'),Storage::size('D:/中文/中文文件222.txt'));
    }


    public function testMakeDir(){
        dumpout(Storage::mkdir('D:/中文/中文/中文/中文23'));
    }


    public function testTouch(){
        dumpout(Storage::touch('D:\\中文2\\才1',140000000,140000000),Storage::touch('D:\\中文2\\才2',140000000,140000000));//测试存在和不存在的文件
    }




    public function testWrite(){
        dumpout(
//            Storage::write(BASE_PATH.'test.txt','yes 中文','UTF-8')
            Storage::write(RUNTIME_PATH.'/才/才/c/test.txt','yes 中文','UTF-8') //BASE_PATH RUNTIME_PATH
//            ,Storage::write(RUNTIME_PATH.'test2.txt','这是一段中文','GB2312') //BASE_PATH RUNTIME_PATH

        );
    }



}