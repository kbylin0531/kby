SELECT 
  COUNT(*) as c
FROM dbo.COURSEPLAN Dbo_courseplan
left join dbo.COURSEAPPROACHES Dbo_courseapproaches on Dbo_courseplan.COURSETYPE = Dbo_courseapproaches.NAME
left join dbo.COURSETYPEOPTIONS m1 on Dbo_courseplan.course_type_options = m1.NAME
left join dbo.EXAMOPTIONS Dbo_examoptions on Dbo_courseplan.EXAMTYPE = Dbo_examoptions.NAME
left join dbo.SCHOOLS Dbo_schools on Dbo_courseplan.SCHOOL = Dbo_schools.SCHOOL
left join dbo.COURSES Dbo_courses on Dbo_courseplan.COURSENO = Dbo_courses.COURSENO
left join dbo.CLASSES Dbo_classes on Dbo_courseplan.CLASSNO =Dbo_classes.CLASSNO
-- left JOIN dbo.COURSETYPEOPTIONS  coursetypeoption on  Dbo_courseplan.course_type_options=coursetypeoption.NAME


WHERE (Dbo_courseplan.YEAR=:YEAR)
   AND (Dbo_courseplan.TERM=:TERM)
   AND (Dbo_courseplan.COURSENO LIKE :COURSENO)
   AND (Dbo_courseplan.[GROUP] LIKE :GROUP)
   AND (Dbo_courseplan.SCHOOL LIKE :SCHOOL)
   AND (Dbo_courseplan.COURSETYPE LIKE :COURSETYPE)
--    AND (Dbo_courseplan.CATEGORY LIKE :CATEGORY)
   AND (Dbo_courseplan.CLASSNO LIKE :CLASSNO)
   AND (Dbo_courseplan.EXAMTYPE LIKE :EXAMTYPE)