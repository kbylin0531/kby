SELECT W.COURSENO,W.COURSENAME,W.TOTAL,W.WEEKS,W.ATTENDENTS,W.STANDARD,W.WORKTYPENAME,
W.CLASSCOEFF,W.CORRECTCOEFF,W.WORKLOAD,WA.WORKLOAD ALLOCWORKLOAD,T.NAME,WA.TEACHERNO FROM WORKLOADALLOC WA 
LEFT JOIN TEACHERS T ON T.TEACHERNO=WA.TEACHERNO 
LEFT JOIN V_WORKLOADCALC W ON W.ID=WA.CALCID 
WHERE WA.WORKLOAD > 0 AND W.WORKLOAD=W.ALLOCWORKLOAD AND W.[YEAR]=:YEAR AND W.TERM=:TERM AND W.COURSENO LIKE :COURSENO
AND W.COURSENAME LIKE :COURSENAME AND W.SCHOOL LIKE :SCHOOL AND W.WORKTYPE LIKE :TYPE
ORDER BY W.COURSENO 