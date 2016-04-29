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

Date: 2015-11-27 16:01:03
*/


-- ----------------------------
-- Table structure for cwebs_score_percent
-- 考试成绩百分比
-- ----------------------------
DROP TABLE [dbo].[cwebs_score_percent]
GO
CREATE TABLE [dbo].[cwebs_score_percent] (
[coursegroup] varchar(16) NOT NULL ,
[normalscore] tinyint NULL ,
[midtermscore] tinyint NULL ,
[finalsscore] tinyint NULL 
)


GO

-- ----------------------------
-- Indexes structure for table cwebs_score_percent
-- ----------------------------

-- ----------------------------
-- Primary Key structure for table cwebs_score_percent
-- ----------------------------
ALTER TABLE [dbo].[cwebs_score_percent] ADD PRIMARY KEY ([coursegroup])
GO
