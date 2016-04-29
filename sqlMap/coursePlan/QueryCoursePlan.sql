SELECT 
	Dbo_courseplan.RECNO,
	Dbo_courseplan.YEAR,
    Dbo_courseplan.TERM,
    Dbo_courseplan.COURSENO,
    Dbo_courseplan.[GROUP],
    Dbo_courses.COURSENAME,
    Dbo_classes.CLASSNO,
    Dbo_classes.CLASSNAME,
    Dbo_courseplan.CREDITS,
    Dbo_courses.HOURS,
    Dbo_courseplan.WEEKS,
    Dbo_courseapproaches.VALUE AS COURSETYPE,
    Dbo_courseplan.COURSETYPE AS TYPE,
--       Dbo_courseplan.course_type_options AS CATEGORY,
    m1.VALUE AS CATEGORYNAME,
    Dbo_examoptions.VALUE AS EXAMTYPE,
    Dbo_courseplan.EXAMTYPE AS EXAM,
    Dbo_courseplan.ATTENDENTS,
    Dbo_schools.NAME AS SCHOOLNAME,
    Dbo_courseplan.SCHOOL AS SCHOOL,
    Dbo_courseplan.REM AS REM,
    Dbo_classes.SCHOOL AS CLASSSCHOOL,
    Dbo_courseplan.LIMITGROUPNO,
     Dbo_courseplan.LIMITNUM,
--   coursetypeoption.[VALUE] as CATEGORYNAME,
  Dbo_courseplan.course_type_options as CTO

FROM dbo.COURSEPLAN Dbo_courseplan
left join dbo.COURSEAPPROACHES Dbo_courseapproaches on Dbo_courseplan.COURSETYPE = Dbo_courseapproaches.NAME
left join dbo.COURSETYPEOPTIONS m1 on Dbo_courseplan.course_type_options = m1.NAME
left join dbo.EXAMOPTIONS Dbo_examoptions on Dbo_courseplan.EXAMTYPE = Dbo_examoptions.NAME
left join dbo.SCHOOLS Dbo_schools on Dbo_courseplan.SCHOOL = Dbo_schools.SCHOOL
left join dbo.COURSES Dbo_courses on Dbo_courseplan.COURSENO = Dbo_courses.COURSENO
left join dbo.CLASSES Dbo_classes on Dbo_courseplan.CLASSNO =Dbo_classes.CLASSNO


WHERE (Dbo_courseplan.YEAR=:YEAR)
   AND (Dbo_courseplan.TERM=:TERM)
   AND (Dbo_courseplan.COURSENO LIKE :COURSENO)
   AND (Dbo_courseplan.[GROUP] LIKE :GROUP)
   AND (Dbo_courseplan.SCHOOL LIKE :SCHOOL)
   AND (Dbo_courseplan.COURSETYPE LIKE :COURSETYPE)
--    AND (Dbo_courseplan.CATEGORY LIKE :CATEGORY)
   AND (Dbo_courseplan.CLASSNO LIKE :CLASSNO)
   AND (Dbo_courseplan.EXAMTYPE LIKE :EXAMTYPE)
