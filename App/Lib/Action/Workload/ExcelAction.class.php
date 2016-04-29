<?php
//todo:工作量导出
class ExcelAction extends RightAction{
    private $objPHPExcel;
    private $model;
    private $style;
    public function __construct(){
        parent::__construct();
        $this->model=new SqlsrvModel();
        //生成工作簿
        vendor("PHPExcel.PHPExcel");
        $this->objPHPExcel = new PHPExcel();
        $this->objPHPExcel->getProperties()->setCreator("Maarten Balliauw")
            ->setLastModifiedBy("Maarten Balliauw")
            ->setTitle("Office 2007 XLSX Test Document")
            ->setSubject("Office 2007 XLSX Test Document")
            ->setDescription("Test document for Office 2007 XLSX, generated using PHP classes.")
            ->setKeywords("office 2007 openxml php")
            ->setCategory("Test result file");
        //设置默认字体和大小
        $this->objPHPExcel->getDefaultStyle()->getFont()->setName("宋体");
        $this->objPHPExcel->getDefaultStyle()->getFont()->setSize(10);
        //设置默认宽度
        $this->objPHPExcel->getActiveSheet()->getDefaultColumnDimension()->setWidth(10);
        //设置单元格自动换行
        $this->objPHPExcel->getDefaultStyle()->getAlignment()->setWrapText(true);
        //设置默认内容垂直居左
        $this->objPHPExcel->getDefaultStyle()->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
        $this->objPHPExcel->getDefaultStyle()->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
        //设置单元格加粗，居中样式
        $this->style=array('font' => array('bold' => true,'color'=>array('argb' => '00000000')),'alignment' => array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER));
    }
    public function download($title){
        //开始下载
        $filename = $title.".xls";
        $ua = $_SERVER["HTTP_USER_AGENT"];

        header('Content-Type:application/vnd.ms-excel');
        if(preg_match("/MSIE/", $ua)){
            header('Content-Disposition:attachment;filename="'.urlencode($filename).'"');
        } else if (preg_match("/Firefox/", $ua)) {
            header('Content-Disposition:attachment;filename*="utf8\'\''.$filename.'"');
        } else {
            header('Content-Disposition:attachment;filename="'.$filename.'"');
        }
        header('Cache-Control: max-age=0');
        header('Cache-Control: max-age=1');

        header ('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        header ('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT');
        header ('Cache-Control: cache, must-revalidate');
        header ('Pragma: public');

        $objWriter = PHPExcel_IOFactory::createWriter($this->objPHPExcel, 'Excel5');
        $objWriter->save('php://output');
    }
    /**
     * 教师工作量分配表-分配
     */
    public function expTeacherAlloc(){
        //重命名工作表名称
        $title="{$_POST["YEAR"]}学年{$_POST["TERM"]}学期教师工作量分配表";
        $this->objPHPExcel->getActiveSheet()->setTitle($title);
        //设置宽度
        $this->objPHPExcel->getActiveSheet()->getColumnDimension("A")->setWidth(12);
        $this->objPHPExcel->getActiveSheet()->getColumnDimension("B")->setWidth(30);
        $this->objPHPExcel->getActiveSheet()->getColumnDimension("G")->setWidth(20);
        $this->objPHPExcel->getActiveSheet()->getColumnDimension("K")->setWidth(15);
        //设置个别列内容居中
        $this->objPHPExcel->getActiveSheet()->getStyle("A")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $this->objPHPExcel->getActiveSheet()->getStyle("C:K")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $this->objPHPExcel->getActiveSheet()->getStyle("M")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        //标题设置
        $this->objPHPExcel->getActiveSheet()->getStyle('A1')->applyFromArray($this->style);//设置样式
        $this->objPHPExcel->getActiveSheet()->mergeCells('A1:M1');//合并A1单元格到M1
        $this->objPHPExcel->setActiveSheetIndex()->setCellValue('A1',$title);//写入A1单元格内容
        $this->objPHPExcel->getActiveSheet()->getStyle('A1')->getFont()->setSize(14);//设置字体大小
        $this->objPHPExcel->getActiveSheet()->getRowDimension('1')->setRowHeight(26);//设置行高
        //列名设置
        $this->objPHPExcel->getActiveSheet()->getStyle("A2:M2")->applyFromArray($this->style);//字体样式
        $this->objPHPExcel->getActiveSheet()->getRowDimension('2')->setRowHeight(22);//设置行高
        //单元格内容写入
        $this->objPHPExcel->setActiveSheetIndex()->setCellValue('A2',"课号");
        $this->objPHPExcel->setActiveSheetIndex()->setCellValue('B2',"课名");
        $this->objPHPExcel->setActiveSheetIndex()->setCellValue('C2',"总课时");
        $this->objPHPExcel->setActiveSheetIndex()->setCellValue('D2',"周数");
        $this->objPHPExcel->setActiveSheetIndex()->setCellValue('E2',"人数");
        $this->objPHPExcel->setActiveSheetIndex()->setCellValue('F2',"标准班");
        $this->objPHPExcel->setActiveSheetIndex()->setCellValue('G2',"工作量类型");
        $this->objPHPExcel->setActiveSheetIndex()->setCellValue('H2',"班型系数");
        $this->objPHPExcel->setActiveSheetIndex()->setCellValue('I2',"校正系数");
        $this->objPHPExcel->setActiveSheetIndex()->setCellValue('J2',"工作量");
        $this->objPHPExcel->setActiveSheetIndex()->setCellValue('K2',"已分配工作量");
        $this->objPHPExcel->setActiveSheetIndex()->setCellValue('L2',"教师姓名");
        $this->objPHPExcel->setActiveSheetIndex()->setCellValue('M2',"教师号");
        $row=2;//设置当前行号
        //查询数据
        $bind = $this->model->getBind("YEAR,TERM,COURSENO,COURSENAME,SCHOOL,TYPE",$_REQUEST,"%");
        $sql = $this->model->getSqlMap("Workload/Excel/Q_teacherAllocList.sql");
        $data = $this->model->sqlQuery($sql,$bind);
        if(count($data) > 0){
            foreach($data as $val){
                $row++;
                $this->objPHPExcel->getActiveSheet()->setCellValue("A$row", $val['COURSENO']);
                $this->objPHPExcel->getActiveSheet()->setCellValue("B$row", $val['COURSENAME']);
                $this->objPHPExcel->getActiveSheet()->setCellValue("C$row", $val['TOTAL']);
                $this->objPHPExcel->getActiveSheet()->setCellValue("D$row", $val['WEEKS']);
                $this->objPHPExcel->getActiveSheet()->setCellValue("E$row", $val['ATTENDENTS']);
                $this->objPHPExcel->getActiveSheet()->setCellValue("F$row", $val['STANDARD']);
                $this->objPHPExcel->getActiveSheet()->setCellValue("G$row", $val['WORKTYPENAME']);
                $this->objPHPExcel->getActiveSheet()->setCellValue("H$row", $val['CLASSCOEFF']);
                $this->objPHPExcel->getActiveSheet()->setCellValue("I$row", $val['CORRECTCOEFF']);
                $this->objPHPExcel->getActiveSheet()->setCellValue("J$row", $val['WORKLOAD']);
                $this->objPHPExcel->getActiveSheet()->setCellValue("K$row", $val['ALLOCWORKLOAD']);
                $this->objPHPExcel->getActiveSheet()->setCellValue("L$row", $val['NAME']);
                $this->objPHPExcel->getActiveSheet()->setCellValueExplicit("M$row", $val['TEACHERNO'],PHPExcel_Cell_DataType::TYPE_STRING);
            }
        }
        //边框设置
        $this->objPHPExcel->getActiveSheet(0)->getStyle("A2:M$row")->getBorders()->getAllBorders()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
        $this->download($title);
    }
    /**
     * 教师工作量归档表-归档
     */
    public function expTeacherWork(){
        //重命名工作表名称
        $title="{$_POST["YEAR"]}学年{$_POST["TERM"]}学期教师工作量归档";
        $this->objPHPExcel->getActiveSheet()->setTitle($title);
        //设置宽度
        $this->objPHPExcel->getActiveSheet()->getColumnDimension("A")->setWidth(12);
        $this->objPHPExcel->getActiveSheet()->getColumnDimension("B")->setWidth(30);
        $this->objPHPExcel->getActiveSheet()->getColumnDimension("C")->setWidth(20);
        $this->objPHPExcel->getActiveSheet()->getColumnDimension("H")->setWidth(20);
        $this->objPHPExcel->getActiveSheet()->getColumnDimension("I")->setWidth(20);
        //设置个别列内容居中
        $this->objPHPExcel->getActiveSheet()->getStyle("A")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $this->objPHPExcel->getActiveSheet()->getStyle("C:G")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $this->objPHPExcel->getActiveSheet()->getStyle("I")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        //标题设置
        $this->objPHPExcel->getActiveSheet()->getStyle('A1')->applyFromArray($this->style);//设置样式
        $this->objPHPExcel->getActiveSheet()->mergeCells('A1:I1');//合并A1单元格到I1
        $this->objPHPExcel->setActiveSheetIndex()->setCellValue('A1',$title);//写入A1单元格内容
        $this->objPHPExcel->getActiveSheet()->getStyle('A1')->getFont()->setSize(14);//设置字体大小
        $this->objPHPExcel->getActiveSheet()->getRowDimension('1')->setRowHeight(26);//设置行高
        //列名设置
        $this->objPHPExcel->getActiveSheet()->getStyle("A2:I2")->applyFromArray($this->style);//字体样式
        $this->objPHPExcel->getActiveSheet()->getRowDimension('2')->setRowHeight(22);//设置行高
        //单元格内容写入
        $this->objPHPExcel->setActiveSheetIndex()->setCellValue('A2',"课号");
        $this->objPHPExcel->setActiveSheetIndex()->setCellValue('B2',"课名");
        $this->objPHPExcel->setActiveSheetIndex()->setCellValue('C2',"开课学院");
        $this->objPHPExcel->setActiveSheetIndex()->setCellValue('D2',"教师姓名");
        $this->objPHPExcel->setActiveSheetIndex()->setCellValue('E2',"教师号");
        $this->objPHPExcel->setActiveSheetIndex()->setCellValue('F2',"工作量");
        $this->objPHPExcel->setActiveSheetIndex()->setCellValue('G2',"标准班");
        $this->objPHPExcel->setActiveSheetIndex()->setCellValue('H2',"工作量类型");
        $this->objPHPExcel->setActiveSheetIndex()->setCellValue('I2',"教师所在学院");
        $row=2;//设置当前行号
        //查询数据
        $bind = $this->model->getBind("YEAR,TERM,COURSENAME,COURSENO,SCHOOL,NAME,TEACHERNO",$_REQUEST,"%");
        $sql = $this->model->getSqlMap("Workload/Archived/Q_workloadList.sql");
        $data = $this->model->sqlQuery($sql,$bind);
        if(count($data) > 0){
            foreach($data as $val){
                $row++;
                $this->objPHPExcel->getActiveSheet()->setCellValue("A$row", $val['COURSENO']);
                $this->objPHPExcel->getActiveSheet()->setCellValue("B$row", $val['COURSENAME']);
                $this->objPHPExcel->getActiveSheet()->setCellValue("C$row", $val['SCHOOLNAME']);
                $this->objPHPExcel->getActiveSheet()->setCellValue("D$row", $val['NAME']);
                $this->objPHPExcel->getActiveSheet()->setCellValueExplicit("E$row", $val['TEACHERNO'],PHPExcel_Cell_DataType::TYPE_STRING);
                $this->objPHPExcel->getActiveSheet()->setCellValue("F$row", $val['WORKLOAD']);
                $this->objPHPExcel->getActiveSheet()->setCellValue("G$row", $val['STANDARD']);
                $this->objPHPExcel->getActiveSheet()->setCellValue("H$row", $val['WORKTYPENAME']);
                $this->objPHPExcel->getActiveSheet()->setCellValue("I$row", $val['TEACHERSCHOOL']);
            }
        }
        //边框设置
        $this->objPHPExcel->getActiveSheet(0)->getStyle("A2:I$row")->getBorders()->getAllBorders()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
        $this->download($title);
    }
}