SELECT S.STUDENTNO,S.NAME,S4.NAME AS SEX,C.CLASSNAME,S3.[VALUE] STATUS,S.WARN,S.POINTS,S.TAKEN,S.PASSED,S2.NAME AS SCHOOLNAME,S.ENTERDATE 
FROM STUDENTS S LEFT JOIN CLASSES C ON S.CLASSNO = C.CLASSNO LEFT JOIN SCHOOLS S2 ON S.SCHOOL = S2.SCHOOL LEFT JOIN 
	STATUSOPTIONS S3 ON S.STATUS = S3.NAME LEFT JOIN SEXCODE S4 ON S.SEX = S4.CODE 
WHERE RTRIM(S.STUDENTNO) LIKE :STUDENTNO AND S.NAME LIKE :NAME AND S.CLASSNO LIKE :CLASSNO AND S.SCHOOL LIKE :SCHOOL AND S.STATUS LIKE :STATUS 
ORDER BY S.ENTERDATE DESC