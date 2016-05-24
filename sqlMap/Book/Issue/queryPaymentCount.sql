select count(b.no) from bookpayment b 
left join students s on s.studentno = b.no 
left join classes c on c.classno=s.classno 
left join statusoptions p on p.name=s.status 
where s.name like :name and b.no like :studentno and b.type='0' 