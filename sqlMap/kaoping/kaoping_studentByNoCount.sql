SELECT COUNT(*) [COUNT] FROM (SELECT S.STUDENTNO FROM STUDENTS S 
INNER JOIN CLASSES C ON S.CLASSNO=C.CLASSNO 
INNER JOIN SCHOOLS S2 ON S2.SCHOOL=S.SCHOOL 
INNER JOIN STATUSOPTIONS S3 ON S3.NAME=S.STATUS WHERE C.CLASSNO=:CLASSNO) KP