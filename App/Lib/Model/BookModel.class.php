<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2015/8/18
 * Time: 12:46
 */
class BookModel extends CommonModel{

    /**
     * 获取学生端我的教材列表内容
     * @param $studentno
     * @param $offset
     * @param $limit
     * @return array|string
     */
    public function getStudentBookTableList($studentno,$offset,$limit){
        $fields = '
book_student.[year] AS YEAR,
book_student.term AS TERM,
books.name AS BOOKSNAME,
books.editor AS EDITOR,
books.publish AS PUBLISH,
books.price AS PRICE,
books.rbprice AS RBPRICE,
book_student.amount AS AMOUNT,
books.rbprice * book_student.amount AS TOTALAMOUNT';
        $where = 'WHERE (book_student.studentno = :STUDENTNO)';
        $order = '[YEAR],TERM';
        $csql = $this->makeCountSql('book_student',array(
            'where' => $where,
        ));
        $ssql = $this->makeSql('book_student',array(
            'fields'    => $fields,
            'where'     => $where,
            'order'     => $order,
        ),$offset,$limit);
        $bind = array(
            ':STUDENTNO'    => $studentno,
        );
        return $this->getTableList($csql,$ssql,$bind);
    }

    /**
     * 新版获取学生教材列表内容
     * @param $studentno
     * @param $offset
     * @param $limit
     * @return array|string
     */
    public function getNewStudentBookTableList($studentno,$offset,$limit){
        //问题 数据表中未出现
        $sql = '
select [year],term,bookname,author,press,price,dis_price,count(*) [count],sum(dis_price) [sum] from  STUDENTBOOK bs
where bs.studentno=:STUDENTNO
group by [year],term,bookname,author,press,price,dis_price,studentno,book_id
order by year,term ';
        $bind = array(
            ':STUDENTNO'    => $studentno
        );
        $rst = $this->sqlQuery($sql,$bind);
        if(is_string($rst)){
            return $this->getErrorMessage();
        }
        $json = array();
        $json['total']  = count($rst);
        $json['rows']   = $rst;
        return $json;
    }

}