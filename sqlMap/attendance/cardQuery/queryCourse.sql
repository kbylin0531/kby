SELECT distinct S.COURSENO+S.[GROUP] COURSENO,C.COURSENAME,T2.NAME NAME,
(SELECT COUNT(*) FROM R32 WHERE R32.[YEAR]=S.[YEAR] AND R32.TERM=S.TERM AND R32.COURSENO=S.COURSENO AND R32.[GROUP]=S.[GROUP]) [COUNT] FROM SCHEDULEPLAN S  
LEFT JOIN TEACHERPLAN T ON S.RECNO=T.MAP LEFT JOIN TEACHERS T2 ON T.TEACHERNO=T2.TEACHERNO LEFT JOIN COURSES C ON C.COURSENO=S.COURSENO 
WHERE S.YEAR=:YEAR AND S.TERM=:TERM AND S.COURSENO+S.[GROUP]=:COURSENO