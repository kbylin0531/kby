select 

i.ID,
i.YEAR,
i.TERM,
i.SCHOOL,
rtrim(m1.NAME) as SCHOOLNAME,
rtrim(i.TEACHERNO) as TEACHERNO,
rtrim(m2.NAME) as TEACHERNAME,
i.REWARDS_NAME_ID,
m3.NAME as REWARDSNAME,
m3.SORT as REWARDSSORT,
m3.GRADE as REWARDSGRADE,
m3.SCORE as REWARDSSCORE,
i.REWARDS_REMARK,
i.STATUS

from rewards i
left outer join SCHOOLS m1 ON m1.SCHOOL=i.SCHOOL
left outer join TEACHERS m2 ON m2.TEACHERNO=i.TEACHERNO
left outer join rewards_name m3 ON m3.ID=i.REWARDS_NAME_ID

where i.ADD_USER = :Quser
and i.YEAR like :Qyear
and i.TERM like :Qterm
and i.SCHOOL like :Qschool
and i.TEACHERNO like :Qteacherno
and m2.NAME like :Qteachername
and m3.SORT like :Qrewardssort
and m3.GRADE like :Qrewardsgrade
and i.STATUS like :Qstatus


ORDER BY i.ID
