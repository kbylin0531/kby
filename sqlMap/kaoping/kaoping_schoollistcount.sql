SELECT COUNT(A.TEACHERNO) As ROWS
FROM 教学质量评估院校评估 AS A
INNER JOIN TEACHERS AS B ON B.TEACHERNO=A.TEACHERNO
INNER JOIN SCHOOLS AS C ON B.SCHOOL=C.SCHOOL
WHERE A.YEAR=:YEAR AND B.SCHOOL=:SCHOOLNO