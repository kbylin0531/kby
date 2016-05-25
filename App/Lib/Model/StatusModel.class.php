<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2015/8/3
 * Time: 16:13
 */

class StatusModel extends CommonModel{


    /**
     * 添加学籍变更记录
     * @param $studentno
     * @param $year
     * @param $term
     * @param $regdate
     * @param $regcode
     * @return array
     */
    public function addRegisterChangeRecord($studentno,$year,$term,$regdate,$regcode){
        $leftHand = array(
            ':studentno'=>$studentno,
            ':year'=>$year,
            ':term'=>$term,
        );
        $rightHand = array(
            ':regdate'=>$regdate,
            ':regcode'=>$regcode,
        );
        $istSql = 'insert into REGDATA values(:studentno,:year,:term,:regdate,:regcode)';
        $updSql = 'UPDATE REGDATA SET REGDATE=:regdate,REGCODE=:regcode WHERE (STUDENTNO=:studentno) AND (YEAR=:year)AND (TERM=:term)';//插入失败时说明已经有了这条记录，执行更新
        $rst = $this->sqlExecute($istSql,array_merge($leftHand,$rightHand));
        if(!$rst){
            if(!$this->sqlExecute($updSql,array_merge($rightHand,$leftHand))){
                return array(
                    'error',
                    $this->getDbError()
                );
            }else{
                return array(
                    'info',
                    'update'
                );
            }
        }
        return array(
            'info',
            'insert'
        );
    }


    /**
     * 获取学生学籍状态
     * @param $studentno
     * @param $year
     * @param $term
     * @return string
     */
    public function getStudentRegisterStatus($studentno,$year,$term){

        $whr = $this->makeWhere(array(
            'STUDENTNO' => $studentno,
            'YEAR'      => $year,
            'TERM'      => $term,
        ));

        $sql = $this->makeSql('REGDATA',array(
            'join'  => 'INNER JOIN REGCODEOPTIONS on REGDATA.REGCODE = REGCODEOPTIONS.NAME',
            'where' => $whr[0],
        ));


        $rst = $this->sqlQuery($sql,$whr[1]);

        if(false === $rst){
            return $this->getDbError();
        }elseif($rst){
            return $rst[0];
        }else{
            return "Can not found Student of id [{$studentno}] in [{$year}-{$term}]";
        }
    }


    /**
     * 获取性别的代号名称映射
     * 对于SexCode表，code是代号
     * @param bool $keyIsName 键是值还是代号
     * @return array|string string表示查询发生了错误
     */
    public function getSexCodeMap($keyIsName=false){
        $sex = $this->getTable('SexCode');
        if(is_string($sex)){
            return $sex;
        }
        $rst = array();
        foreach($sex as $row){
            if($keyIsName){
                $rst[trim($row['NAME'])] = $row['CODE'];
            }else{
                $rst[$row['CODE']] = trim($row['NAME']);
            }
        }
        return $rst;
    }

    /**
     * 获取学籍状态的代号名称映射
     * 对于STATUSOPTIONS，name字段是代号
     * @param bool $keyIsName
     * @return array|string
     */
    public function getStatusOptionMap($keyIsName=false){
        $statusOption = $this->getTable('STATUSOPTIONS');
        if(is_string($statusOption)){
            return $statusOption;
        }
        $rst = array();
        foreach($statusOption as $row){
            if($keyIsName){
                $rst[trim($row['VALUE'])] = $row['NAME'];
            }else{
                $rst[$row['NAME']] = trim($row['VALUE']);
            }
        }
        return $rst;
    }



    /**
     * 获取政治面貌
     * @param bool $keyIsName
     * @return array
     */
    public function getPartyCode($keyIsName=false){
        $party = $this->getTable('PARTYCODE');
        if(is_string($party)){
            return $party;
        }
        $rst = array();
        foreach($party as $row){
            if($keyIsName){
                $rst[trim($row['NAME'])] = $row['CODE'];
            }else{
                $rst[$row['CODE']] = trim($row['NAME']);
            }
        }
        return $rst;
    }
    public function getNationCode($keyIsName=false){
        $party = $this->getTable('NATIONALITYCODE');
        if(is_string($party)){
            return $party;
        }
        $rst = array();
        foreach($party as $row){
            if($keyIsName){
                $rst[trim($row['NAME'])] = $row['CODE'];
            }else{
                $rst[$row['CODE']] = trim($row['NAME']);
            }
        }
        return $rst;
    }


