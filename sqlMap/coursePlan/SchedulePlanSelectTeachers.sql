SELECT TEACHERPLAN.RECNO,
RTRIM(TEACHERPLAN.TEACHERNO) AS TEACHERNO,
TEACHERS.NAME as TEACHERSNAME,
POSITIONS.VALUE as POSITIONSNAME,
TEACHERPLAN.HOURS,
UNITOPTIONS.NAME as UNITOPTIONSNAME,
UNITOPTIONS.CODE as UNIT,
RTRIM(TASKOPTIONS.NAME) as TASKOPTIONSNAME,
TASKOPTIONS.CODE as TASK,
TEACHERPLAN.REM,
TEACHERPLAN.AMOUNT,
ZO.VALUE as ZOVALUE,
SCHOOLS.NAME as SCHOOLNAME,
TEACHERPLAN.TOSCHEDULE
FROM TEACHERPLAN
INNER JOIN TEACHERS ON TEACHERPLAN.TEACHERNO=TEACHERS.TEACHERNO
left JOIN SCHOOLS ON TEACHERS.SCHOOL=SCHOOLS.SCHOOL
left JOIN UNITOPTIONS ON TEACHERPLAN.UNIT=UNITOPTIONS.CODE
left JOIN TASKOPTIONS ON TEACHERPLAN.TASK=TASKOPTIONS.CODE
left JOIN ZO ON TEACHERPLAN.TOSCHEDULE=CAST(ZO.NAME AS bit)
LEFT JOIN POSITIONS ON TEACHERS.POSITION=POSITIONS.NAME
WHERE TEACHERPLAN.MAP=:RECNO
AND TEACHERPLAN.TEACHERNO=TEACHERS.TEACHERNO
AND TEACHERS.SCHOOL=SCHOOLS.SCHOOL
AND TEACHERPLAN.UNIT=UNITOPTIONS.CODE
AND TEACHERPLAN.TASK=TASKOPTIONS.CODE
AND TEACHERPLAN.TOSCHEDULE=CAST(ZO.NAME AS bit)