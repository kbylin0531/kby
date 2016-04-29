SELECT 学生考勤表.STUDENTNO FROM 学生考勤表 INNER JOIN 
(SELECT RECNO,CASE [请假理由] WHEN 'A' THEN 1 WHEN 'D' THEN 0.33 WHEN 'E' THEN 0.33 ELSE 0 END AS BASE FROM 学生考勤表) KAOQIN 
ON KAOQIN.RECNO = 学生考勤表.RECNO 
INNER JOIN STUDENTS ON STUDENTS.STUDENTNO=学生考勤表.STUDENTNO 
INNER JOIN SCHOOLS ON STUDENTS.SCHOOL=SCHOOLS.SCHOOL 
INNER JOIN COURSES ON COURSES.COURSENO=SUBSTRING(学生考勤表.COURSENO,1,7) 
WHERE  学生考勤表.YEAR LIKE :YEAR AND TERM LIKE :TERM AND STUDENTS.SCHOOL LIKE :SCHOOL 
AND EXISTS (SELECT * FROM COURSES WHERE COURSES.COURSENO=SUBSTRING(学生考勤表.COURSENO,1,7)) 
GROUP BY 学生考勤表.STUDENTNO,COURSES.COURSENO,COURSES.HOURS 
HAVING SUM(学时 * BASE) >= ((COURSES.HOURS * :WEEK) / 3) 