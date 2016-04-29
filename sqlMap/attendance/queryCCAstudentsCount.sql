SELECT COUNT(*) 

FROM [学生考勤表] S 
INNER JOIN STUDENTS ON S.STUDENTNO = STUDENTS.STUDENTNO 

left JOIN 学生考勤表请假理由OPTIONS S2 ON S.请假理由 = S2.CODE 
left JOIN TIMESECTORS ON TIMESECTORS.NAME=S.节次 

WHERE 
S.[COURSES_CLASS_ATTENDANCE_ID] = :COURSES_CLASS_ATTENDANCE_ID
AND S.studentno LIKE :studentno
AND STUDENTS.NAME LIKE :studentname
AND S.[请假理由] LIKE :breaktherule