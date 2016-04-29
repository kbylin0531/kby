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

Date: 2015-11-27 16:01:11
*/


-- ----------------------------
-- Table structure for cwebs_scores
-- 成绩表
-- ----------------------------
DROP TABLE [dbo].[cwebs_scores]
GO
CREATE TABLE [dbo].[cwebs_scores] (
[courseno] varchar(16) NOT NULL ,
[group] char(2) NOT NULL ,
[year] smallint NOT NULL ,
[studentno] varchar(16) NOT NULL ,
[term] tinyint NOT NULL ,
[normal_score] varchar(10) NOT NULL DEFAULT '' ,
[midterm_score] varchar(10) NOT NULL DEFAULT '' ,
[finals_score] varchar(10) NOT NULL DEFAULT '' ,
[general_score] varchar(10) NOT NULL DEFAULT '' ,
[resit_score] varchar(10) NOT NULL DEFAULT '' ,
[retake_score] varchar(10) NOT NULL DEFAULT '' ,
[midterm_exam_date] datetime NULL ,
[midterm_input_date] datetime NULL ,
[finals_exam_date] datetime NULL ,
[finals_input_date] datetime NULL ,
[resit_exam_date] datetime NULL ,
[resit_input_date] datetime NULL ,
[midterm_lock] tinyint NULL DEFAULT ((0)) ,
[finals_lock] tinyint NULL DEFAULT ((1)) ,
[resit_lock] tinyint NULL DEFAULT ((1)) ,
[retake_lock] tinyint NULL DEFAULT ((1)) ,
[midterm_exam_status] char(1) NOT NULL DEFAULT ('Z') ,
[finals_exam_status] char(1) NOT NULL DEFAULT ('Z') ,
[resit_exam_status] char(1) NOT NULL DEFAULT ('Z') ,
[retake_exam_status] char(1) NOT NULL DEFAULT ('Z') ,
[status] tinyint NULL DEFAULT ((1)) ,
[recno] int NOT NULL IDENTITY(1,1) ,
[general_status] char(1) NOT NULL DEFAULT ('Z') ,
[point] decimal(18) NOT NULL DEFAULT ((0)) 
)


GO
DBCC CHECKIDENT(N'[dbo].[cwebs_scores]', RESEED, 21502)
GO

-- ----------------------------
-- Indexes structure for table cwebs_scores
-- ----------------------------

-- ----------------------------
-- Primary Key structure for table cwebs_scores
-- ----------------------------
ALTER TABLE [dbo].[cwebs_scores] ADD PRIMARY KEY ([recno])
GO
