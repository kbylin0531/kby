insert into TestPlan(year,term,FLAG,DATE,COURSENO,ATTENDENTS,R10,rem,examType)
select :YONE,:TONE,TC.FLAG,TB.TESTTIME,SP.COURSENO+SP.[GROUP],SP.ATTENDENTS,SP.RECNO,'',:examType
from TESTCOURSE TC
inner join TESTBATCH TB ON TC.flag=TB.FLAG
inner join SCHEDULEPLAN SP ON TC.COURSENO=SP.COURSENO+SP.[group] AND SP.YEAR=:YTWO AND SP.TERM=:TTWO
