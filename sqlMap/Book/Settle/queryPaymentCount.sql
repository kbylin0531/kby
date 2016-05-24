select count(*) from (
select c.classname,stu.studentno,stu.name,b2.isbn,b2.bookname,b.price,b.dis_price,b.dis_rate,
b.[year],b.term,stu.school,stu.classno from bookpayment b 
left join bookapply a on b.book_id=a.apply_id 
left join book b2 on b2.book_id=a.book_id 
left join students stu on stu.studentno=b.[no] 
left join classes c on c.classno=stu.classno 
where b.issueType='0' and b.type='0' 
union all 
select c.classname,stu.studentno,stu.name,b2.isbn,b2.bookname,b.price,b.dis_price,b.dis_rate,
b.[year],b.term,stu.school,stu.classno from bookpayment b 
left join book b2 on b2.book_id=b.book_id 
left join students stu on stu.studentno=b.[no] 
left join classes c on c.classno=stu.classno 
where b.issueType='1') bs 
where [year]=:year and term=:term and isbn like :isbn and bookname like :bookname and school like :school 
and classno like :classno