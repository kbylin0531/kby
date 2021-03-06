select count(*) as ROWS from(SELECT  row_number() over(order by COURSETYPEOPTIONS.VALUE) as row,COURSETYPEOPTIONS.VALUE  kclx, SUM(SCHEDULEPLAN.ATTENDENTS) AS xkzrs
FROM COURSES INNER JOIN
      SCHEDULEPLAN ON
      COURSES.COURSENO = SCHEDULEPLAN.COURSENO INNER JOIN
      COURSETYPEOPTIONS ON
      COURSES.TYPE = COURSETYPEOPTIONS.NAME
WHERE (SCHEDULEPLAN.YEAR = :YEAR) AND (SCHEDULEPLAN.TERM =:TERM)
GROUP BY COURSETYPEOPTIONS.VALUE
) as b