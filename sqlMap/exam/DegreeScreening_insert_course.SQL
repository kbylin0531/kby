insert into TestCourse(CourseNo,CourseName,Flag,Lock,classes,CourseNo2)
select S.COURSENO,dbo.getOne(C.COURSENAME),0,0,NULL,dbo.getOne(S.COURSENO) FROM SCORES AS S INNER JOIN COURSES as C ON S.COURSENO=C.COURSENO
WHERE S.YEAR=:YEAR AND S.TERM=:TERM AND EXISTS (
  select * from TestStudent AS t where T.STUDENTNO=S.STUDENTNO AND T.COURSENO=S.COURSENO
)
group by S.COURSENO