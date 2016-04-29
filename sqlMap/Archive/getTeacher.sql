SELECT CONVERT(varchar(10), T.Birth, 23) Birth,T.Degree,rtrim(T.Department) Department,rtrim(T.DSchool) DSchool,T.EduLevel,rtrim(T.Email) Email,
rtrim(T.ESchool) ESchool,CONVERT(varchar(10), T.HDate, 23) HDate,rtrim(T.HeadShip) HeadShip,rtrim(T.ID) ID,rtrim(T.NAME) NAME,T.Nationality,T.Party,
CONVERT(varchar(10), T.PDate, 23) PDate,T.Profession,rtrim(T.PSubject) PSubject,T.SEX,rtrim(T.MOBILE_LONG) MOBILE_LONG,rtrim(T.MOBILE_SHORT) MOBILE_SHORT,rtrim(T.Tel) Tel,rtrim(S.NAME) SCHOOLNAME,rtrim(S1.NAME) TGROUPNAME,rtrim(T.TEACHERNO) TEACHERNO,T.SCHOOL,T.TGROUP 

FROM TEACHERS T 
LEFT JOIN SCHOOLS S ON T.SCHOOL=S.SCHOOL 
LEFT JOIN TGROUPS S1 ON T.TGROUP=S1.TGROUP 
WHERE T.TEACHERNO=:TEACHERNO