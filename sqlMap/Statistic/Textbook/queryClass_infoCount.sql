SELECT COUNT(*) FROM CLASSES C INNER JOIN SCHOOLS S ON C.SCHOOL = S.SCHOOL 
	WHERE C.CLASSNO LIKE :CLASSNO AND C.CLASSNAME LIKE :CLASSNAME AND C.SCHOOL LIKE :SCHOOL