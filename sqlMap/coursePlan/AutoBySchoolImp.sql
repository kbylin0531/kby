INSERT INTO COURSEPLAN ([YEAR],TERM,COURSENO,CLASSNO,SCHOOL,WEEKS,[GROUP],COURSETYPE,EXAMTYPE,ATTENDENTS,LIMITGROUPNO,LIMITNUM,CREDITS,CATEGORY,LIMITRESULT)
SELECT DISTINCT
  :COL_YEAR as [YEAR],
  :COL_TERM as TERM,
  Dbo_r12.COURSENO as COURSENO,
  Dbo_r16.CLASSNO as CLASSNO,
  Dbo_courses.SCHOOL as SCHOOL,
  :COL_WEEKS as WEEKS,
  dbo.fn_10to36(DENSE_RANK() over(PARTITION BY Dbo_r12.COURSENO order by Dbo_r16.CLASSNO,Dbo_r16.PROGRAMNO)-1) as [GROUP],
  Dbo_r12.COURSETYPE as COURSETYPE,
  Dbo_r12.EXAMTYPE as EXAMTYPE,
  Dbo_classes.STUDENTS as ATTENDENTS,
  Dbo_r12.LIMITGROUPNO,
  Dbo_r12.LIMITNUM,
  Dbo_r12.CREDITS,
  Dbo_Programs.TYPE,
  Dbo_r12.LIMITCREDIT
FROM dbo.PROGRAMS Dbo_Programs ,dbo.R16 Dbo_r16, dbo.R12 Dbo_r12, dbo.CLASSES Dbo_classes, dbo.COURSES Dbo_courses
WHERE  (Dbo_r16.PROGRAMNO = Dbo_r12.PROGRAMNO)
AND (Dbo_Programs.ProgramNo=Dbo_r12.ProgramNo)
AND  (Dbo_r12.COURSENO = Dbo_courses.COURSENO)
AND  (Dbo_r16.CLASSNO = Dbo_classes.CLASSNO)
AND  (Dbo_r12.YEAR = Dbo_Classes.Grade and Dbo_r12.TERM=:TERM)
ORDER BY Dbo_r12.COURSENO