    public function insertStudents($fields){
        return $this->createRecord('STUDENTS',$fields);
    }
    public function insertPersonal($fields){
        return $this->createRecord('PERSONAL',$fields);
    }

    public function listPersons($studentno,$studentname,$grade,$classno,$school,$limit=null,$offset=null){
        $fields = '
pn.studentno ,
RTRIM(sts.NAME) as studentname,
LEFT(convert(varchar, sts.ENTERDATE, 112),4) as grade,
RTRIM(MAJORCODE.name) as majorname,
RTRIM(MAJORS.class_nature_name) as majoritemname,
convert(varchar, pn.birthday, 102) as birthday,
RTRIM(nnc.name) as nationality,
RTRIM(PARTYCODE.name) as party,
RTRIM(pn.tel) as telephone';
        $join = '
LEFT OUTER JOIN STUDENTS  sts on sts.STUDENTNO = pn.studentno
LEFT OUTER JOIN CLASSES on sts.classno = CLASSES.classno
LEFT OUTER JOIN SCHOOLS on SCHOOLS.school = CLASSES.school
LEFT OUTER JOIN MAJORCODE on MAJORCODE.CODE = pn.MAJOR
LEFT OUTER JOIN MAJORS on MAJORS.MAJORNO = pn.MAJOR and MAJORS.CLASS_NATURE = pn.CLASS_NATURE
LEFT OUTER JOIN NATIONALITYCODE nnc on nnc.code = pn.nationality
LEFT OUTER JOIN PARTYCODE on PARTYCODE.code = pn.party';
        $where = '
sts.STUDENTNO like :studentno
and RTRIM(sts.NAME) like :studentname
and LEFT(convert(varchar, sts.ENTERDATE, 112),4) like :grade
and CLASSES.CLASSNO like :classno
and SCHOOLS.SCHOOL like :school';
        return $this->getTableList(
            $this->makeCountSql('PERSONAL pn',array(
                'join'  => $join,
                'where' => $where,
            )),
            $this->makeSql('PERSONAL pn',array(
                'fields'    => $fields,
                'join'  => $join,
                'where' => $where,
            ),$limit,$offset),
            array(
                ':studentno' => $studentno,
                ':studentname' => $studentname,
                ':grade' => $grade,
                ':classno' => $classno,
                ':school' => $school,
            )
        );
    }

    public function listStudents($studentno,$studentname,$grade,$classno,$school,$limit=null,$offset=null){
        $fields = '
sts.STUDENTNO as studentno,
RTRIM(sts.NAME) as studentname,
CASE
	WHEN  sts.SEX = \'M\' then \'男\'
	WHEN  sts.SEX = \'F\' then \'女\'
	ELSE \'\' end as sexname,
CONVERT(VARCHAR (10),sts.ENTERDATE,20) AS enterdate,
sts.YEARS as years,
RTRIM(CLASSES.CLASSNAME) as classname,
sts.WARN as warn,
sts.classno,
sts.taken as taken,
sts.PASSED as passed,
RTRIM(STATUSOPTIONS.[VALUE]) as status,
RTRIM(SCHOOLS.NAME) as schoolname';
        $join = '
-- INNER JOIN PERSONAL on PERSONAL.studentno = sts.STUDENTNO
-- INNER JOIN MAJORCODE on MAJORCODE.CODE = PERSONAL.MAJOR
-- INNER JOIN MAJORS on MAJORS.MAJORNO = PERSONAL.MAJOR and MAJORS.CLASS_NATURE = PERSONAL.CLASS_NATURE
INNER JOIN CLASSES on CLASSES.CLASSNO = sts.classno
INNER JOIN STATUSOPTIONS on sts.STATUS = STATUSOPTIONS.NAME
INNER JOIN SCHOOLS on SCHOOLS.SCHOOL = CLASSES.SCHOOL';
        $where = ' 
         sts.STATUS = \'A\'
and sts.STUDENTNO like :studentno
and RTRIM(sts.NAME) like :studentname
and LEFT(convert(varchar, sts.ENTERDATE, 112),4) like :grade
and CLASSES.CLASSNO like :classno
and SCHOOLS.SCHOOL like :school';
 $order ='
        CLASSES.CLASSNO';
        return $this->getTableList(
            $this->makeCountSql('STUDENTS sts',array(
                'join'  => $join,
                'where' => $where,
            )),
            $this->makeSql('STUDENTS sts',array(
                'fields'    => $fields,
                'join'  => $join,
                'where' => $where,
                'order' => $order,
                ),$limit,$offset),
            array(
                ':studentno' => $studentno,
                ':studentname' => $studentname,
                ':grade' => $grade,
                ':classno' => $classno,
                ':school' => $school,
            )
        );
    }


