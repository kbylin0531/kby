select Count(*) As ROWS
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