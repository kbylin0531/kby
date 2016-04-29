SELECT COUNT(SCHEDULE.RECNO) AS ROWS
FROM SCHEDULE
left JOIN COURSEPLAN ON (SCHEDULE.COURSENO=COURSEPLAN.COURSENO AND SCHEDULE.[GROUP]=COURSEPLAN.[GROUP] AND SCHEDULE.YEAR=COURSEPLAN.YEAR AND SCHEDULE.TERM=COURSEPLAN.TERM)
left JOIN TEACHERPLAN ON(SCHEDULE.MAP=TEACHERPLAN.RECNO)
left JOIN COURSES ON (SCHEDULE.COURSENO=COURSES.COURSENO)
left JOIN CLASSES ON (COURSEPLAN.CLASSNO=CLASSES.CLASSNO)
left JOIN TEACHERS ON (TEACHERPLAN.TEACHERNO=TEACHERS.TEACHERNO)
left JOIN SCHOOLS ON (COURSES.SCHOOL=SCHOOLS.SCHOOL)
left JOIN TIMESECTORS ON (SCHEDULE.TIME=TIMESECTORS.NAME)
left JOIN OEWOPTIONS ON (SCHEDULE.OEW=OEWOPTIONS.CODE)
left JOIN ROOMOPTIONS ON (SCHEDULE.ROOMR=ROOMOPTIONS.NAME)
left JOIN TASKOPTIONS ON (SCHEDULE.LE=TASKOPTIONS.CODE)
left JOIN CLASSROOMS ON (SCHEDULE.ROOMNO=CLASSROOMS.ROOMNO)
WHERE SCHEDULE.YEAR=:YEAR
AND SCHEDULE.TERM=:TERM
AND SCHEDULE.COURSENO LIKE :COURSENO
AND SCHEDULE.[GROUP] LIKE :GROUP
AND COURSEPLAN.CLASSNO LIKE :CLASSNO
AND COURSEPLAN.COURSETYPE LIKE :APPROACHES
AND COURSEPLAN.EXAMTYPE LIKE :EXAMTYPE
AND SCHEDULE.ROOMR LIKE :ROOMR
AND SCHEDULE.UNIT LIKE :UNIT
AND SCHEDULE.DAY LIKE :DAY
AND SCHEDULE.TIME LIKE :TIME
AND SCHEDULE.ROOMNO LIKE :ROOMNO
AND CLASSES.CLASSNAME LIKE :CLASSNAME
AND TEACHERS.TEACHERNO LIKE :TEACHERNO
AND TEACHERS.NAME LIKE :TEACHERNAME
AND COURSES.COURSENAME LIKE :COURSENAME
AND COURSES.SCHOOL LIKE :SCHOOL
AND COURSES.TYPE2 LIKE :COURSETYPE

