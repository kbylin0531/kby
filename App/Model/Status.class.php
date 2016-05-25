<?php

/**
 * Created by PhpStorm.
 * User: kbylin
 * Date: 25/05/16
 * Time: 10:00
 */
namespace App\Model;
use System\Library\Model;

class Status extends Model{

    const TABLE_NAME = '';//用于指定本模型对应的表,只允许字符串类型

    public function listClassCoursePlan($classno,$year,$term){
        $sql = '
select 
COURSES.COURSENAME as coursename,
R12.COURSENO as courseno,
COURSEAPPROACHES.[VALUE] as approach ,
COURSETYPEOPTIONS.[VALUE] as coursetype,
R12.CREDITS as credit,
PROGRAMS.PROGRAMNO as programno,
PROGRAMS.PROGNAME as programname
from PROGRAMS
INNER JOIN R16 on R16.PROGRAMNO = PROGRAMS.PROGRAMNO
INNER JOIN R12 on R12.PROGRAMNO = PROGRAMS.PROGRAMNO
INNER JOIN COURSES on COURSES.COURSENO = R12.COURSENO
INNER JOIN COURSEAPPROACHES on R12.COURSETYPE = COURSEAPPROACHES.NAME
INNER JOIN COURSETYPEOPTIONS on COURSETYPEOPTIONS.NAME = R12.CATEGORY
WHERE R16.CLASSNO = :classno and PROGRAMS.PROGRAMNO like :yearterm
ORDER BY R12.CATEGORY';

    }


}