SELECT S.STUDENTNO,RTRIM(S.NAME) NAME,S.SEX,CONVERT(varchar(10), S.ENTERDATE, 23) ENTERDATE,S.YEARS,P.MAJOR,RTRIM(S.CLASSNO) CLASSNO,C.CLASSNAME,
S.TAKEN,S.PASSED,S.POINTS,S.WARN,S.STATUS,RTRIM(S.CONTACT) CONTACT,P.CLASS,S.SCHOOL,RTRIM(P.PHOTO) PHOTO 
FROM STUDENTS S LEFT JOIN PERSONAL P ON P.STUDENTNO=S.STUDENTNO 
LEFT JOIN CLASSES C ON S.CLASSNO=C.CLASSNO WHERE RTRIM(S.STUDENTNO) = :STUDENTNO