    /**
     * 获取学生学籍信息列表
     * @param $bind
     * @param $offset
     * @param $limit
     * @return array|string
     */
    public function getStudentStatusTableList($bind,$offset=null,$limit=null){
        $fields = '
STUDENTS.STUDENTNO,
STUDENTS.NAME,
SEXCODE.NAME AS SEX,
STUDENTS.ENTERDATE,
STUDENTS.YEARS,
STUDENTS.CLASSNO,
STUDENTS.TAKEN,
STUDENTS.PASSED,
STUDENTS.POINTS,
STUDENTS.REG,
STUDENTS.WARN,
STUDENTS.STATUS,
STUDENTS.CONTACT,
STUDENTS.GRADE,
STUDENTS.SCHOOL,
CLASSES.CLASSNAME,
SCHOOLS.NAME AS SCHOOLNAME,
STATUSOPTIONS.VALUE AS STATUSVALUE,
STUDENTS.OLDSTUDENTNO,
PERSONAL.MAJOR,
MAJORCODE.NAME as MAJORNAME';
        $join = '
LEFT OUTER JOIN CLASSES ON STUDENTS.CLASSNO=CLASSES.CLASSNO
LEFT OUTER JOIN SCHOOLS ON STUDENTS.SCHOOL=SCHOOLS.SCHOOL
LEFT OUTER JOIN STATUSOPTIONS ON STUDENTS.STATUS=STATUSOPTIONS.NAME
LEFT OUTER JOIN SEXCODE ON STUDENTS.SEX=SEXCODE.CODE
LEFT OUTER JOIN PERSONAL on PERSONAL.studentno = STUDENTS.STUDENTNO
LEFT OUTER JOIN MAJORCODE on PERSONAL.MAJOR = MAJORCODE.CODE';
        $where = '
RTRIM(STUDENTS.StudentNo) LIKE :StudentNo
and STUDENTS.Name like :Name1
and STUDENTS.ClassNo like :ClassNo
and STUDENTS.School like :School
and STUDENTS.Status like :Status';
        $order = 'STUDENTS.ClassNo';
        $csql = $this->makeCountSql('STUDENTS',array(
            'join'  => $join,
            'where' => $where
        ));
        $ssql = $this->makeSql('STUDENTS',array(
            'fields'    => $fields,
            'join'  => $join,
            'where' => $where,
            'order'   => $order,
        ),$offset,$limit);
        return $this->getTableList($csql,$ssql,$bind);
    }

    /**
     * 获取学生注册历史记录
     * @param $studentno
     * @return array|string
     */
    public function getStudentRegisterHistory($studentno){
        $sql = '
SELECT REGDATA.YEAR,
REGDATA.TERM,
REGDATA.REGDATE,
REGDATA.REGCODE,
RTRIM(REGCODEOPTIONS.VALUE) AS REGVALUE
FROM REGDATA
LEFT OUTER JOIN REGCODEOPTIONS ON REGDATA.REGCODE=REGCODEOPTIONS.NAME
WHERE RTRIM(REGDATA.STUDENTNO)=:studentno ';
        $bind = array(':studentno'  => $studentno);
        return $this->doneQuery($this->sqlQuery($sql,$bind));
    }

