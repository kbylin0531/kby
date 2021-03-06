INSERT INTO TIMELIST
SELECT
dbo.getone(T.[YEAR]) as [YEAR],
dbo.getone(T.TERM) as TERM,
dbo.getone(R.STUDENTNO) as WHO,
dbo.getone('S') as [TYPE],
dbo.getone('') as PARA,
dbo.GROUP_OR(MON) as MON,
dbo.GROUP_OR(TUE) as TUE,
dbo.GROUP_OR(WES) as WES,
dbo.GROUP_OR(THU) as THU,
dbo.GROUP_OR(FRI) AS FRI,
dbo.GROUP_OR(SAT) AS SAT,
dbo.GROUP_OR(SUN) as SUN
FROM TIMELIST T
INNER JOIN R32 R
ON (T.[YEAR]=R.[YEAR] AND T.TERM=R.TERM AND T.WHO=R.COURSENO AND T.PARA=R.[GROUP])
WHERE T.TYPE='P' AND R.YEAR=:YEAR AND R.TERM=:TERM AND R.STUDENTNO=:STUDENTNO