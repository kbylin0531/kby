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

Date: 2015-11-27 15:25:30
*/



-- ----------------------------
-- Table structure for cwebs_student_class_change
-- ----------------------------


CREATE TABLE [dbo].[cwebs_student_class_change] (
[studentno] varchar(15) NOT NULL ,
[origin_classno] varchar(15) NOT NULL ,
[origin_classname] varchar(64) NOT NULL ,
[classno] varchar(15) NOT NULL ,
[change_date] datetime NOT NULL DEFAULT (getdate())
)


GO

-- ----------------------------
-- Indexes structure for table cwebs_student_class_change
-- ----------------------------

-- ----------------------------
-- Primary Key structure for table cwebs_student_class_change
-- ----------------------------
ALTER TABLE [dbo].[cwebs_student_class_change] ADD PRIMARY KEY ([studentno])
GO


