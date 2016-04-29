<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2015/8/19
 * Time: 10:37
 */
class RoomModel extends CommonModel{

    /**
     * 更新教师信息
     * @param $roomno
     * @param $updFields
     * @return int|string
     */
    public function updateRoom($roomno,$updFields){
        return $this->updateRecords('ClASSROOMS',$updFields,array(
            'ROOMNO'    => $roomno,
        ));
    }

    /**
     * @param $fields
     * @return int|string
     */
    public function createRoom($fields){
        return $this->createRecord('CLASSROOMS',$fields);
    }


    /**
     * 获取性别的代号名称映射
     * 对于SexCode表，code是代号
     * @param bool $keyIsName 键是值还是代号
     * @return array|string string表示查询发生了错误
     */
    public function getAreaCodeMap($keyIsName=false){
        $sex = $this->getTable('AREAS');
        if(is_string($sex)){
            return $sex;
        }
        $rst = array();
        foreach($sex as $row){
            if($keyIsName){
                $rst[trim($row['NAME'])] = trim($row['VALUE']);
            }else{
                $rst[trim($row['VALUE'])] = trim($row['NAME']);
            }
        }
        return $rst;
    }

    public function importClassrooms($info){
        $info = $this->initImport($info);
        $sheetData = $info[1];

        $message = array('index'=>'','debug'=>'');

        $key_val = array (
            '教室号'=>'A',      // ****
            '房间号'=>'B',      // ****
            '简称'=>'C',      // ****
            '楼名'=>'D',      // ****
            '校区'=>'E',
            '座位数'=>'F',
            '考位数'=>'G',
            '设施'=>'H',    // ****
            '优先学部'=>'I',       // ****
            '排课约束'=>'J',// ****
            '是否保留'=>'K',
        );

        foreach ( $sheetData as $key => $words ){
            if ( $key != 1 ){//第一行作为标题栏
                $roomno = trim($words[$key_val['教室号']]);
                $no = trim($words[$key_val['房间号']]);
                $jsn = trim($words[$key_val['简称']]);
                $building = trim($words[$key_val['楼名']]);
                $area = trim($words[$key_val['校区']]);
                $seats = trim($words[$key_val['座位数']]);
                $testers = trim($words[$key_val['考位数']]);
                $equipment = trim($words[$key_val['设施']]);
                $priority = trim($words[$key_val['优先学部']]);
                $usage = trim($words[$key_val['排课约束']]);
                $reserved = trim($words[$key_val['是否保留']]);


                $_areas = $this->getAreaCodeMap();
                $_schools = $this->getSchoolMap();

                $iresult = array();
                $iresult["row"] = $key;

                if (!empty($area)) $area = $_areas[$area];
                if (!empty($priority)) $priority = $_schools[$priority];
                if (!empty($priority)) $reserved = $reserved === '是'?1:0;

                $fields = array(
                    'ROOMNO' => $roomno,
                    'NO' => $no,
                    'BUILDING' => $building,
                    'SEATS' => $seats,
                    'TESTERS' => $testers,
                    'JSN' => $jsn,
                    'AREA' => $area,
                    'STATUS'    => '1',
                    'EQUIPMENT' => $equipment,
                    'PRIORITY' => $priority,
                    'USAGE' => $usage,
                    'RESERVED' => $reserved,
                );
                $rst = $this->createRoom($fields);
                if(is_string($rst)){
                    $message['index'] .= "{$key},";
                    $message['debug'] .= var_export($fields,true)."{$rst}\n";
                }
            }
        }
        return $message;
    }





}