<?php

class ExcelAction extends  Action{

    /**实现导入excel
     **/
    public function import($map){
        if (!empty($_FILES)) {
            import('ORG.Util.UploadFile');
            $config=array(
                'allowExts'=>array('xlsx','xls'),
                'savePath'=>'./upload/',
                'saveRule'=>'time',
            );
            $upload = new UploadFile($config);
            if (!$upload->upload()) {
                $this->error($upload->getErrorMsg());
            } else {
                $info = $upload->getUploadFileInfo();
            }

            vendor("PHPExcel.PHPExcel");
            $file_name=$info[0]['savepath'].$info[0]['savename'];
            $objReader = PHPExcel_IOFactory::createReader('Excel5');
            $objPHPExcel = $objReader->load($file_name,$encode='utf-8');
            $sheet = $objPHPExcel->getSheet(0);
            $highestRow = $sheet->getHighestRow(); // 取得总行数
            $highestColumn = $sheet->getHighestColumn(); // 取得总列数
            $json = array('data'=>array());

            for($i=3;$i<=$highestRow;$i++){
                foreach($map as $key=>$val){
                    $data[$map[$key]['fieldname']]= $objPHPExcel->getActiveSheet()->getCell($map[$key]['key'].$i)->getValue();
                }

                $json['data'][] = $data;
            }
            $json['msg'] = 'success';
// 			$this->success('导入成功！');
        }else{
            $json['msg'] = 'failure';
//            $this->error("请选择上传的文件");
        }
        return $json;

    }


















}