select courses.CourseNo,
courses.CourseName,
courses.Credits,
courses.School,
COURSES.TGROUP,
jyz.NAME AS tgroupname,
Schools.Name As SchoolName,
COURSETYPEOPTIONS.VALUE AS COURSETYPE,
courses.REM
from courses
left JOIN TGROUPS jyz ON COURSES.TGROUP = jyz.TGROUP
JOIN Schools ON COURSES.SCHOOL=SCHOOLS.SCHOOL
JOIN COURSETYPEOPTIONS ON COURSES.TYPE=COURSETYPEOPTIONS.NAME
where
Courses.CourseNo like :COURSENO
and Courses.CourseName like :COURSENAME
and Courses.School like :SCHOOL
AND COURSETYPEOPTIONS.NAME LIKE :COURSETYPE
AND COURSES.TGROUP like :TGROUPNO
 
