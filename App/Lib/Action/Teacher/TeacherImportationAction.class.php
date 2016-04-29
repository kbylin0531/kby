<?php
/**
 * Created by PhpStorm.
 * User: linzh_000
 * Date: 2016/3/11
 * Time: 10:44
 */

/**
 * Class TeacherImportationAction 教师导入
 */
class TeacherImportationAction extends RightAction {



    public function index(){

        if(null !== REQTAG){
            $teacherModel = new TeacherModel();
            if(REQTAG === 'import'){
                //补考成绩导入
                $excelModel = new ExcelExtensionModel();

                //按照不同的学校设置不同的导入格式
                switch(SCHOOL_CODE){
                    case 'yzzj':
                        $format = array(
                            'num'  => '序号',
                            'school'  => '部 门',
                            'teachername'  => '姓 名',
                            'birthday'  => '出生 日期',
                            'birthplace'  => '籍贯',
                            'sex'  => '性别',
                            'armyjoindate'  => '入伍 时间',
                            'enterdate'  => '进本单位',
                            'capacity'  => '身份',
                            'position'  => '现任职务',
                            'positiondate'  => '任职时间',
                            'positiontechdate'  => '技术职务审定时间',
                            'party'  => '政治面貌',
                            'partydate'  => '入党年月',
                            'graduation'  => '何时何校何专业毕业',
                            'employment'  => '用工形式',
                            'rem'   => '备注'
                        );
                        break;
                    default:
                        die('本校的教师导入功能需求未被提及，该功能暂时无法使用！');
                }
                $data = $excelModel->import($format,null,2);

//dump($data);exit();
                if(is_string($data)) $this->failedWithReport("查询失败！{$data}");
                $info = '<pre><b>导入失败的教师姓名如下：</b><br />';
                foreach($data as $value){//补考记录一定后于总评成绩存在
                    switch(SCHOOL_CODE){
                        case 'yzzj':
                            $params = array();
                            $params['teachername'] = $value['teachername']; unset($value['teachername']);
                            $params['school'] = $value['school']; unset($value['school']);
                            $params['birthday'] = $value['birthday']; unset($value['birthday']);
                            $params['sex'] = $value['sex']=='男'?'M':'F'; unset($value['sex']);
                            $params['position'] = $value['position']; unset($value['position']);
                            $params['party'] = $value['party']; unset($value['party']);
                            $params['enterdate'] = $value['enterdate']; unset($value['enterdate']);
                            $params['metas'] = serialize($value);
                            break;
                        default:
                            die('本校的教师导入功能需求未被提及，该功能暂时无法使用！');
                    }
                    //$teachername,$school,$birthday,$sex,$position,$party,$enterdate,$metas

//                    dump($params);

                    $rst = call_user_func_array(array($teacherModel,'createTeachersAutomatic'),$params);
                    if(is_string($rst)){
                        $info .= "  {$params['teachername']} 发生了错误！$rst<br />";
                    }elseif(!$rst){
                        $info .= "  {$params['teachername']} 修改失败,请检查是否存在填写错误！<br />";
                    }
                }
                exit("{$info}</pre>");
            }
        }


        $this->display();
    }


}