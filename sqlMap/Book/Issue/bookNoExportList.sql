select c.classname,b2.isbn,b2.bookname,b2.author,p2.name press,count(*) [count],b.price,b.dis_rate,b.dis_price,
(b.price * count(*)) m_price,(b.dis_price * count(*)) s_price from bookpayment b 
left join students s on s.studentno = b.no 
left join classes c on c.classno=s.classno 
left join statusoptions p on p.name=s.status 
left join book b2 on b.book_id=b2.book_id 
left join bookpress p2 on b2.press=p2.id 
where b.[year]=:year and b.term=:term and b2.isbn like :isbn and b2.bookname like :bookname and s.school like :school 
and c.classno like :classno and b.issueType='1' 
group by c.classname,b2.isbn,b2.bookname,b2.author,p2.name,b.price,b.dis_rate,b.dis_price 