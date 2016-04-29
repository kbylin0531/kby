select
R12.CourseNo As CourseNo,
Courses.CourseName As CourseName ,
R12.CREDITS As Credits,
Courses.Hours As Hours,
R12.PROGRAMNO as PROGRAMNO,
Courses.School As School,
Schools.Name As SchoolName,
EXAMOPTIONS.VALUE as ExamType,
R12.ExamType As ExamTypeCode,
COURSEAPPROACHES.VALUE as CourseType,
R12.CourseType As CourseTypeCode,
R12.Year as Year,
R12.Term as Term,
--默认选择学部统考
--TESTLEVEL.VALUE AS TESTVALUE,
(SELECT TESTLEVEL.[VALUE] from TESTLEVEL WHERE TESTLEVEL.NAME = 'S') AS TESTVALUE,
R12.Test As TestCode,
R12.Category As CategoryCode,
COURSETYPEOPTIONS.VALUE AS CATEGORYVALUE,
R12.Weeks As Weeks,
'0' AS UPDATED,
R12.LIMITGROUPNO,
R12.LIMITNUM,
R12.RECNO,
R12.LIMITCREDIT
from R12
JOIN Courses ON R12.CourseNo=Courses.CourseNo
JOIN Programs ON R12.ProgramNo=Programs.ProgramNo
JOIN Schools ON Courses.School=Schools.School
JOIN EXAMOPTIONS ON R12.EXAMTYPE=EXAMOPTIONS.NAME
JOIN COURSEAPPROACHES  ON R12.COURSETYPE=COURSEAPPROACHES.NAME
JOIN COURSETYPEOPTIONS ON R12.CATEGORY=COURSETYPEOPTIONS.NAME
Where R12.ProgramNo =:programno
ORDER BY year,term,Courseno






