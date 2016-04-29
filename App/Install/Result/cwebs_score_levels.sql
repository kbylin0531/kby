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

Date: 2015-11-27 16:00:56
*/


-- ----------------------------
-- Table structure for cwebs_score_levels
-- 成绩等级表（优秀、良好。。。。）
-- ----------------------------
DROP TABLE [dbo].[cwebs_score_levels]
GO
CREATE TABLE [dbo].[cwebs_score_levels] (
[code] char(1) NOT NULL ,
[name] varchar(32) NOT NULL DEFAULT '' 
)


GO

-- ----------------------------
-- Indexes structure for table cwebs_score_levels
-- ----------------------------

-- ----------------------------
-- Primary Key structure for table cwebs_score_levels
-- ----------------------------
ALTER TABLE [dbo].[cwebs_score_levels] ADD PRIMARY KEY ([code])
GO
