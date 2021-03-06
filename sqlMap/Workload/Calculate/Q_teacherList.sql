SELECT W.ID CALCID,TP.TEACHERNO,T.NAME,W.REPEATCOEFF,W.TOTAL,
CASE WHEN (SELECT COUNT(*) FROM TEACHERPLAN TP LEFT JOIN TEACHERS T ON T.TEACHERNO=TP.TEACHERNO WHERE TP.MAP=W.MAP AND T.NAME IS NOT NULL)=1 THEN W.WORKLOAD ELSE 0 END WORKLOAD
FROM TEACHERPLAN TP 
LEFT JOIN TEACHERS T ON T.TEACHERNO=TP.TEACHERNO 
LEFT JOIN WORKLOADCALC W ON W.MAP=TP.MAP
WHERE T.NAME IS NOT NULL AND W.ID=:ID