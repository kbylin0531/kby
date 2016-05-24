select count(*) [count] from bookapply b 
left join courses c on b.courseno=c.courseno  
left join courseapproaches c2 on c2.name=b.approaches 
left join classes c3 on c3.classno=b.classno 
left join schools s on b.school=s.school 
where b.[year] = :year and b.term = :term and b.approaches like :approaches and b.courseno+b.[group] like :courseno and c.coursename like :coursename 
and b.school like :school and b.classno like :classno and b.status like :status 