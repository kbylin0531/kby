-- --------------------------------
-- 教师班级评分
-- view_evaluation_teacher
-- 某学年某学期某教师在某班级的评分 （ 教过几门课程就被平均几次）
-- --------------------------------

SELECT DISTINCT
cetc.[year],
cetc.term,
cetc.teacherno,
cetc.classno,
ISNULL(temp.score_avg, 0) as score_avg
 from view_evaluation_teacher_courses cetc
LEFT OUTER JOIN (
	SELECT
	[year],
	term,
	teacherno,
	SUM(score_avg)/COUNT(DISTINCT courseno+[group]) as score_avg
	from view_evaluation_teacher_courses WHERE score_avg != 0 -- 判断是否已经输入，分数只要输入过就不会是0分
	GROUP BY [year],term,teacherno
) temp  on temp.[year] = cetc.[year] and temp.term = cetc.term and temp.teacherno = cetc.teacherno



-- ---------------------------
-- 教师课程评价
-- view_evaluation_teacher_courses
-- 某学年学期改班级给予某课程某上课老师的平均评分
-- 如果学生未评过将不计入
-- ---------------------------

SELECT DISTINCT
ces.[year],
ces.term,
ces.courseno,
ces.[group],
ces.teacherno,
RTRIM(STUDENTS.CLASSNO) as classno,
ISNULL(temp.score_avg, 0) as score_avg
FROM cwebs_evaluation_students ces
INNER JOIN STUDENTS on ces.studentno = STUDENTS.STUDENTNO
LEFT OUTER JOIN (
	SELECT
	ces.[year],
	ces.term,
	ces.courseno+ces.[group] as coursegroup,
	ces.teacherno,
	SUM(scores_general)/COUNT(DISTINCT ces.studentno) as score_avg
	FROM cwebs_evaluation_students ces
	WHERE ces.scores_detail != '' -- 判断是否输入过
	GROUP BY ces.[year],ces.term,ces.courseno,ces.[group],ces.teacherno
) temp on temp.[year] = ces.[year] and temp.term = ces.term
 and temp.coursegroup = ces.courseno+ces.[group]
 and temp.teacherno = ces.teacherno