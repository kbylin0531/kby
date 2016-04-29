SELECT REGDATA.YEAR,
REGDATA.TERM,
REGDATA.REGDATE,
REGDATA.REGCODE,
REGCODEOPTIONS.VALUE AS REGVALUE
FROM REGDATA
LEFT OUTER JOIN REGCODEOPTIONS
ON REGDATA.REGCODE=REGCODEOPTIONS.NAME
WHERE REGDATA.STUDENTNO=:STUDENTNO
