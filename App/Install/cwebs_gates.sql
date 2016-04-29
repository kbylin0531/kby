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

Date: 2015-10-28 10:52:01
*/
-- ----------------------------
-- Table structure for cwebs_gates
-- 操作限制的大门，各个模块可以对之进行业务逻辑的设置
-- 类似系统表，设置项不能清空，否则会影响业务的进行
-- ----------------------------
DROP TABLE [dbo].[cwebs_gates]
GO
CREATE TABLE [dbo].[cwebs_gates] (
[id] int NOT NULL IDENTITY(1,1) , -- 每个模块对应也该固定的ID
[name] varchar(32) NOT NULL , -- 名称
[comment] varchar(255) NOT NULL DEFAULT '' , -- 说明
[status] tinyint NULL , --  开启状态，不同的模块可以自定义状态规则，如成绩输入模块当status为0时任何输入都不允许，即使在指定的时间内
[begin_time] datetime NULL , -- 开启时间
[end_time] datetime NULL , -- 结束时间
[year] smallint NULL , -- 学年
[term] tinyint NULL  -- 学期
)
GO

-- ----------------------------
-- Records of cwebs_gates
-- ----------------------------
SET IDENTITY_INSERT [dbo].[cwebs_gates] ON
GO
-- 指定ID为1的是 成绩输入 模块
INSERT INTO [dbo].[cwebs_gates] ([id], [name], [comment], [status], [begin_time], [end_time], [year], [term])
    VALUES (N'1', N'成绩输入', N'关闭后成绩输入将无法进行', N'1', N'2015-10-13 00:00:00.000', N'2015-10-14 00:00:00.000', N'2011', N'2')
GO
GO
SET IDENTITY_INSERT [dbo].[cwebs_gates] OFF
GO

-- ----------------------------
-- Indexes structure for table cwebs_gates
-- ----------------------------

-- ----------------------------
-- Primary Key structure for table cwebs_gates
-- ----------------------------
ALTER TABLE [dbo].[cwebs_gates] ADD PRIMARY KEY ([id])
GO
