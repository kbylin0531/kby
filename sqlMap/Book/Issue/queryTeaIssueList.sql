select distinct s.name schoolname,t.teacherno,b.name,b2.isbn,b2.bookname,b.price,b.dis_rate,b.dis_price from bookpayment b left join teachers t on t.teacherno=b.[no] 
left join schools s on s.school=t.school 
left join bookapply a on a.apply_id=b.book_id 
left join book b2 on b2.book_id=a.book_id 
where b.type='1' and b.[year]=:year and b.term=:term and t.school like :school and t.teacherno like :teacherno and b.name like :name