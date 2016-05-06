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
//        $this->testTouch();
//        $this->testUnlink();
//        $this->testWrite();
        $this->testAppend();



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
    public function testUnlink(){
        dumpout(Storage::unlink('D:/abs.txt')
        ,Storage::unlink('D:/中文2')
        ,Storage::unlink('D:/中文2',true)
        );
    }




    public function testWrite(){
        dumpout(
            //使用notepad++打开看到右下角编码还是ANSI的情况通常是中文字变化幅度不大的缘故,使用一大段中文代替"中文"两个字更能达到检测的目的
            Storage::write('D:/test1.txt','yes 这里由一大肚的合适觉得会觉得就会撒计划中文','UTF-8')
            ,Storage::write('D:/test2.txt','yes 这里由一大肚的合适觉得会觉得就会撒计划中文','GBK')
            ,Storage::write('D:/test3.txt','yes 这里由一大肚的合适觉得会觉得就会撒计划中文','GB2312')

            ,Storage::write(RUNTIME_PATH.'中文目录/test.txt','yes 这里是大家阿斯顿健身卡几点回家啊放假开始的附近号手机打开啊尽快 ','UTF-8') //BASE_PATH RUNTIME_PATH
            ,Storage::write(RUNTIME_PATH.'中文目录/test2.txt','yes 这里是大家阿斯顿健身卡几点回家啊放假开始的附近号手机打开啊尽快','GBK') //BASE_PATH RUNTIME_PATH
        );
    }


    public function testAppend(){
        dumpout(
            Storage::append('D:/test1.txt','yes 这里由一大肚的合适觉得会觉得就会撒计划中文','UTF-8')//写入已知文件
            ,Storage::append('D:/test4.txt','yes 这里由一大肚的合适觉得会觉得就会撒计划中文','UTF-8')//写入不存在的文件


            //出现了GB2312中没有的中文,被当成空格写入??  否,操作系统无法显示该中文的缘故
            ,Storage::append('D:/这个人/噷.txt','yes 这里由一大肚的合适觉得会觉得就会撒计划中文','UTF-8')//写入中文路径已知文件
            ,Storage::append('D:/这个人/噷2.txt','yes 这里由一大肚的合适觉得会觉得就会撒计划中文','UTF-8')//写入中文路径不存在的文件

            ,Storage::read('D:/这个人/噷.txt')

            ,Storage::append('D:/这个人/很帅.txt','yes 这里由一大肚的合适觉得会觉得就会撒计划中文','UTF-8')//写入中文路径已知文件
            ,Storage::append('D:/这个人/很帅2.txt','yes 这里由一大肚的合适觉得会觉得就会撒计划中文','UTF-8')//写入中文路径不存在的文件

        );
    }


}