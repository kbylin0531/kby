<?php
/**
 * Created by Linzh.
 * Email: linzhv@qq.com
 * Date: 2016/1/26
 * Time: 10:53
 */

/**
 * Class CalculationAction 学分计算
 *
 * 学分计算各个学校的计算过程可能不同
 *
 */
class CalculationAction extends RightAction {

    /**
     * 计算学分
     * @param null $year
     * @param null $term
     * @param null $classno
     * @param null $studentno
     * @param null $coursegroup
     */
    public function calculate($year=null,$term=null,$classno=null,$studentno=null,$coursegroup=null){

        if(REQTAG){
            switch(REQTAG){
                case 'refresh' :
                    $this->calculateScoreCredit($year,$term,$classno,$studentno,$coursegroup);
                    break;
                case 'list':
                    $this->listScoreCreditForCalculate($year,$term,$classno,$studentno,$coursegroup);
                    break;
                case 'refreshAll':
                    $resultModel = new ResultModel();
                    $rst = $resultModel->calculateAllScoreCredit($year,$term);
                    if(is_string($rst)){
                        $this->failedWithReport('失败！'.$rst);
                    }
                    $this->successWithReport('成功，一共计算'.$rst.'个学生的数据 ！');
                    break;
            }
            exit();
        }

        $this->display();
    }

    /**
     * 罗列用于成绩学分计算的数据
     * @param $year
     * @param $term
     * @param $classno
     * @param $studentno
     * @param $coursegroup
     */
    public function listScoreCreditForCalculate($year,$term,$classno,$studentno,$coursegroup){
        $resultModel = new ResultModel();
        $list = $resultModel->listScoreCreditForCalculate($year,$term,$classno,$studentno,$coursegroup,$this->_pageDataIndex,$this->_pageSize);
        if(is_string($list)){
            $this->failedWithReport("查询失败！{$list}");
        }
        $this->ajaxReturn($list);
    }

    /**
     * 计算学生学分
     * @param $year
     * @param $term
     * @param $classno
     * @param $studentno
     * @param $coursegroup
     * @param bool $return 是否直接返回
     * @return int|void
     */
    public function calculateScoreCredit($year,$term,$classno,$studentno,$coursegroup,$return =true){
        $resultModel = new ResultModel();
        $list = $resultModel->listScoreCreditForCalculate($year,$term,$classno,$studentno,$coursegroup);
        if(is_string($list)){
            $this->failedWithReport("获取列表失败！{$list}");
        }elseif(empty($list['total'])){
            $this->failedWithReport("获取的列表为空！");
        }

        //从开课计划中获取课程信息
        $ctlist = $resultModel->getCourseInfoByYearTerm($year,$term);
        $mapping = array();
        foreach($ctlist as $course) {
            $mapping[strtoupper($course['courseno'].$course['group'])] = array(
                'scoretype'  => $course['scoretype'],
                'coursetype'  => $course['coursetype'],
                'approach'  => $course['approach'],
                'credit'    => $course['credit'],
            );
        }

        $c = 0;//修改成功数目

        foreach($list['rows'] as $student){
            $courseinfo = $mapping[$student['coursegroup']];

            try{
                $credit = $this->_caculateCredit($student['general_score'],$courseinfo['approach'],$courseinfo['scoretype'],$courseinfo['credit']);
            }catch(Exception $e){
                continue;
            }
            $rst = $resultModel->calculateScoreCredit($student['studentno'],$student['year'],$student['term'],$student['coursegroup'],$credit);
            if(is_string($rst)){
                $this->failedWithReport('更新失败！'.$rst);
            }
            ++$c;
        }
        if($return){
            $this->successWithReport('更新成功,一共'.$c.'条数据！');
        }
        return $c;
    }

    /**
     * 计算学分
     * @param $general
     * @param $courseapproach
     * @param $scoretype
     * @param $credit
     * @return int
     * @throws Exception
     */
    private function _caculateCredit($general,$courseapproach,$scoretype,$credit){
        switch($scoretype){
            case 'ten':
                $general = intval($general);
                return $this->_caculateByTen($general,$courseapproach,$credit);
                break;
            case 'five':
                return $this->_caculateByFive($general,$courseapproach,$credit);
                break;
            case 'two':
                //TODO:二级制
                return 0;
                break;
            default:
                throw new Exception("无法识别的成绩类型'{$scoretype}'!");
        }
    }

    /**
     * 计算五级制学分
     * @param $general
     * @param $courseapproach
     * @param $credit
     * @return int
     * @throws Exception
     */
    private function _caculateByFive($general,$courseapproach,$credit){
        switch(SCHOOL_CODE){
            case 'yzzj'://鄞州职教
                //超过60分即可得到全部学分
                return in_array($general,array('优秀','良好','中等','及格',))?$credit:0;
                break;
            case 'qzzz'://衢州中专
                //TODO:衢州中专五级级制
                return 0;
                break;
            default:
                throw new Exception('未识别的学院代号!');
        }
    }

    /**
     * 计算百分制学分
     * @param float $general 总评成绩
     * @param string $courseapproach 修课方式，衢州中专采取的
     * @param float $credit 课程本身对应的学分，对于衢州中专这个学分是无效的，课程最终获得的学分是一句课程成绩（百分制）和课程的修课方式（他们的说法是课程类别）
     * @return int|float 返回学分
     * @throws Exception
     */
    private function _caculateByTen($general,$courseapproach,$credit){
        $general = floatval($general);
        switch(SCHOOL_CODE){
            case 'yzzj':
                //超过60分即可得到全部学分
                return $general >= 60?$credit:0;
                break;
            case 'qzzz'://衢州中专
//                exit($courseapproach);
                switch($courseapproach){
                    case 'C'://核心课程
                        if($general >= 85) return 5;
                        elseif($general < 60) return 0;
                        else return 3;
                        break;
                    case 'M'://必修课程
                        if($general >= 85) return 3;
                        elseif($general < 60) return 0;
                        else return 2;
                        break;
                    case 'E'://其他
                        if($general >= 85) return 2;
                        elseif($general < 60) return 0;
                        else return 1;
                        break;
                    default:
                        throw new Exception("无法识别的课程类型'{$courseapproach}'!");
                }
                break;
            default:
                throw new Exception('未识别的学院代号!');
        }

    }


}