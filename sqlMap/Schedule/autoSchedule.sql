SELECT scheduleplan.YEAR,
       scheduleplan.TERM,
       scheduleplan.COURSENO,
       scheduleplan.[GROUP],
       scheduleplan.ESTIMATE,
       scheduleplan.ATTENDENTS,
       scheduleplan.ROOMTYPE,
       scheduleplan.LHOURS,
       scheduleplan.EHOURS,
       scheduleplan.WEEKS,
       scheduleplan.RECNO AS SCHEDULEPLANRECNO,
       teacherplan.TEACHERNO,
       teacherplan.HOURS,
       teacherplan.UNIT,
       teacherplan.REM,
       scheduleplan.EMPROOM,
       scheduleplan.TIME,
       scheduleplan.SCHEDULED,
       scheduleplan.EBATCH,
       scheduleplan.DAYS,
       scheduleplan.AREA,
       teacherplan.TASK,
       teacherplan.AMOUNT,
       teacherplan.TOSCHEDULE,
       teacherplan.RECNO,
       courses.TYPE
FROM SCHEDULEPLAN
LEFT OUTER JOIN TEACHERPLAN ON SCHEDULEPLAN.RECNO=TEACHERPLAN.MAP
JOIN COURSES ON SCHEDULEPLAN.COURSENO=COURSES.COURSENO
WHERE scheduleplan.YEAR=:YEAR
  AND scheduleplan.TERM=:TERM
  AND teacherplan.TEACHERNO IS NOT NULL
  AND teacherplan.TEACHERNO<>''
  AND teacherplan.TEACHERNO<>'000000'
ORDER BY scheduleplan.COURSENO,
         scheduleplan.[GROUP]