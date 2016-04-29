<?php
/**
 * Created by Lin.
 * User: Lin
 * Date: 2015/6/4
 * Time: 13:09
 *
 * 提供了url地址访问的数据
 */
class ProviderAction extends  Action{

    private  $model = null;

    public function __construct(){
//        parent::__construct();
        import('ORG.Util.Utils');
    }

    /**
     * @param bool $toall 是否带全部的选项 前端设置默认选中
     */
    public function getjsonschools($toall = false){
        $jsonschools = array();
        $rootId = "-1";
        if ($toall){
            $jsonschool["id"] = "%";
            $jsonschool["text"] = "全部";
            $jsonschool["parentId"] = "-1";
            array_push($jsonschools, $jsonschool);
        }
        $schools = M('schools')->select();
        foreach($schools as $school) {
            $jsonschool["id"] = trim($school["SCHOOL"]);
            $jsonschool["text"] = trim($school["NAME"]);
            $parentId = "-1";
            if (trim($school["PARENT"])) {
                $parentId = trim($school["PARENT"]);
            }
            $jsonschool["parentId"] = $parentId;
            array_push($jsonschools, $jsonschool);
        }

        $jsonschools = create_tree($jsonschools,$rootId);
        $this->ajaxReturn($jsonschools,"JSON");
    }

    public function getjsonteachers(){
        $json = array();
        $input = trim(urldecode($_REQUEST['input']));
        if(isset($input)){
            if(!isset($this->model)) $this->model = M("SqlsrvModel:");
            //最多选择10个
            $sql = 'SELECT DISTINCT TOP 20 NAME from TEACHERS WHERE NAME like :name ;';
            $bind = array(':name'=>"%$input%");
            $teachers = $this->model->sqlQuery($sql,$bind);
            foreach($teachers as $teacher){
                array_push($json,array('name'=>trim($teacher['NAME']),'val'=>trim($teacher['NAME']).'%'));
            }
        }
        $this->ajaxReturn($json,'JSON');
        exit;
    }

    /**
     * 有注入风险
     */
    public function metaProvider(){
        $json = array();
        $input = Utils::getSafeParam($_POST['input']);
        if(isset($input)){
            $field = Utils::getSafeParam($_POST['field']) ;//可以使逗号分隔的数组，不能有空格
            $limit = Utils::getSafeParam($_POST['limit']);
            $tablename = Utils::getSafeParam($_POST['tblnm']);
            if(Utils::allNotNull($field,$limit,$tablename)){
                if(!isset($this->model)) $this->model = new CommonModel();
                //最多选择10个
                $sql = "SELECT DISTINCT TOP $limit $field from $tablename WHERE $field like :name ;";
                $bind = array(':name'=>"%$input%");
                $teachers = $this->model->sqlQuery($sql,$bind);
                foreach($teachers as $teacher){
                    array_push($json,array('name'=>trim($teacher['NAME']),'val'=>trim($teacher['NAME']).'%'));
                }
            }
        }
        $this->ajaxReturn($json,'JSON');
        exit;
    }



    /**
     * INSERT INTO METHODS  VALUES ('CS51', '*', '查询年级学院班级学生', 'Common/Provider/seacher');
     * 测试地址：http://localhost:8085/Common/Provider/seacher/reqtag/school
     * @param null|int $year 学年
     * @param null|int $term 学期
     * @param string $grade 年级
     * @param string $schoolno 学院号
     * @param string $classno 班级号
     */
    public function seacher($year=null,$term=null,$grade='%',$schoolno='%',$classno='%', $tgroup='%',$type='%',$majorcode='%'){
        $model = new SearcherModel();
        $message = null;
        switch($_REQUEST['reqtag']){//$_REQUEST['reqtag']
            case 'grade':
                $message = $model->getGrades();
                break;
            case 'school':
                $message = $model->getSchools();
                break;
            case 'class':
                $message = $model->getClassesByGradeAndSchoolno($grade,$schoolno);
                break;
            case 'student':
                $message = $model->getStudentsByClassno($classno);
                break;
            case 'teacher': //班级任课教师
                $message = $model->getTeachersByClassno($year,$term,$classno);
                break;

            case 'major':
                $message = $model->getMajor();
                break;
            case 'majoritem':
                $message = $model->getMajorItem($majorcode);
                break;

            case 'g-teacher' :
                $message = $model->getTeachersByGroup($schoolno, $tgroup);
                break;
            case 'tgroup' :
                $message = $model->getTGroup();
                break;

            case 'courseapproach':
                $message = $model->getCourseApproach();
                break;
            case 'coursetype':
                $message = $model->getCourseType();
                break;
            case 'examtype':
                $message = $model->getCourseExamType();
                break;
            case 'scoretype':
                $message = $model->getScoreType();
                break;
            case 'competitionlevel':
                $message = $model->getCompetitionLevel($type);
                break;
            case 'competitionrank':
                $message = $model->getCompetitionRank($type);
                break;
        }

//        echo '<pre>';
//        vardump($message,REQTAG);
        $this->ajaxReturn($message,'JSON');
    }


    public function getClassList(){
        $prmTEACHERNO = "%";
        $prmNAME = "%";
        if ($_POST["q"])
        {
            $prmTEACHERNO = trim($_POST["q"])."%";
            $prmNAME = "%".trim($_POST["q"])."%";
        }
        $this->model = new CommonModel();
        $bind = array(":CLASSNO"=>$prmTEACHERNO,":CLASSNAME"=>$prmNAME);
        $teachers = $this->model->sqlQuery("
select top 300 rtrim(CLASSNO) as id, rtrim(CLASSNO) + ' / ' + rtrim(CLASSNAME) as text from CLASSES
where CLASSNO like :CLASSNO or CLASSNAME like :CLASSNAME
order by CLASSNO", $bind);
        $this->ajaxReturn($teachers,"JSON");

    }


}