    /**
     * 获取学生注册信息详细信息
     * @param $studentno
     * @return array|string
     */
    public function getStudentRegisterInfo($studentno){
        $sql = '
SELECT
STUDENTS.STUDENTNO        as studentno,
RTRIM(STUDENTS.NAME)      as studentname,
STUDENTS.SEX              as sexcode,
RTRIM(SEXCODE.NAME)       as sexname,
CONVERT(varchar(10),STUDENTS.ENTERDATE,20) as enterdate,
STUDENTS.YEARS            as years,
RTRIM(STUDENTS.CLASSNO)   as classno,
RTRIM(CLASSES.CLASSNAME)  as classname,
STUDENTS.TAKEN            as token,
STUDENTS.PASSED           as passed,
STUDENTS.POINTS           as points,
STUDENTS.REG              as reg,
STUDENTS.WARN             as warn,
STUDENTS.STATUS           as status ,
RTRIM(STATUSOPTIONS.VALUE)  as statusname,
RTRIM(STUDENTS.CONTACT)   as contact,
STUDENTS.GRADE            as grade,
STUDENTS.SCHOOL           as school,
RTRIM(SCHOOLS.NAME)       as schoolname,
RTRIM(CS.NAME)	 		  as classschool,
PERSONAL.MAJOR            as major,
PERSONAL.ID               as id,
PERSONAL.PARTY            as party,
PERSONAL.NATIONALITY      as nationality,
CONVERT(varchar(10),PERSONAL.BIRTHDAY,20) as birthday,
PERSONAL.PHOTO            as photo,
RTRIM(PERSONAL.CLASS)     as class  -- 对应的表示classcode
FROM STUDENTS
INNER JOIN PERSONAL ON PERSONAL.STUDENTNO=STUDENTS.STUDENTNO
LEFT OUTER JOIN CLASSES ON STUDENTS.CLASSNO=CLASSES.CLASSNO
LEFT OUTER JOIN SCHOOLS CS ON CLASSES.SCHOOL=CS.SCHOOL
LEFT OUTER JOIN SCHOOLS ON STUDENTS.SCHOOL=SCHOOLS.SCHOOL
LEFT OUTER JOIN STATUSOPTIONS ON STUDENTS.STATUS=STATUSOPTIONS.NAME
LEFT OUTER JOIN SEXCODE ON STUDENTS.SEX=SEXCODE.CODE
where RTRIM(STUDENTS.STUDENTNO) =  :studentno';
        $bind = array(':studentno'  => $studentno);
        return $this->doneQuery($this->sqlQuery($sql,$bind),false);
    }

    /**
     * 获取学生信息
     * @param string $studentno
     * @param bool $onlycount 仅仅获取学生数量时候设置为true
     * @return array|int|string
     */
    public function getStudentInfo($studentno,$onlycount=false){
        return $this->getTable('STUDENTS',array(
            'studentno' => $studentno,
        ),$onlycount);
    }

