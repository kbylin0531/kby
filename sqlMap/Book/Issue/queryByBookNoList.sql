select b.payment_id,b.no,s.name,c.classname,c.classno,p.value,b2.isbn,b2.bookname from bookpayment b 
left join students s on s.studentno = b.no 
left join classes c on c.classno=s.classno 
left join statusoptions p on p.name=s.status 
left join book b2 on b.book_id=b2.book_id 
where b.[year]=:year and b.term=:term and b2.isbn like :isbn and b2.bookname like :bookname and s.school like :school 
and c.classno like :classno and b.issueType=:issueType