<?php
/**
 * Created by Linzh.
 * Email: linzhv@qq.com
 * Date: 2015/12/31
 * Time: 13:53
 */

class  ExcelExtensionModel{

    //单元格对齐模式
    const ALI_LEFT = 0;
    const ALI_CENTER = 1;
    const ALI_RIGHT = 2;
    //单元格值的类型
    const TYPE_STR = 1;
    const TYPE_NUM = 2;
    //默认单元格宽度
    const DEFALUT_CELL_WIDTH = 14;
    /**
     * 单元格键与名的映射
     * @var array
     */
    protected static $cellName = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z',
        'AA', 'AB', 'AC', 'AD', 'AE', 'AF', 'AG', 'AH', 'AI', 'AJ', 'AK', 'AL', 'AM', 'AN', 'AO', 'AP', 'AQ', 'AR', 'AS', 'AT', 'AU', 'AV', 'AW', 'AX', 'AY', 'AZ');
    protected static $cellName2 = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z',
        'AA', 'AB', 'AC', 'AD', 'AE', 'AF', 'AG', 'AH', 'AI', 'AJ', 'AK', 'AL', 'AM', 'AN', 'AO', 'AP', 'AQ', 'AR', 'AS', 'AT', 'AU', 'AV', 'AW', 'AX', 'AY', 'AZ');

    /**
     * @var PHPExcel
     */
    private $phpExcel = null;
    /**
     * @var PHPExcel_Reader_IReader
     */
    private $phpExcelReader = null;

    //样式预定义
    protected $headTitleStyle = null;
    protected $titleStyle = null;
    protected $bodyStyle = null;

    /**
     * ExcelExtensionModel constructor.
     * ExcelExtensionModel constructor.
     */
    public function __construct(){

        $this->getPhpExcelObject();

        $this->headTitleStyle = array('font' => array('bold' => true, 'color' => array('argb' => '00000000'), 'size' => 18),
            'alignment' => array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
                'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER));
        $this->titleStyle = array('font' => array('bold' => false,'color'=>array('argb' => '00000000'),'size'=>10),
            'alignment' => array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
                'vertical'=>PHPExcel_Style_Alignment::VERTICAL_CENTER));
        $this->bodyStyle = array('font' => array('bold' => false,'color'=>array('argb' => '00000000'),'size'=>10),
            'alignment' => array('vertical'=>PHPExcel_Style_Alignment::VERTICAL_CENTER));
    }

    /**
     * 设置全局样式
     * @param array|null $global_style 全局样式配置
     */
    public function setGlobalStyle(array $global_style=null){
        if(isset($global_style)){
            $this->headTitleStyle = $this->titleStyle = $this->bodyStyle = $global_style;
        }
    }

    /**
     * 设置到处的excel的大标题
     * @param string $title 大标题文本
     * @param int $colnum 大标题占的列的数目
     * @param array $style 大标题风格
     * @throws PHPExcel_Exception
     */
    public function setBigTitle($title,$colnum,$style=null){
        if(is_numeric($colnum)) $colnum = self::$cellName[$colnum];
        //合并单元格
        $this->phpExcel->getActiveSheet()->mergeCells('A1:' . $colnum . '1');
        //设置样式
        $this->phpExcel->getActiveSheet()->getStyle('A1')->applyFromArray(isset($style)?$style:$this->headTitleStyle);
        //设置单元格值(设置值和样式的区别就是getActiveSheet和setActiveSheetIndex)
        $this->phpExcel->setActiveSheetIndex()->setCellValue('A1', $title);
    }

    /**
     * 返回PHPExcelObject
     * @return PHPExcel
     */
    public function getPhpExcelObject(){
        if(null === $this->phpExcel){
            vendor("PHPExcel.PHPExcel");//导入PHPExcel类，自定义的实现中要出现
            $this->phpExcel = new PHPExcel();
        }
        return $this->phpExcel;
    }


    /**
     * 获取列的名称
     * @param int $num
     * @return string
     */
    public function getColumnNameByNum($num){
        return self::$cellName[$num];
    }


    /**
     * 根据指定的格式导出设置
     * @param array $data 导出的数据格式和内容
     * @param null $opfilename  导出的文件名称，缺失时默认使用文件标题+日期
     * @throws PHPExcel_Exception
     */
    public function export($data, $opfilename = null){
        $date = date('Ymd', time());
        $xlsTitle = iconv('utf-8', 'gb2312', $data['title']);
        $fileName = isset($opfilename) ? $opfilename : "{$xlsTitle}-{$date}" ;

        $cellTitle = $data['head'];
        $cellValue = $data['body'];
        $cellNum = count($cellTitle);
        $dataNum = count($cellValue);
        $keys = array_keys($cellTitle);
        //记录数据域单元格对齐信息
        $titlealign = array();
        $titletype = array();

        /*-- 设置大标题 --*/
        $this->setBigTitle($data['title'],self::$cellName[$cellNum - 1],isset($data['titledstyle'])?$data['titledstyle']:null);

        /*-- 设置标题列 --*/
        $activeSheet = $this->phpExcel->getActiveSheet();
        $i = 0;
        foreach($cellTitle as $key=>$val) {
            $cellname = self::$cellName[$i] . '2';
            $cellval = isset($cellTitle[$key]['title'])?$cellTitle[$key]['title']:$cellTitle[$key][0];//用0作角标可以简化配置
            //居中设置
            switch($cellTitle[$key]['type']) {
                case self::TYPE_NUM:
                    $this->phpExcel->setActiveSheetIndex(0)->setCellValueExplicit($cellname,$cellval , PHPExcel_Cell_DataType::TYPE_NUMERIC);
                    break;
                case self::TYPE_STR:
                case null://null的情况下设为字符模式
                default:
                    $this->phpExcel->setActiveSheetIndex(0)->setCellValueExplicit($cellname, $cellval,PHPExcel_Cell_DataType::TYPE_STRING);
            }
            $titletype[$i]  = $cellTitle[$key]['type'];
            $titlealign[$i] = $cellTitle[$key]['align'];
            //宽度设置
            $activeSheet->getColumnDimension(self::$cellName[$i])->setWidth(isset($cellTitle[$key]['width'])?$cellTitle[$key]['width']:self::DEFALUT_CELL_WIDTH);
            //标题栏样式设计
            $activeSheet->getStyle($cellname)->applyFromArray(isset($data['headstyle'])?$data['headstyle']:$this->titleStyle);
            $i++;
        }

        /*-- 设置数据列 --*/
        for ($i = 0; $i < $dataNum; $i++) {
            $row = $cellValue[$i];
            for ($j = 0; $j < $cellNum; $j++) {
                $curcellname = self::$cellName[$j] . ($i + 3);
                //应用对齐设置
                $alignObj =$activeSheet->getStyle($curcellname)->getAlignment();
                switch($titlealign[$j]){
                    case self::ALI_RIGHT:
                        $alignObj->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
                        break;
                    case self::ALI_LEFT:
                        $alignObj->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
                        break;
                    case self::ALI_CENTER://默认居中
                    default:
                        $alignObj->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                }
                //值与风格设置
                $valueObj = $this->phpExcel->setActiveSheetIndex(0);
                switch($titletype[$j]) {
                    case self::TYPE_NUM:
                        $valueObj->setCellValueExplicit($curcellname,$row[$keys[$j]] , PHPExcel_Cell_DataType::TYPE_NUMERIC);
                        break;
                    case self::TYPE_STR:
                    case null://null的情况下设为字符模式
                    default:
                        $valueObj->setCellValueExplicit($curcellname, $row[$keys[$j]],PHPExcel_Cell_DataType::TYPE_STRING);
                }
                $activeSheet->getStyle($curcellname)->applyFromArray(isset($data['bodystyle'])?$data['bodystyle']:$this->bodyStyle);
            }
        }
        $this->output($fileName);
    }

    /**
     * 输出文件
     * @param string $filename 输出文件名
     * @throws PHPExcel_Reader_Exception
     */
    public function output($filename){
        ob_end_clean();//清除Bom和其他输出，否则会导致乱码
        header('pragma:public');
        header("Content-type:application/vnd.ms-excel;charset=utf-8;name='{$filename}.xls'");
        header("Content-Disposition:attachment;filename={$filename}.xls");//attachment新窗口打印inline本窗口打印
        $objWriter = PHPExcel_IOFactory::createWriter($this->phpExcel, 'Excel5');
        $objWriter->save('php://output');
        exit;
    }

    /**
     * @param array $map 数据域映射关系对象 兼 表头域验证
     *  格式 array( 'column_name01' => '第一列名称' )
     * @param callable $callback 回调函数对象,处理表头
     * @param int $datastartline 默认数据开始的行，默认为1 表示从第一行开始
     * @return array 数据域的二维数组
     * @throws PHPExcel_Reader_Exception
     */
    public function import($map,$callback = null,$datastartline = 1){
        $data = array();
        $headlines = null;
        $file = $this->getUploadFile();
        if(!$file || is_string($file)){
            return is_string($file)?$file:'无法获取导入的文件!';
        }

        $filename = $file[0]['savepath'] . $file[0]['savename'];
        if( $file[0]['extension'] =='xlsx' ){
            $this->phpExcelReader = new PHPExcel_Reader_Excel2007();
        }else{
            $this->phpExcelReader = new PHPExcel_Reader_Excel5();
        }
//        $this->phpExcelReader = PHPExcel_IOFactory::createReader('Excel5');
        $this->phpExcel = $this->phpExcelReader->load($filename);
        //获取第0张sheet对象
        $sheet = $this->phpExcel->getSheet(0);
        //总行数和总列数
        $highestRow = intval($sheet->getHighestRow());
        $sheetData = $sheet->toArray(null,true,true,true);
        //获取表头数组 表头所在行默认为数据域开始行的前一行,且无法修改
        $headlineno = $datastartline-1;
        if($headlineno < 0){
            return '标题栏缺失 ! ';
        }
        $headlines = $sheetData[$headlineno+1];

        for ($i = $headlineno; $i < $highestRow; $i++) {
            //仅第一次验证headline
            if ($i === $headlineno) {
                foreach ($map as $key => $colnm) {
                    //不想要验证这一行，可能是无关紧要的数据
                    if (isset($coltitlenm)) {
                        if (trim($headlines[$colnm]) !== $colnm) {
                            return "The head format of Excel on '{$headlines[$colnm]}' is not equal to '{$colnm}'! ";
                        }
                    }
                }
                continue;
            }

            //获取数据域
            $row = array();
            $temp = $sheetData[$i+1];
            foreach ($map as $key => $colnm) {
                $cellval = array_shift($temp);
                if (isset($callback) && is_callable($callback) ) {
                    $cellval = $callback($key,$colnm, $cellval, $i);
                }
                $row[$key] = isset($cellval)?$cellval.'':'';
            }
            $data[] = $row;
        }
        return $data;
    }
    /**
     * @return array 文件信息数组
     *         bool  未检测到文件上传
     *         string   文件上传失败信息
     */
    protected function getUploadFile(){
        import('ORG.Util.UploadFile');
        $config = array(
            'allowExts' => array('xlsx', 'xls'),
            'savePath' => './uploads/',
            'maxSize'   => 3145728 ,
        );
        $upload = new UploadFile($config);

//        varsdumpout($_FILES);

        if ($upload->upload()) {
            $info = $upload->getUploadFileInfo();
            $rst =  empty($info)?$upload->getErrorMsg():$info;
            return $rst;
        }
        return false;
    }

}