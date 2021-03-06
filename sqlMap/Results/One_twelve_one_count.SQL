select count(*) as ROWS from(SELECT row_number() over(ORDER BY SUM(POINT) DESC) as row,SCORES.STUDENTNO AS xh, STUDENTS.NAME AS xm, SUM(POINT)
      AS jdh
FROM SCORES, STUDENTS
WHERE YEAR = :YEAR AND TERM = :TERM AND STUDENTS.CLASSNO = :CLASSNO AND
      SCORES.STUDENTNO = STUDENTS.STUDENTNO
GROUP BY SCORES.STUDENTNO, STUDENTS.NAME
) as b