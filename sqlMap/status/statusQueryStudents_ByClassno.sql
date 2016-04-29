SELECT
STUDENTS.STUDENTNO,
STUDENTS.NAME,
SEXCODE.NAME AS SEX,
STUDENTS.ENTERDATE,
STUDENTS.YEARS,
STUDENTS.CLASSNO,
STUDENTS.TAKEN,
STUDENTS.PASSED,
STUDENTS.POINTS,
STUDENTS.REG,
STUDENTS.WARN,
STUDENTS.STATUS,
STUDENTS.CONTACT,
STUDENTS.GRADE,
STUDENTS.SCHOOL,
CLASSES.CLASSNAME,
SCHOOLS.NAME AS SCHOOLNAME,
STATUSOPTIONS.VALUE AS STATUSVALUE
FROM STUDENTS
LEFT OUTER JOIN CLASSES ON STUDENTS.CLASSNO=CLASSES.CLASSNO
LEFT OUTER JOIN SCHOOLS ON STUDENTS.SCHOOL=SCHOOLS.SCHOOL
LEFT OUTER JOIN STATUSOPTIONS ON STUDENTS.STATUS=STATUSOPTIONS.NAME
LEFT OUTER JOIN SEXCODE ON STUDENTS.SEX=SEXCODE.CODE
where RTRIM(STUDENTS.StudentNo) LIKE :STUDENTNO
and STUDENTS.Name like :NAME
and STUDENTS.ClassNo like :CLASSNO
and STUDENTS.School like :SCHOOL
and STUDENTS.Status like :STATUS
ORDER BY CLASSNO,STUDENTNO

 
