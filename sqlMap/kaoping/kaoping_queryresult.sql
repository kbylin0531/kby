SELECT 
TEACHERS.NAME AS TEACHERNAME,
temp.TEACHERNO AS TEACHERNO,
temp.COURSENO AS COURSENO,
COURSES.COURSENAME AS COURSENAME,
temp.TASK ,
YEAR,
TERM,
RECNO,
TEMP.ENABLED,
temp.lock
FROM 教学质量评估综合 as temp 
INNER JOIN TEACHERS ON TEACHERS.TEACHERNO=temp.TEACHERNO
INNER JOIN COURSES ON COURSES.COURSENO=SUBSTRING(temp.COURSENO,1,7)
where  YEAR=:YEAR AND TERM=:TERM AND TEMP.COURSENO LIKE :COURSENO AND COURSENAME LIKE :COURSENAME AND TASK LIKE :TASK
	AND TEACHERS.NAME LIKE :TEACHERNAME AND TEMP.ENABLED LIKE :ENABLED
ORDER BY TEMP.COURSENO