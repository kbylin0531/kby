select count(*) from bookapply b 
left join courses c on b.courseno=c.courseno  
left join courseapproaches c2 on c2.name=b.approaches 
left join schools s on b.school=s.school 
left join book b2 on b2.book_id=b.book_id 
where b.[year] = :year and b.term = :term and b.courseno+b.[group] like :courseno and c.coursename like :coursename 
and b.school like :school and b.classno like :classno and (b.status='1' or b.status='3') 