SELECT S.STUDENTNO,S2.COURSENO,SUBSTRING(S2.COURSETYPE,1,1) COURSETYPE FROM STUDENTS S
	LEFT OUTER JOIN (SELECT R32.STUDENTNO,R32.COURSENO,C.[VALUE] COURSETYPE
	FROM R32 LEFT JOIN COURSEAPPROACHES C ON R32.COURSETYPE = C.NAME WHERE R32.YEAR=:YEAR AND R32.TERM=:TERM)AS S2
	ON S.STUDENTNO=S2.STUDENTNO
WHERE S.CLASSNO=:CLASSNO
ORDER BY S.STUDENTNO