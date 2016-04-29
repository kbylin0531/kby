select A.学年 AS YEAR,A.学期 AS TERM,A.教师号 AS TEACHNO,A.教师 AS TEACHNAME,A.课号 AS COURSENO,A.课名
AS COURSENAME,A.参加人数 AS JOINNUM,A.完成人数 AS FINISHNUM,A.有效人数 AS VALIDNUM,A.态度 AS MANNER,A.内容 AS CONTENT,A.方法 AS METHOD,A.效果 AS EFFECT,
A.类型 AS TYPE,A.得分 AS SCORE
from 教学质量评估成绩 AS A
where A.学年=:YEAR and A.学期=:TERM and A.课号 like :COURSENO
and A.教师 like :TEACHERNAME and A.课名 like :COURSENAME