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

Date: 2015-11-26 13:23:41
*/


-- ----------------------------
-- Table structure for cwebs_evaluation_items
-- 考评项
-- ----------------------------
CREATE TABLE [dbo].[cwebs_evaluation_items] (
[id] tinyint NOT NULL ,     -- 考评项ID号，从中取出时按照这个ID号排序
[description] varchar(255) NOT NULL ,  -- 考评项描述
[score] tinyint NOT NULL ,     -- 考评项分值
[active] tinyint NOT NULL DEFAULT ((0))   -- 表示该考评项是否被激活，只有激活的才能被投入使用（为保证考评项满分值为100）
)
GO

-- ----------------------------
-- Indexes structure for table cwebs_evaluation_items
-- ----------------------------

-- ----------------------------
-- Primary Key structure for table cwebs_evaluation_items
-- ----------------------------
ALTER TABLE [dbo].[cwebs_evaluation_items] ADD PRIMARY KEY ([id])
GO
