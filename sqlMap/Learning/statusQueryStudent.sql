SELECT

FROM STUDENTS INNER JOIN PERSONAL ON PERSONAL.STUDENTNO=STUDENTS.STUDENTNO 
LEFT OUTER JOIN CLASSES ON STUDENTS.CLASSNO=CLASSES.CLASSNO
LEFT OUTER JOIN SCHOOLS ON STUDENTS.SCHOOL=SCHOOLS.SCHOOL
LEFT OUTER JOIN STATUSOPTIONS ON STUDENTS.STATUS=STATUSOPTIONS.NAME
LEFT OUTER JOIN SEXCODE ON STUDENTS.SEX=SEXCODE.CODE
where 
RTRIM(STUDENTS.StudentNo) =:STUDENTNO

 
