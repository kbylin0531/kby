select count(b.row) from(SELECT row_number() over(order by :ORDER2)as row,* FROM VIEWROOMLEASE
WHERE YEAR= :YEAR
AND TERM= :TERM
AND SCHOOLCODE LIKE :SCHOOL
AND ROOMNO LIKE :ROOMNO
AND (APPROVED= :APPROVED OR APPROVED= :NOTAPPROVED)
)as b