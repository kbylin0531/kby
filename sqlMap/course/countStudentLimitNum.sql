select count(*) from R32 r
WHERE r.STUDENTNO=:STUDENTNO AND r.[YEAR]=:R_YEAR AND r.TERM = :R_TERM AND r.COURSENO IN (
	select v.COURSENO from VIEWSCHEDULETABLE v WHERE v.[YEAR]=:V_YEAR AND v.TERM=:V_TERM AND v.LIMITGROUPNO=:LIMITGROUPNO
)
