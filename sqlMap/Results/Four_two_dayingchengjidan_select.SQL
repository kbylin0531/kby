select * from(select row_number() over(order by scores.courseno+scores.[group]) as row,scores.courseno+scores.[group] as kh,
courses.coursename kcmc,
schools.name as kkxy, count(*) as cjrs from scores  inner join courses on courses.courseno=scores.courseno
inner join schools on schools.school=courses.school
where year=:year and term=:term and courses.school like :school and [group] not in ('BY' ,'ZX')
and scores.courseno+scores.[group] like :courseno
group by scores.courseno+scores.[group],courses.coursename,schools.name

) as b where b.row between :start and :end