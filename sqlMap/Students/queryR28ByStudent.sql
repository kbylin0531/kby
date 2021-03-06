SELECT R28.STUDENTNO AS STUDENTNO,
R28.PROGRAMNO AS PROGRAMNO,
PROGRAMS.PROGNAME AS PROGRAMNAME,
PROGRAMS.REM AS REM,
PROGRAMS.TYPE AS TYPE,
PROGRAMTYPE.VALUE AS PROGRAMTYPEVALUE,
PROGRAMS.SCHOOL AS SCHOOL,
SCHOOLS.NAME AS SCHOOLNAME
FROM R28 JOIN PROGRAMS
ON R28.PROGRAMNO=PROGRAMS.PROGRAMNO
JOIN SCHOOLS ON PROGRAMS.SCHOOL=SCHOOLS.SCHOOL
LEFT OUTER JOIN PROGRAMTYPE ON PROGRAMS.TYPE=PROGRAMTYPE.NAME
WHERE R28.STUDENTNO=:STUDENTNO
ORDER BY PROGRAMNO