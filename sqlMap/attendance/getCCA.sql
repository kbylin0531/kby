		SELECT
		
		S.ID,S.[YEAR],S.TERM,S.[WEEKS] WEEK,
		CONVERT(varchar(10), S.[SCHOOLTIME], 23) DATETIME,
		S.SECTORS,
		TIMESECTORS.[VALUE] TIMENO,
		S.COURSENO,COURSES.COURSENAME,(rtrim(S.COURSENO) + ' / ' + rtrim(COURSES.COURSENAME)) as COURSE,
		CLASSES.CLASSNO,CLASSES.CLASSNAME,(rtrim(CLASSES.CLASSNO) + ' / ' + rtrim(CLASSES.CLASSNAME)) as CLASS,
		SCHOOLS.NAME SCHOOLNAME
		
		FROM courses_class_attendance S
		INNER JOIN COURSES ON LEFT(S.COURSENO,7) = COURSES.COURSENO
		INNER JOIN CLASSES ON S.CLASSNO = CLASSES.CLASSNO
		left JOIN SCHOOLS ON SCHOOLS.SCHOOL=COURSES.SCHOOL
		left JOIN TIMESECTORS ON TIMESECTORS.NAME=S.SECTORS
		
		WHERE S.ID = :ID
