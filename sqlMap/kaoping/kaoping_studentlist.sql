SELECT 教学质量评估详细.STUDENTNO,students.NAME, classes.CLASSNAME,COMPELETE,TOTAL,RECNO AS RECNOA,USED
FROM 教学质量评估详细 INNER JOIN students ON 教学质量评估详细.STUDENTNO=students.STUDENTNO
INNER JOIN classes ON classes.CLASSNO=students.CLASSNO
WHERE MAP=:MAP
ORDER BY classes.classname