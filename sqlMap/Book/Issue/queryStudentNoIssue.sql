select b.payment_id,b.isbn,b.bookname,b.studentno,b.name,b.classname,b.classno,p.[value],sch.name schoolname from studentbook b
left join students s on s.studentno=b.studentno 
left join schools sch on sch.school=s.school  
left join statusoptions p on p.name=s.status
where b.[year]=:year and b.term=:term and b.isbn like :isbn and b.bookname 
like :bookname and b.classno like :classno and b.school like :school and b.studentno like :studentno 