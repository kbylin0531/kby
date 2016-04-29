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

Date: 2015-11-30 10:22:40
*/


-- ----------------------------
-- Table structure for cwebs_graduation_audit
-- ----------------------------
CREATE TABLE [dbo].[cwebs_graduation_audit] (
[studentno] varchar(15) NOT NULL , -- 学号（主键）
[status] tinyint NOT NULL DEFAULT ((0)) , -- 审核状态，值见下main所述，另外前端还有值为3的情况表示未创建该记录
[auditime] datetime2(7) NOT NULL DEFAULT (getdate()) -- 审核日期
)


GO
IF ((SELECT COUNT(*) from fn_listextendedproperty('MS_Description', 
'SCHEMA', N'dbo', 
'TABLE', N'cwebs_graduation_audit', 
'COLUMN', N'status')) > 0) 
EXEC sp_updateextendedproperty @name = N'MS_Description', @value = N'0表示已经查看,1表示审核通过,2表示审核不通过'
, @level0type = 'SCHEMA', @level0name = N'dbo'
, @level1type = 'TABLE', @level1name = N'cwebs_graduation_audit'
, @level2type = 'COLUMN', @level2name = N'status'
ELSE
EXEC sp_addextendedproperty @name = N'MS_Description', @value = N'0表示已经查看,1表示审核通过,2表示审核不通过'
, @level0type = 'SCHEMA', @level0name = N'dbo'
, @level1type = 'TABLE', @level1name = N'cwebs_graduation_audit'
, @level2type = 'COLUMN', @level2name = N'status'
GO

-- ----------------------------
-- Indexes structure for table cwebs_graduation_audit
-- ----------------------------

-- ----------------------------
-- Primary Key structure for table cwebs_graduation_audit
-- ----------------------------
ALTER TABLE [dbo].[cwebs_graduation_audit] ADD PRIMARY KEY ([studentno])
GO
