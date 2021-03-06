select count(*) as ROWS from(SELECT row_number() over(order by dbo.getOne(RTRIM(VIEWSCHEDULE.COURSENOGROUP)))as row,dbo.getOne(RTRIM(VIEWSCHEDULE.COURSENOGROUP)) AS kh,
    dbo.getOne(RTRIM(VIEWSCHEDULE.COURSENAME)) AS km,
    dbo.getOne(VIEWSCHEDULE.CREDITS) AS xf,
    dbo.getOne(VIEWSCHEDULE.WEEKHOURS) AS zxs,
    dbo.getOne(VIEWSCHEDULE.WEEKEXPEHOURS) AS zsy,
    dbo.getOne(RTRIM(VIEWSCHEDULE.COURSETYPE)) AS xk,
    dbo.getOne(RTRIM(VIEWSCHEDULE.EXAMTYPE)) AS kh2,
    dbo.getOne(SCHEDULEPLAN.ATTENDENTS) AS yxrs,
    dbo.getOne(RTRIM(VIEWSCHEDULE.COURSETYPENAME)) AS kclb,
    dbo.getOne(RTRIM(VIEWSCHEDULE.SCHOOLNAME)) AS kkxy,
    dbo.getOne(RTRIM(VIEWSCHEDULE.CLASSNONAME)) AS bj,
    dbo.getOne(RTRIM(VIEWSCHEDULE.TEACHERNONAME)) AS js,
    dbo.getOne(RTRIM(VIEWSCHEDULE.REM)) AS bz,
    dbo.GROUP_CONCAT_MERGE(RTRIM(VIEWSCHEDULE.DAYNTIME)+'座位数:'+CAST(VIEWSCHEDULE.SEATS AS char),',') AS kcap
FROM VIEWSCHEDULE LEFT OUTER JOIN SCHEDULEPLAN ON VIEWSCHEDULE.RECNO=SCHEDULEPLAN.RECNO
WHERE VIEWSCHEDULE.YEAR= :YEAR
AND VIEWSCHEDULE.TERM= :TERM
AND VIEWSCHEDULE.COURSENOGROUP LIKE :COURSE
AND VIEWSCHEDULE.COURSENAME LIKE :CNAME
AND VIEWSCHEDULE.TYPE LIKE :CTYPE
AND VIEWSCHEDULE.SCHOOL LIKE :SCHOOL
AND VIEWSCHEDULE.CLASSNO LIKE :CLASSNO
AND CAST(SCHEDULEPLAN.SEATSLOCK AS CHAR) LIKE :SEATSLOCK
group by VIEWSCHEDULE.COURSENOGROUP
) as b