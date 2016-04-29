-- 需求：成绩输入的分数类型（二级制、五级制）改为在开课计划中设置
-- 默认使用百分制
-- 百分制 - 'ten'    五级制 - 'five'  二级制 - 'two'
ALTER TABLE [dbo].[COURSEPLAN]
ADD [score_type] varchar(4) NOT NULL DEFAULT 'ten'
GO

