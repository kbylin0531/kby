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

Date: 2015-11-27 16:00:50
*/


-- ----------------------------
-- Table structure for cwebs_resit_students
-- 补考学生列表
-- ----------------------------
CREATE TABLE [dbo].[cwebs_resit_students] (
[studentno] varchar(16) NOT NULL ,
[coursegroup] varchar(16) NOT NULL ,
[year] smallint NULL ,
[term] tinyint NOT NULL ,
[recno] int NOT NULL IDENTITY(1,1) 
)


GO
DBCC CHECKIDENT(N'[dbo].[cwebs_resit_students]', RESEED, 429)
GO

-- ----------------------------
-- Indexes structure for table cwebs_resit_students
-- ----------------------------

-- ----------------------------
-- Primary Key structure for table cwebs_resit_students
-- ----------------------------
ALTER TABLE [dbo].[cwebs_resit_students] ADD PRIMARY KEY ([recno])
GO