    /**
     * 获取学生个人信息，通过学号或者准考证号
     * @param string $studentno
     * @param string $examno
     * @return array|string
     */
    public function getStudentPersonalInfo($studentno=null,$examno=null){
        $bind = array(
            ':no'   => $studentno,
        );
        $filter = ' RTRIM(personal.STUDENTNO) = :no ';
        if(!empty($examno)){
            $filter = ' RTRIM(personal.EXAMNO) = :no ';
            $bind[':no'] = $examno;
        }
        $sql = "
SELECT
    RTRIM(personal.STUDENTNO) 	as STUDENTNO,
    RTRIM(personal.NAME) 		as studentname,
    CONVERT(varchar(10),personal.BIRTHDAY,20) as BIRTHDAY,
    personal.NATIONALITY 		as NATIONALITY,
    personal.PARTY				as PARTY,
    RTRIM(personal.EXAMNO)		as EXAMNO,
    RTRIM(personal.CLASS)		as CLASS,
    personal.FEATURE,
    personal.PLANCLASS,
    personal.POSTCODE,
    personal.ADDRESS,
    personal.TEL,
    personal.MIDSCHOOL,
    personal.MAJOR,
    personal.YEARS,
    personal.SCHOOL,
    personal.SCORE,
    personal.REM,
    personal.ID,
    personal.PROVINCE,
    personal.BRANCH,
    CONVERT(varchar(10),personal.DAYOFENROLL,20) as enterdate,
    --查看，无法修改
    RTRIM(SEXCODE.NAME) 		as sexname,
    RTRIM(nationalitycode.NAME)	as nationalityname,
    RTRIM(partycode.NAME)		as partyname,
    RTRIM(classcode.NAME) 	    as classcodename,
    RTRIM(featurecode.NAME)     as featurename,
    RTRIM(planclasscode.NAME)	as planclasscodename,
    RTRIM(majorcode.NAME) 	    as majorcodename,
    RTRIM(schools.NAME) 		as schoolname,
    RTRIM(provincecode.NAME) 	as provincecodename,
    RTRIM(branchcode.NAME) 	    as branchcodename
FROM personal
LEFT OUTER JOIN nationalitycode ON personal.NATIONALITY = nationalitycode.CODE
LEFT OUTER JOIN partycode ON personal.PARTY = partycode.CODE
LEFT OUTER JOIN classcode ON personal.CLASS = classcode.CODE
LEFT OUTER JOIN featurecode ON personal.FEATURE = featurecode.CODE
LEFT OUTER JOIN planclasscode ON personal.PLANCLASS = planclasscode.CODE
LEFT OUTER JOIN majorcode ON personal.MAJOR = majorcode.CODE
LEFT OUTER JOIN schools ON personal.SCHOOL = schools.SCHOOL
LEFT OUTER JOIN provincecode ON personal.PROVINCE = provincecode.CODE
LEFT OUTER JOIN branchcode ON personal.BRANCH = branchcode.CODE
LEFT OUTER JOIN SEXCODE ON PERSONAL.SEX=SEXCODE.CODE
WHERE  {$filter} ";
//        mist($sql,$bind);
        return $this->doneQuery($this->sqlQuery($sql,$bind),false);
    }

    /**
     * 修改学生某学年学期的注册状态
     * @param $studentno
     * @param $year
     * @param $term
     * @param $regcode
     * @return int|string
     */
    public function updateStudentRegdata($studentno,$year,$term,$regcode){
        return $this->updateRecords('REGDATA',array(
            'REGCODE'   => $regcode,
            'REGDATE'   => date('Y-m-d'),
        ),array(
            'STUDENTNO' => $studentno,
            'YEAR'  => array($year,true),
            'TERM'  => array($term,true),
        ));
    }
    /**
     * 创建学生某学年学期的注册状态
     * @param $studentno
     * @param $year
     * @param $term
     * @param $regcode
     * @return int|string
     */
    public function createStudentRegdata($studentno,$year,$term,$regcode){
        return $this->createRecord('REGDATA',array(
            'REGCODE'   => $regcode,
            'REGDATE'   => date('Y-m-d'),
            'STUDENTNO' => $studentno,
            'YEAR'  => array($year,true),
            'TERM'  => array($term,true),
        ));
    }
    /**
     * 获取学生某学年学期的数据
     * @param $studentno
     * @param $year
     * @param $term
     * @return array|int|string
     */
    public function getStudentRegdata($studentno,$year,$term){
        return $this->doneQuery($this->getTable('REGDATA',array(
            'STUDENTNO' => $studentno,
            'YEAR'  => array($year,true),
            'TERM'  => array($term,true),
        )),false);
    }

    /**
     * 修改学生 信息
     * @param $studentno
     * @param $fields
     * @return int|string
     */
    public function updateStudentInfo($studentno,$fields){
        return $this->updateRecords('STUDENTS',$fields,array(
            'STUDENTNO' => $studentno
        ));
    }

