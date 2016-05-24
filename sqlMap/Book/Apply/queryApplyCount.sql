select COUNT(*)
from bookapply b 
left join courses c on b.courseno=c.courseno  
left join courseapproaches c2 on c2.name=b.approaches 
left join classes c3 on c3.classno=b.classno 
where b.[year] = :YEAR and b.term = :TERM and b.approaches like :COURSETYPE and b.courseno+b.[group] like :COURSENO and c.coursename like :COURSENAME 
and b.classno like :CLASSNO and b.school like :SCHOOL and b.status like :STATUS