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

Date: 2015-11-27 16:00:39
*/


-- ----------------------------
-- Table structure for cwebs_exam_status
-- 考试状态
-- ----------------------------
CREATE TABLE [dbo].[cwebs_exam_status] (
[code] varchar(2) NOT NULL ,
[name] varchar(32) NULL 
)


GO

-- ----------------------------
-- Indexes structure for table cwebs_exam_status
-- ----------------------------

-- ----------------------------
-- Primary Key structure for table cwebs_exam_status
-- ----------------------------
ALTER TABLE [dbo].[cwebs_exam_status] ADD PRIMARY KEY ([code])
GO
