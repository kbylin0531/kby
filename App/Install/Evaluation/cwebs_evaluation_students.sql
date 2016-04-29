/*
Navicat SQL Server Data Transfer

Source Server         : mssql2012
Source Server Version : 110000
Source Host           : localhost:1433
Source Database       : yzzj_jwgl
Source Schema         : dbo

Target Server Type    : SQL Server
Target Server Version : 110000
File Encoding         : 65001

Date: 2015-11-26 13:26:36
*/


-- ----------------------------
-- Table structure for cwebs_evaluation_students
-- 学生评教记录
-- ----------------------------
CREATE TABLE [dbo].[cwebs_evaluation_students] (
[year] smallint NULL ,   
[term] tinyint NOT NULL ,
[courseno] varchar(15) NOT NULL ,  
[group] varchar(2) NOT NULL ,
[studentno] varchar(15) NOT NULL ,
[teacherno] varchar(15) NOT NULL ,
[scores_detail] varchar(255) NOT NULL DEFAULT '' ,   -- 分数详细，表示方式为"很好,好,很好,很好,很好,很好"，方便查看
[scores_general] tinyint NOT NULL DEFAULT ((0)) ,  -- 学生考评计算得出的该教师的总分
[remark] varchar(255) NOT NULL DEFAULT '' ,    -- 该学生对该教室的评语
[input_date] datetime NULL ,      -- 学生评论日期
[recno] int NOT NULL IDENTITY(1,1)  -- 自增主键
)


GO

-- ----------------------------
-- Indexes structure for table cwebs_evaluation_students
-- ----------------------------

-- ----------------------------
-- Primary Key structure for table cwebs_evaluation_students
-- ----------------------------
ALTER TABLE [dbo].[cwebs_evaluation_students] ADD PRIMARY KEY ([recno])
GO

-- 索引
CREATE INDEX [index] ON [dbo].[cwebs_evaluation_students]
([year] ASC, [term] ASC, [courseno] ASC, [group] ASC, [studentno] ASC, [teacherno] ASC) 
GO

