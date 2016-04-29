select Classes.PRI_CLASSNO As PRI_CLASSNO,
Classes.ClassNo As CLASSNO,
Classes.ClassName As CLASSNAME,
Classes.School As SCHOOL,
Classes.CHARGE_TEACHERNO,
Classes.Students As STUDENTS,
CONVERT(varchar(10),Classes.YEAR,20) As YEAR,
Classes.REMARK As REMARK,
rtrim(Schools.Name) As SCHOOLNAME,
rtrim(teachers.TEACHERNO) + ' / ' + rtrim(teachers.NAME) As CHARGE_TEACHERNAME,
MYCOUNT.COUNTS AS COUNTS,
'' AS CL
from Classes
left outer join Schools ON Schools.School=Classes.School
left outer join teachers ON teachers.TEACHERNO=Classes.CHARGE_TEACHERNO
left outer join
(
    select CLASSES.CLASSNO AS CLASSNO,Count(students.studentNo) As COUNTS
    from CLASSES
    LEFT OUTER JOIN STUDENTS ON CLASSES.classno=STUDENTS.classno
    JOIN schools ON CLASSES.school=schools.school
    GROUP BY CLASSES.CLASSNO
) AS MYCOUNT ON CLASSES.CLASSNO=MYCOUNT.CLASSNO
WHERE Classes.ClassNo like :CLASSNO
and Classes.ClassName like :CLASSNAME
and Classes.School like :SCHOOL
and CAST(YEAR(Classes.YEAR) AS CHAR) LIKE :YEAR
ORDER BY CLASSES.CLASSNO
