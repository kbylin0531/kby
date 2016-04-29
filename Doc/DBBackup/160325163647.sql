/*
MySQL Backup
Source Server Version: 5.5.44
Source Database: bylincms
Date: 2016/3/25 16:36:49
*/

SET FOREIGN_KEY_CHECKS=0;

-- ----------------------------
--  Table structure for `bl_config`
-- ----------------------------
DROP TABLE IF EXISTS `bl_config`;
CREATE TABLE `bl_config` (
  `cid` smallint(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '配置项id',
  `title` varchar(255) NOT NULL COMMENT '配置名称',
  `name` varchar(255) NOT NULL COMMENT '标识名称',
  `description` varchar(255) NOT NULL DEFAULT '' COMMENT '配置说明',
  `value` text NOT NULL COMMENT '配置值',
  `group` tinyint(4) NOT NULL COMMENT '配置分组',
  `status` enum('1','0') NOT NULL DEFAULT '1' COMMENT '状态，1启用 0 禁用',
  `sort` smallint(255) DEFAULT NULL COMMENT '排序',
  `create_time` datetime NOT NULL COMMENT '创建时间',
  `update_time` datetime NOT NULL COMMENT '修改时间',
  PRIMARY KEY (`cid`),
  UNIQUE KEY `unique_conf_name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;

-- ----------------------------
--  Table structure for `bl_entity_auth`
-- ----------------------------
DROP TABLE IF EXISTS `bl_entity_auth`;
CREATE TABLE `bl_entity_auth` (
  `aid` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '权限id',
  `value` varchar(255) NOT NULL DEFAULT '' COMMENT '权限值，即访问节点的URI',
  `name` varchar(255) NOT NULL DEFAULT 'Undefined' COMMENT '权限名称',
  `status` enum('-1','0','1') NOT NULL DEFAULT '1' COMMENT '权限状态',
  `sub_auth_ids` text CHARACTER SET ascii COMMENT '子权限集合，多个以逗号分隔，默认为null',
  `parent_auth_id` int(11) NOT NULL COMMENT '父权限id',
  PRIMARY KEY (`aid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------
--  Table structure for `bl_entity_auth_group`
-- ----------------------------
DROP TABLE IF EXISTS `bl_entity_auth_group`;
CREATE TABLE `bl_entity_auth_group` (
  `gid` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '组号id',
  `name` varchar(255) NOT NULL DEFAULT 'Undefined' COMMENT '节点名称',
  `description` varchar(255) NOT NULL DEFAULT '' COMMENT '权限组描述',
  `status` enum('0','-1','1') NOT NULL DEFAULT '1' COMMENT '权限组状态，1表示启用，0表示禁用，-1表示删除',
  PRIMARY KEY (`gid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------
--  Table structure for `bl_entity_login_history`
-- ----------------------------
DROP TABLE IF EXISTS `bl_entity_login_history`;
CREATE TABLE `bl_entity_login_history` (
  `rid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `uid` int(11) NOT NULL COMMENT '用户id',
  `log_ip` varchar(255) NOT NULL COMMENT '登陆ip',
  `log_time` datetime DEFAULT NULL,
  `log_place` varchar(255) DEFAULT NULL COMMENT '登陆地点(参考）',
  PRIMARY KEY (`rid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------
--  Table structure for `bl_entity_role`
-- ----------------------------
DROP TABLE IF EXISTS `bl_entity_role`;
CREATE TABLE `bl_entity_role` (
  `rid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL DEFAULT 'Undefined' COMMENT '角色名称',
  `parents` int(11) DEFAULT NULL COMMENT '父角色,为null时表示顶级角色，如果有多个自角色则使用逗号分隔',
  `status` enum('0','-1','1') NOT NULL DEFAULT '1' COMMENT '角色状态，1表示启用，0表示禁用，-1表示删除',
  PRIMARY KEY (`rid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------
--  Table structure for `bl_entity_user`
-- ----------------------------
DROP TABLE IF EXISTS `bl_entity_user`;
CREATE TABLE `bl_entity_user` (
  `uid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `nickname` varchar(255) NOT NULL DEFAULT 'Nick' COMMENT '昵称',
  `username` varchar(255) NOT NULL COMMENT '登陆名称',
  `birthday` date DEFAULT NULL,
  `sex` enum('0','1') NOT NULL DEFAULT '1' COMMENT '1表示男，0表示女',
  `reg_time` datetime NOT NULL COMMENT '注册时间',
  `reg_ip` varchar(32) NOT NULL COMMENT '注册ip',
  `status` enum('0','1','-1') NOT NULL DEFAULT '1' COMMENT '账号状态，1表示启用，0表示禁用，-1表示删除',
  `email` varchar(255) DEFAULT NULL COMMENT '电子邮件',
  `phone` varchar(255) DEFAULT NULL COMMENT '手机',
  PRIMARY KEY (`uid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------
--  Table structure for `bl_menu`
-- ----------------------------
DROP TABLE IF EXISTS `bl_menu`;
CREATE TABLE `bl_menu` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `titile` varchar(255) NOT NULL,
  `url` text NOT NULL,
  `name` varchar(255) NOT NULL,
  `sort` smallint(6) NOT NULL DEFAULT '0' COMMENT '排序',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------
--  Table structure for `bl_menu_category`
-- ----------------------------
DROP TABLE IF EXISTS `bl_menu_category`;
CREATE TABLE `bl_menu_category` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '菜单分类项ID',
  `title` varchar(255) NOT NULL COMMENT '分类项名称',
  `type` enum('0','1') NOT NULL DEFAULT '0' COMMENT '分类类型，0为主分类，1为子分类',
  `default` int(11) DEFAULT NULL COMMENT '默认对应的菜单项',
  `sort` smallint(6) NOT NULL DEFAULT '0' COMMENT '排序',
  `name` varchar(255) NOT NULL COMMENT '菜单项文本标识(唯一）',
  PRIMARY KEY (`id`),
  UNIQUE KEY `name_unique` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------
--  Table structure for `bl_relation_auth_authgroup`
-- ----------------------------
DROP TABLE IF EXISTS `bl_relation_auth_authgroup`;
CREATE TABLE `bl_relation_auth_authgroup` (
  `aid` int(11) NOT NULL,
  `gid` int(11) NOT NULL,
  PRIMARY KEY (`aid`,`gid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------
--  Table structure for `bl_relation_authgroup_role`
-- ----------------------------
DROP TABLE IF EXISTS `bl_relation_authgroup_role`;
CREATE TABLE `bl_relation_authgroup_role` (
  `rid` int(11) NOT NULL COMMENT '角色id',
  `gid` int(11) NOT NULL COMMENT '权限组id',
  PRIMARY KEY (`rid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------
--  Table structure for `bl_relation_role_user`
-- ----------------------------
DROP TABLE IF EXISTS `bl_relation_role_user`;
CREATE TABLE `bl_relation_role_user` (
  `uid` int(11) NOT NULL COMMENT '用户id',
  `rid` int(11) NOT NULL COMMENT '角色id',
  PRIMARY KEY (`uid`,`rid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------
--  Records 
-- ----------------------------
INSERT INTO `bl_config` VALUES ('1','配置分组','CONF_GROUP_LIST','配置的分组设置','{\"0\": \"默认分组\",\"1\": \"系统设置\"}','0','1','0','2016-03-24 11:10:35','2016-03-24 11:10:40');
