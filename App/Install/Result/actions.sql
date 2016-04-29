
INSERT INTO [dbo].[MENU_ACTIONS] ([ID], [PID], [NAME], [ACTION], [INNERID], [ROLES], [REM], [PATH], [ISMENU]) VALUES (N'17', null, N'成绩管理', N'Results/Index/index', N'', N'AB', N'', N'|17|', N'1')

INSERT INTO [dbo].[MENU_ACTIONS] ([ID], [PID], [NAME], [ACTION], [INNERID], [ROLES], [REM], [PATH], [ISMENU]) VALUES (N'17101', N'17', N'班级学期成绩汇总表 页面显示', N'Results/Select/pageClassStudentsScores', N'', N'B', N'', N'|17|17101|', N'1')
GO
GO
INSERT INTO [dbo].[MENU_ACTIONS] ([ID], [PID], [NAME], [ACTION], [INNERID], [ROLES], [REM], [PATH], [ISMENU]) VALUES (N'17301', N'17', N'成绩输入时间 页面', N'Results/Input/pageInputTimeSetting', N'', N'B', N'', N'|17|17301|', N'1')
GO
GO
INSERT INTO [dbo].[MENU_ACTIONS] ([ID], [PID], [NAME], [ACTION], [INNERID], [ROLES], [REM], [PATH], [ISMENU]) VALUES (N'17302', N'17', N'成绩输入时间 修改设置', N'Results/Input/updateInputTime', N'', N'B', N'', N'|17|17302|', N'1')
GO
GO
INSERT INTO [dbo].[MENU_ACTIONS] ([ID], [PID], [NAME], [ACTION], [INNERID], [ROLES], [REM], [PATH], [ISMENU]) VALUES (N'17303', N'17', N'课程总评成绩百分比管理 页面', N'Results/Input/pageCourseGeneralScorePercentManage', N'', N'B', N'', N'|17|17303|', N'1')
GO
GO
INSERT INTO [dbo].[MENU_ACTIONS] ([ID], [PID], [NAME], [ACTION], [INNERID], [ROLES], [REM], [PATH], [ISMENU]) VALUES (N'17304', N'17303', N'添加课程设置', N'Results/Input/createCourseGeneralScorePercent', N'', N'B', N'', N'|17|17303|17304|', N'1')
GO
GO
INSERT INTO [dbo].[MENU_ACTIONS] ([ID], [PID], [NAME], [ACTION], [INNERID], [ROLES], [REM], [PATH], [ISMENU]) VALUES (N'17305', N'17303', N'删除课程设置', N'Results/Input/deleteCourseGeneralScorePercent', N'', N'B', N'', N'|17|17303|17305|', N'1')
GO
GO
INSERT INTO [dbo].[MENU_ACTIONS] ([ID], [PID], [NAME], [ACTION], [INNERID], [ROLES], [REM], [PATH], [ISMENU]) VALUES (N'17306', N'17303', N'对应的课程设置', N'Results/Input/selectCourseScoreSetting', N'', N'B', N'', N'|17|17303|17306|', N'1')
GO
GO
INSERT INTO [dbo].[MENU_ACTIONS] ([ID], [PID], [NAME], [ACTION], [INNERID], [ROLES], [REM], [PATH], [ISMENU]) VALUES (N'17307', N'17303', N'列表数据获取', N'Results/Input/listCourseGeneralScorePercent', N'', N'B', N'', N'|17|17303|17307|', N'1')
GO
GO
INSERT INTO [dbo].[MENU_ACTIONS] ([ID], [PID], [NAME], [ACTION], [INNERID], [ROLES], [REM], [PATH], [ISMENU]) VALUES (N'17308', N'17303', N'批量更新总评百分比设置', N'Results/Input/updateGeneralScorePercentInBatch', N'', N'B', N'', N'|17|17303|17308|', N'1')
GO
GO
INSERT INTO [dbo].[MENU_ACTIONS] ([ID], [PID], [NAME], [ACTION], [INNERID], [ROLES], [REM], [PATH], [ISMENU]) VALUES (N'17309', N'17', N'课程成绩输入初始化 页面', N'Results/Input/pageInputCourseInit', N'', N'B', N'', N'|17|17309|', N'1')
GO
GO
INSERT INTO [dbo].[MENU_ACTIONS] ([ID], [PID], [NAME], [ACTION], [INNERID], [ROLES], [REM], [PATH], [ISMENU]) VALUES (N'17311', N'17309', N'课程成绩输入初始化', N'Results/Input/initCourseFinalsInput', N'', N'B', N'', N'|17|17309|17311|', N'1')
GO
GO
INSERT INTO [dbo].[MENU_ACTIONS] ([ID], [PID], [NAME], [ACTION], [INNERID], [ROLES], [REM], [PATH], [ISMENU]) VALUES (N'17312', N'17309', N'课程补考成绩输入初始化', N'Results/Input/initCourseResitInput', N'', N'B', N'', N'|17|17309|17312|', N'1')
GO
GO
INSERT INTO [dbo].[MENU_ACTIONS] ([ID], [PID], [NAME], [ACTION], [INNERID], [ROLES], [REM], [PATH], [ISMENU]) VALUES (N'17313', N'17', N'任课教师补考成绩输入 课程列表页面', N'Results/Input/pageResitSelectNV', N'', N'B', N'', N'|17|17313|', N'1')
GO
GO
INSERT INTO [dbo].[MENU_ACTIONS] ([ID], [PID], [NAME], [ACTION], [INNERID], [ROLES], [REM], [PATH], [ISMENU]) VALUES (N'17314', N'17313', N'补考成绩打印页面', N'Results/Input/pageResitInputForPrintNV', N'', N'B', N'', N'|17|17313|17314|', N'1')
GO
GO
INSERT INTO [dbo].[MENU_ACTIONS] ([ID], [PID], [NAME], [ACTION], [INNERID], [ROLES], [REM], [PATH], [ISMENU]) VALUES (N'17315', N'17313', N'学生成绩列表获取', N'Results/Input/listResitInputNV', N'', N'B', N'', N'|17|17313|17315|', N'1')
GO
GO
INSERT INTO [dbo].[MENU_ACTIONS] ([ID], [PID], [NAME], [ACTION], [INNERID], [ROLES], [REM], [PATH], [ISMENU]) VALUES (N'17316', N'17313', N'更新补考成绩输入 ', N'Results/Input/updateResitScoreInBatchNV', N'', N'B', N'', N'|17|17313|17316|', N'1')
GO
GO
INSERT INTO [dbo].[MENU_ACTIONS] ([ID], [PID], [NAME], [ACTION], [INNERID], [ROLES], [REM], [PATH], [ISMENU]) VALUES (N'17317', N'17313', N'成绩列表页面显示', N'Results/Input/pageResitInputNV', N'', N'B', N'', N'|17|17313|17317|', N'1')
GO
GO
INSERT INTO [dbo].[MENU_ACTIONS] ([ID], [PID], [NAME], [ACTION], [INNERID], [ROLES], [REM], [PATH], [ISMENU]) VALUES (N'17318', N'17', N'检查是否允许输入成绩', N'Results/Input/isInputable', N'', N'B', N'', N'|17|17318|', N'1')
GO
GO
INSERT INTO [dbo].[MENU_ACTIONS] ([ID], [PID], [NAME], [ACTION], [INNERID], [ROLES], [REM], [PATH], [ISMENU]) VALUES (N'17319', N'17', N'补考成绩输入课程列表查询界面', N'Results/Input/pageResitSelect', N'', N'B', N'', N'|17|17319|', N'1')
GO
GO
INSERT INTO [dbo].[MENU_ACTIONS] ([ID], [PID], [NAME], [ACTION], [INNERID], [ROLES], [REM], [PATH], [ISMENU]) VALUES (N'17320', N'17319', N'补考成绩输入页面', N'Results/Input/pageResitInput', N'', N'B', N'', N'|17|17319|17320|', N'1')
GO
GO
INSERT INTO [dbo].[MENU_ACTIONS] ([ID], [PID], [NAME], [ACTION], [INNERID], [ROLES], [REM], [PATH], [ISMENU]) VALUES (N'17321', N'17319', N'成绩打印页面', N'Results/Input/pageResitInputForPrint', N'', N'B', N'', N'|17|17319|17321|', N'1')
GO
GO
INSERT INTO [dbo].[MENU_ACTIONS] ([ID], [PID], [NAME], [ACTION], [INNERID], [ROLES], [REM], [PATH], [ISMENU]) VALUES (N'17322', N'17', N'任课老师期末成绩输入 课程选择界面', N'Results/Input/pageFinalsSelect', N'', N'B', N'', N'|17|17322|', N'1')
GO
GO
INSERT INTO [dbo].[MENU_ACTIONS] ([ID], [PID], [NAME], [ACTION], [INNERID], [ROLES], [REM], [PATH], [ISMENU]) VALUES (N'17323', N'17322', N'课程列表获取', N'Results/Input/listFinalsSelect', N'', N'B', N'', N'|17|17322|17323|', N'1')
GO
GO
INSERT INTO [dbo].[MENU_ACTIONS] ([ID], [PID], [NAME], [ACTION], [INNERID], [ROLES], [REM], [PATH], [ISMENU]) VALUES (N'17324', N'17322', N'学生列表界面', N'Results/Input/pageFinalsInput', N'', N'B', N'', N'|17|17322|17324|', N'1')
GO
GO
INSERT INTO [dbo].[MENU_ACTIONS] ([ID], [PID], [NAME], [ACTION], [INNERID], [ROLES], [REM], [PATH], [ISMENU]) VALUES (N'17325', N'17322', N'成绩列表获取', N'Results/Input/listFinalsInput', N'', N'B', N'', N'|17|17322|17325|', N'1')
GO
GO
INSERT INTO [dbo].[MENU_ACTIONS] ([ID], [PID], [NAME], [ACTION], [INNERID], [ROLES], [REM], [PATH], [ISMENU]) VALUES (N'17326', N'17319', N'获取页面数据', N'Results/Input/listResitInputForPrint', N'', N'B', N'', N'|17|17319|17326|', N'1')
GO
GO
INSERT INTO [dbo].[MENU_ACTIONS] ([ID], [PID], [NAME], [ACTION], [INNERID], [ROLES], [REM], [PATH], [ISMENU]) VALUES (N'17327', N'17319', N'获取页面数据', N'Results/Input/listResitSelect', N'', N'B', N'', N'|17|17319|17327|', N'1')
GO
GO
INSERT INTO [dbo].[MENU_ACTIONS] ([ID], [PID], [NAME], [ACTION], [INNERID], [ROLES], [REM], [PATH], [ISMENU]) VALUES (N'17328', N'17319', N'学生列表数据获取', N'Results/Input/listResitStudent', N'', N'B', N'', N'|17|17319|17328|', N'1')
GO
GO
INSERT INTO [dbo].[MENU_ACTIONS] ([ID], [PID], [NAME], [ACTION], [INNERID], [ROLES], [REM], [PATH], [ISMENU]) VALUES (N'17329', N'17322', N'批量修改期末成绩', N'Results/Input/updateFinalsScoreInBatch', N'', N'B', N'', N'|17|17322|17329|', N'1')
GO
GO
INSERT INTO [dbo].[MENU_ACTIONS] ([ID], [PID], [NAME], [ACTION], [INNERID], [ROLES], [REM], [PATH], [ISMENU]) VALUES (N'17330', N'17', N'开放与查看课程 页面', N'Results/Input/pageCoursesWithOpen', N'', N'B', N'', N'|17|17330|', N'1')
GO
GO
INSERT INTO [dbo].[MENU_ACTIONS] ([ID], [PID], [NAME], [ACTION], [INNERID], [ROLES], [REM], [PATH], [ISMENU]) VALUES (N'17331', N'17330', N'开放与查看课程 页面列表数据', N'Results/Input/listCoursesWithOpen', N'', N'B', N'', N'|17|17330|17331|', N'1')
GO
GO
INSERT INTO [dbo].[MENU_ACTIONS] ([ID], [PID], [NAME], [ACTION], [INNERID], [ROLES], [REM], [PATH], [ISMENU]) VALUES (N'17332', N'17330', N'批量解锁 期末成绩输入', N'Results/Input/updateFinalsLockStatusInBatch', N'', N'B', N'', N'|17|17330|17332|', N'1')
GO
GO
INSERT INTO [dbo].[MENU_ACTIONS] ([ID], [PID], [NAME], [ACTION], [INNERID], [ROLES], [REM], [PATH], [ISMENU]) VALUES (N'17333', N'17330', N'批量解锁 补考成绩输入', N'Results/Input/updateResitLockStatusInBatch', N'', N'B', N'', N'|17|17330|17333|', N'1')
GO
GO
INSERT INTO [dbo].[MENU_ACTIONS] ([ID], [PID], [NAME], [ACTION], [INNERID], [ROLES], [REM], [PATH], [ISMENU]) VALUES (N'17334', N'17330', N'单个解锁 补考成绩输入', N'Results/Input/updateResitLockStatus', N'', N'B', N'', N'|17|17330|17334|', N'1')
GO
GO
INSERT INTO [dbo].[MENU_ACTIONS] ([ID], [PID], [NAME], [ACTION], [INNERID], [ROLES], [REM], [PATH], [ISMENU]) VALUES (N'17336', N'17', N'还未输入成绩课程 页面显示', N'Results/Input/pageCoursesWhichScoreInputness', N'', N'B', N'', N'|17|17336|', N'1')
GO
GO
INSERT INTO [dbo].[MENU_ACTIONS] ([ID], [PID], [NAME], [ACTION], [INNERID], [ROLES], [REM], [PATH], [ISMENU]) VALUES (N'17337', N'17336', N'列表数据获取', N'listCoursesWhichScoreInputness', N'', N'B', N'', N'|17|17336|17337|', N'1')
GO
GO
INSERT INTO [dbo].[MENU_ACTIONS] ([ID], [PID], [NAME], [ACTION], [INNERID], [ROLES], [REM], [PATH], [ISMENU]) VALUES (N'17338', N'17336', N'导出excel', N'Results/Input/exportCoursesWithOpen', N'', N'B', N'', N'|17|17336|17338|', N'1')
GO
GO
INSERT INTO [dbo].[MENU_ACTIONS] ([ID], [PID], [NAME], [ACTION], [INNERID], [ROLES], [REM], [PATH], [ISMENU]) VALUES (N'17339', N'17', N'重修成绩输入  课程列表界面', N'Results/Input/pageRetakeSelect', N'', N'B', N'', N'|17|17339|', N'1')
GO
GO
INSERT INTO [dbo].[MENU_ACTIONS] ([ID], [PID], [NAME], [ACTION], [INNERID], [ROLES], [REM], [PATH], [ISMENU]) VALUES (N'17340', N'17339', N'列表数据获取', N'Results/Input/listRetakeSelect', N'', N'B', N'', N'|17|17339|17340|', N'1')
GO
GO
INSERT INTO [dbo].[MENU_ACTIONS] ([ID], [PID], [NAME], [ACTION], [INNERID], [ROLES], [REM], [PATH], [ISMENU]) VALUES (N'17341', N'17339', N'成绩输入界面', N'Results/Input/pageRetakeInput', N'', N'B', N'', N'|17|17339|17341|', N'1')
GO
GO
INSERT INTO [dbo].[MENU_ACTIONS] ([ID], [PID], [NAME], [ACTION], [INNERID], [ROLES], [REM], [PATH], [ISMENU]) VALUES (N'17342', N'17', N'获取学生信息', N'Results/Input/selectStudentInfo', N'', N'B', N'', N'|17|17342|', N'1')
GO
GO
INSERT INTO [dbo].[MENU_ACTIONS] ([ID], [PID], [NAME], [ACTION], [INNERID], [ROLES], [REM], [PATH], [ISMENU]) VALUES (N'17345', N'17322', N'打印成绩列表', N'Results/Input/pageFinalsInputForPrint', N'', N'B', N'', N'|17|17322|17345|', N'1')
GO
GO
INSERT INTO [dbo].[MENU_ACTIONS] ([ID], [PID], [NAME], [ACTION], [INNERID], [ROLES], [REM], [PATH], [ISMENU]) VALUES (N'17350', N'17101', N'列表数据获取', N'Results/Select/listClassStudentScores', N'', N'B', N'', N'|17|17101|17350|', N'1')
GO
GO
INSERT INTO [dbo].[MENU_ACTIONS] ([ID], [PID], [NAME], [ACTION], [INNERID], [ROLES], [REM], [PATH], [ISMENU]) VALUES (N'17351', N'17101', N'excel导出', N'Results/Select/exportClassStudentScores', N'', N'B', N'', N'|17|17101|17351|', N'1')
GO
GO
INSERT INTO [dbo].[MENU_ACTIONS] ([ID], [PID], [NAME], [ACTION], [INNERID], [ROLES], [REM], [PATH], [ISMENU]) VALUES (N'17352', N'17101', N'查询班级课程信息', N'Results/Select/selectClassCourses', N'', N'B', N'', N'|17|17101|17352|', N'1')
GO
GO
INSERT INTO [dbo].[MENU_ACTIONS] ([ID], [PID], [NAME], [ACTION], [INNERID], [ROLES], [REM], [PATH], [ISMENU]) VALUES (N'17360', N'17', N'班级成绩分析 页面', N'Results/Analysis/pageAnaliseClassScores', N'', N'B', N'', N'|17|17360|', N'1')
GO
GO
INSERT INTO [dbo].[MENU_ACTIONS] ([ID], [PID], [NAME], [ACTION], [INNERID], [ROLES], [REM], [PATH], [ISMENU]) VALUES (N'17361', N'17360', N'页面数据获取', N'listAnaliseClassScores', N'', N'B', N'', N'|17|17360|17361|', N'1')
GO
GO
INSERT INTO [dbo].[MENU_ACTIONS] ([ID], [PID], [NAME], [ACTION], [INNERID], [ROLES], [REM], [PATH], [ISMENU]) VALUES (N'17362', N'17360', N'导出', N'Results/Analysis/exportAnaliseClassScores', N'', N'B', N'', N'|17|17360|17362|', N'1')
GO