<?php
/**
 * 教学质量评估
 * User: cwebs
 * Date: 14-2-10
 * Time: 下午4:17
 */
class EntryAction extends RightAction {
	private $model;
	/**
	 * 首页
	 */
	public function __construct() {
		parent::__construct ();
		$this->model = new QualityModel();
	}
	/**
	 * 条目整理 - 创建考评总表
	 */
	public function entry() {
		if ($this->_hasJson) {
			if (trim ( $_POST ['FLAG'] ) == "1") {//创建
                $rst = $this->model->createEvaluationTableByYearTerm($_POST['YEAR'],$_POST['TERM']);
                if(is_string($rst)){
                    $this->exitWithReport('创建考评总表出现错误！'.$rst);
                }else{
                    $this->exitWithReport("成功创建考评总表，共执行[{$rst}]条数据",'info');
                }
			}elseif (trim ( $_POST ['FLAG'] ) == "2") {//删除
                $rst = $this->model->clearEvaluationTable(array(
                        'YEAR' => $_POST['YEAR'],
                        'TERM' => $_POST['TERM'],
                    ));
                if(is_string($rst)){
                    $this->exitWithReport('清除考评总表的过程中出现错误！'.$rst);
                }else{
                    $this->exitWithReport("成功清除考评总表，共删除[{$rst}]条数据",'info');
                }
			}
		}
        $this->assign("yearTerm",$this->model->getYearTerm('C'));
        $this->display();
	}
}