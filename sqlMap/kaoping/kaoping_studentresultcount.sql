select count(*) as ROWS from (SELECT COUNT(教学质量评估综合.RECNO) AS ROWS
FROM 教学质量评估综合 INNER JOIN TEACHERS ON TEACHERS.TEACHERNO=教学质量评估综合.TEACHERNO
INNER JOIN COURSES ON COURSES.COURSENO=SUBSTRING(教学质量评估综合.COURSENO,1,7)
LEFT OUTER  join 教学质量评估详细 on 教学质量评估详细.map=教学质量评估综合.recno
WHERE 教学质量评估综合.YEAR=:YEAR AND 教学质量评估综合.TERM=:TERM AND TEACHERS.NAME LIKE :TEACHERNAME
AND 教学质量评估综合.courseno LIKE :COURSENO AND COURSES.COURSENAME LIKE :COURSENAME
AND 教学质量评估综合.task LIKE :TASK
GROUP BY TEACHERS.NAME,教学质量评估综合.TEACHERNO,教学质量评估综合.COURSENO,COURSENAME,
教学质量评估综合.TASK,教学质量评估综合.YEAR,教学质量评估综合.TERM,教学质量评估综合.RECNO) as b
