
-- -----------------------------
-- view_scores_finals_fail 期末成绩不合格学生列表
-- -----------------------------
SELECT * from cwebs_scores cs
WHERE  ( -- 判断不及格
    (cs.finals_exam_status <> 'Z')  -- 考试状态异常则认为需要参加补考
	or (ISNUMERIC(cs.finals_score) = 1 and cast(cs.finals_score as numeric(5,2))  < 60) -- 分数为数字 且小于60分
	or (ISNUMERIC(cs.finals_score) = 0 and cs.finals_score not in ('优秀','良好','中等','及格') ))

-- -----------------------------
-- view_scores_general_fail 总评成绩不合格学生列表
-- -----------------------------
	SELECT * FROM cwebs_scores cs WHERE
-- 考试状态异常则认为需要参加补考
( cs.general_status <> 'Z')
	-- 分数为数字 且小于60分
	or (ISNUMERIC(cs.general_score) = 1 and cast(cs.general_score as numeric(5,2))  < 60)
	-- 分数是文字 不等于'优秀','良好','中等','及格'的都属于不及格
	or (ISNUMERIC(cs.general_score) = 0 and cs.general_score not in ('优秀','良好','中等','及格') )

-- -----------------------------
-- view_scores_general_pass 总评通过学生列表
-- -----------------------------
	SELECT * from cwebs_scores cs
WHERE  (
    (cs.finals_exam_status = 'Z')  -- 考试状态必须属于正常
	and
(
	(ISNUMERIC(cs.finals_score) = 1 and cast(cs.finals_score as numeric(5,2))  >= 60)  -- 分数为数字时候必须大于等于60
	or (ISNUMERIC(cs.finals_score) = 0 and cs.finals_score in ('优秀','良好','中等','及格') )) -- 为中文时必须在该范围内
)

-- -----------------------------
-- view_scores_midterm_fail 期中成绩不合格学生列表
-- -----------------------------
SELECT * from cwebs_scores cs
WHERE  ( -- 判断不及格
    (cs.midterm_exam_status <> 'Z')  -- 考试状态异常则认为需要参加补考
	or (ISNUMERIC(cs.midterm_score) = 1 and cast(cs.midterm_score as numeric(5,2))  < 60) -- 分数为数字 且小于60分
	or (ISNUMERIC(cs.midterm_score) = 0 and cs.midterm_score not in ('优秀','良好','中等','及格') ))

-- -----------------------------
-- view_scores_resit_fail 补考不通过学生列表
-- -----------------------------
	SELECT * from cwebs_scores cs
WHERE  ( -- 判断不及格
    (cs.general_status <> 'Z')  -- 考试状态异常则认为需要参加补考
	or (ISNUMERIC(cs.general_score) = 1 and cast(cs.general_score as numeric(5,2))  < 60) -- 分数为数字 且小于60分
	or (ISNUMERIC(cs.general_score) = 0 and cs.general_score not in ('优秀','良好','中等','及格') ))
and ( -- 判断不及格
    (cs.resit_exam_status <> 'Z')  -- 考试状态异常则认为需要参加补考
	or (ISNUMERIC(cs.resit_score) = 1 and cast(cs.resit_score as numeric(5,2)) < 60) -- 分数为数字 且小于60分
	or (ISNUMERIC(cs.resit_score) = 0 and cs.resit_score not in ('优秀','良好','中等','及格') ))


-- -----------------------------
-- view_classcourse_attendants 班级中选修某一课程的学生的数目
-- -----------------------------
	SELECT
	R32.[YEAR] as [year],
	R32.TERM as term,
	R32.COURSENO as courseno,
	R32.[GROUP] as [group],
	STUDENTS.CLASSNO as classno,
	COUNT(R32.STUDENTNO) as attendents
	FROM R32
	INNER JOIN STUDENTS on STUDENTS.STUDENTNO = R32.STUDENTNO
	GROUP BY R32.[YEAR],R32.TERM,R32.COURSENO,R32.[GROUP],STUDENTS.CLASSNO