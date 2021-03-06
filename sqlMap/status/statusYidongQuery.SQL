select * from (SELECT row_number() over(order by REGISTRIES.STUDENTNO) as row,REGISTRIES.STUDENTNO AS STUDENTNO,
REGISTRIES.FILENO AS FILENO,
REGISTRIES.INFOTYPE AS INFOTYPE,
INFOTYPE.NAME AS INFOTYPEVALUE,
CONVERT(varchar(10),REGISTRIES.DATE,20)  AS FILEDATE,
STUDENTS.NAME STUDENTNAME,
STUDENTS.CLASSNO AS CLASSNO,
STUDENTS.SCHOOL AS SCHOOL,
SCHOOLS.NAME AS SCHOOLNAME,
CLASSES.CLASSNAME AS CLASSNAME,
'' AS FILEVALUE,
'' AS STATUSLINK,
REGISTRIES.RECNO AS RECNO
FROM REGISTRIES
LEFT OUTER JOIN INFOTYPE ON REGISTRIES.INFOTYPE=INFOTYPE.CODE
LEFT OUTER JOIN STUDENTS ON REGISTRIES.STUDENTNO=STUDENTS.STUDENTNO
LEFT OUTER JOIN CLASSES ON STUDENTS.CLASSNO=CLASSES.CLASSNO
LEFT OUTER JOIN SCHOOLS ON STUDENTS.SCHOOL=SCHOOLS.SCHOOL
WHERE (REGISTRIES.STUDENTNO LIKE :STUDENTNO)
AND (REGISTRIES.FILENO LIKE :FILENO OR REGISTRIES.FILENO IS NULL)
AND (REGISTRIES.INFOTYPE LIKE :INFOTYPE OR REGISTRIES.INFOTYPE IS NULL)
)as b where b.row between :start and :end