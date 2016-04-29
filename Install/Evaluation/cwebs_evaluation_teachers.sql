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

Date: 2015-11-26 13:32:29
*/


-- ----------------------------
-- Table structure for cwebs_evaluation_teachers
-- ----------------------------
CREATE TABLE [dbo].[cwebs_evaluation_teachers] (
[year] smallint NOT NULL ,
[term] tinyint NOT NULL ,
[teacherno] varchar(15) NOT NULL ,
[score_evaluation] smallint NULL DEFAULT ((0)) -- 计算得出的教师评分
)

-- ----------------------------
-- score_evaluation计算规则
-- FS：该教师所有所教班级的评分的班级平均值 
-- FM：该教师所教班级中得分最高的教师的平均分经一定计算规则得到结果的平均值
-- N ：这个教师教的班级的数目
-- 计算规则:( (10 - (FM - FS)/10)) + ........) / N  
-- ----------------------------

GO

-- ----------------------------
-- Indexes structure for table cwebs_evaluation_teachers
-- ----------------------------

-- ----------------------------
-- Primary Key structure for table cwebs_evaluation_teachers
-- ----------------------------
ALTER TABLE [dbo].[cwebs_evaluation_teachers] ADD PRIMARY KEY ([teacherno], [term], [year])
GO
