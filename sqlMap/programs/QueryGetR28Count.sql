select COUNT(*) AS ROWS
from students JOIN R28 ON students.StudentNo=R28.StudentNo
JOIN SCHOOLS ON STUDENTS.SCHOOL=SCHOOLS.SCHOOL
JOIN CLASSES ON STUDENTS.CLASSNO=CLASSES.CLASSNO
where
R28.ProgramNo=:programno
 
 
 
 