    /**
     * 修改学生个人信息
     * @param $studentno
     * @param $fields
     * @return int|string
     */
    public function updatePersonInfo($studentno,$fields){
        return $this->updateRecords('PERSONAL',$fields,array(
            'STUDENTNO' => $studentno
        ));
    }

    /**
     * 通过班级号获取学生的全部信息
     * @param $classno
     * @return array|int|string
     */
    public function getStudentListByClassno($classno){
        return $this->getTable('STUDENTS',array(
            'CLASSNO'   => $classno,
        ));
    }

    /**
     * 添加学籍异动记录
     * @param $studentno
     * @param $date
     * @param $fileno
     * @param $rem
     * @param $info
     * @param $year
     * @param $term
     * @return int|string
     */
    public function insertRegisteries($studentno,$date,$fileno,$rem,$info,$year,$term){
        $sql = '
INSERT INTO [REGISTRIES]
([INFOTYPE], [DATE], [FILENO], [REM],year,term,
 [STUDENTNO],[studentname], [major], [majoritem], [schoolname], [classname])
SELECT
:info ,:date, :fileno, :rem,:year,:term,
RTRIM(STUDENTS.STUDENTNO),
RTRIM(STUDENTS.NAME),
RTRIM(majorcode.NAME ),
RTRIM(MAJORS.REM),
RTRIM(SCHOOLS.NAME) ,
RTRIM(CLASSES.CLASSNAME)
from STUDENTS
INNER JOIN PERSONAL on PERSONAL.studentno = STUDENTS.STUDENTNO
LEFT OUTER JOIN MAJORCODE on MAJORCODE.CODE = PERSONAL.MAJOR
LEFT OUTER JOIN MAJORS on MAJORS.MAJORNO = PERSONAL.MAJOR and MAJORS.CLASS_NATURE = PERSONAL.CLASS_NATURE
INNER JOIN CLASSES on CLASSES.CLASSNO = STUDENTS.CLASSNO
INNER JOIN SCHOOLS on SCHOOLS.SCHOOL = CLASSES.SCHOOL
WHERE STUDENTS.STUDENTNO = :studentno';
        return $this->doneExecute($this->sqlExecute($sql,array(
            ':info' => $info,
            ':date' => $date,
            ':fileno' => $fileno,
            ':rem' => $rem,
            ':year' => $year,
            ':term' => $term,
            ':studentno' => $studentno,
        )));
    }


    /**
     * 罗列学籍异动记录
     * @param $studentno
     * @param $fileno
     * @param $infotype
     * @param null $limit
     * @param null $offset
     * @return array|string
     */
    public function listRegisteries($studentno,$fileno,$infotype,$year=2015,$term=1,$limit=null,$offset=null){
        $fields = '
r.year,r.term,
r.recno,
r.STUDENTNO as STUDENTNO,
r.studentname as STUDENTNAME,
r.CLASSNAME,
r.SCHOOLNAME,
RTRIM(INFOTYPE.[NAME]) as INFOTYPEVALUE,
r.FILENO as FILENO,
r.REM as rem,
CONVERT(VARCHAR(10),[DATE],20) as FILEDATE';
        $join = 'INNER JOIN INFOTYPE on INFOTYPE.CODE = r.infotype';
        $where = '
RTRIM(r.STUDENTNO) like :studentno
and RTRIM(r.FILENO) like :fileno
and RTRIM(r.INFOTYPE) like :infotype
and r.year like :year
and r.term like :term';
        $order = 'FILEDATE desc';
        return $this->getTableList(
            $this->makeCountSql('REGISTRIES r',array(
                'join'      => $join,
                'where'     => $where,
            )),
            $this->makeSql('REGISTRIES r',array(
                'fields'    => $fields,
                'join'      => $join,
                'where'     => $where,
                'order'     => $order,
            ),$limit,$offset),
            array(
                ':studentno'    => $studentno,
                ':fileno'   => $fileno,
                ':infotype'  => $infotype,
                ':year'  => $year,
                ':term'  => $term,
            )
        );
    }

}