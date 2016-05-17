-- 160503 修改用户表 USERS
-- char2varchar
update a set a.username = b.username,a.password=b.password,a.teacherno=b.teacherno,a.roles=b.roles
 from USERS a ,(
select rTRIM(username) as username,RTRIM(password) as password,
RTRIM(roles) as roles,RTRIM(teacherno) as teacherno from USERS
 ) b WHERE a.username = b.username


