SELECT COUNT(*)
FROM STUDENTS,PERSONAL
WHERE ((PERSONAL.YEARS<3)OR(PERSONAL.YEARS IS NULL))AND(ENTERDATE IS NOT NULL)
AND(ABS(MONTH(PERSONAL.DAYOFENROLL)-MONTH(GETDATE()))<=5)
AND(YEAR(PERSONAL.DAYOFENROLL)=YEAR(GETDATE()))
AND(STUDENTS.STUDENTNO=PERSONAL.STUDENTNO)