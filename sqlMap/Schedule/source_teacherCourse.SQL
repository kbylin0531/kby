SELECT row_number() over(order by SCHEDULE.COURSENO) as row,SCHEDULE.COURSENO+SCHEDULE.[GROUP] AS COURSE,TIMESECTIONS.[VALUE] as jc,OEWOPTIONS.NAME as dsz,
SCHEDULE.DAY,SCHEDULE.TIME,SCHEDULE.OEW,SCHEDULE.WEEKS,OEWOPTIONS.TIMEBIT&TIMESECTIONS.TIMEBITS2 AS TIMES
FROM SCHEDULE JOIN TEACHERPLAN
ON SCHEDULE.MAP=TEACHERPLAN.RECNO
inner join TIMESECTIONS on TIMESECTIONS.NAME=SCHEDULE.TIME
inner join OEWOPTIONS on OEWOPTIONS.CODE=SCHEDULE.OEW
WHERE SCHEDULE.YEAR=:year
AND SCHEDULE.TERM=:term
AND TEACHERPLAN.TEACHERNO=:teacherno