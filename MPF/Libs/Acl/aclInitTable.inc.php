<?php
/**
 * 这个文件是用户 ACL 权限数据库的建立
*/
 
return array(
//--资源组表
"DROP TABLE IF EXISTS ".MY_ACL_RESGROUP_TAB,
"CREATE TABLE IF NOT EXISTS ".MY_ACL_RESGROUP_TAB." (
  `resgroup_id` int(11) NOT NULL auto_increment COMMENT '资源组ID',
  `resgroup_name` varchar(100) default '0'      COMMENT '资源组名称',
  `product_type`  varchar(50) default ''        COMMENT '产品唯一标识',
  PRIMARY KEY  (`resgroup_id`),
  KEY `resgroup_name` (`resgroup_name`),
  KEY `product_type` (`product_type`)
) ENGINE=[ENGINE] DEFAULT CHARSET=[CHARSET];",

//--资源组与资源的关系表
"DROP TABLE IF EXISTS ".MY_ACL_RESGROUP_RESOURCE_TAB,
"CREATE TABLE IF NOT EXISTS ".MY_ACL_RESGROUP_RESOURCE_TAB." (
  `argr_id` int(11) NOT NULL auto_increment,
  `resgroup_id` int(11) default '0'          COMMENT '资源组ID',
  `resource_id` int(11) NOT NULL default '0' COMMENT '资源ID',
  PRIMARY KEY  (`argr_id`),
  KEY `resgroup_id` (`resgroup_id`)
) ENGINE=[ENGINE] DEFAULT CHARSET=[CHARSET];",

//--资源表
"DROP TABLE IF EXISTS ".MY_ACL_RESOURCE_TAB,
"CREATE TABLE IF NOT EXISTS ".MY_ACL_RESOURCE_TAB." (
  `resource_id` int(11) NOT NULL auto_increment      COMMENT '资源ID',
  `resource_name` varchar(60) NOT NULL default ''    COMMENT '资源名称',
  `resource_action` varchar(255) NOT NULL default '' COMMENT '资源动作',
  `product_type`  varchar(50) default ''             COMMENT '产品唯一标识',
  PRIMARY KEY  (`resource_id`),
  KEY `resource_name` (`resource_name`),
  KEY `product_type` (`product_type`)
) ENGINE=[ENGINE] DEFAULT CHARSET=[CHARSET];",

//--角色表
"DROP TABLE IF EXISTS ".MY_ACL_ROLE_TAB,
"CREATE TABLE IF NOT EXISTS ".MY_ACL_ROLE_TAB." (
  `role_id` int(11) NOT NULL auto_increment    COMMENT '角色ID',
  `role_name` varchar(100) NOT NULL default '' COMMENT '角色名称',
  `role_isadmin` tinyint(4) default '0'        COMMENT '超级角色是对所有的资源都可以访问;0:非 1:是 默认为 0',
  `product_type`  varchar(50) default ''       COMMENT '产品唯一标识',
  PRIMARY KEY  (`role_id`),
  KEY `role_name` (`role_name`),
  KEY `product_type` (`product_type`)
) ENGINE=[ENGINE] DEFAULT CHARSET=[CHARSET];",

//--角色继承关系表
"DROP TABLE IF EXISTS ".MY_ACL_ROLE_EXTEND_TAB,
"CREATE TABLE IF NOT EXISTS ".MY_ACL_ROLE_EXTEND_TAB." (
  `are_id` int(11) NOT NULL auto_increment,
  `role_id` int(11) NOT NULL default '0' COMMENT '角色ID',
  `role_parent_id` int(11) default '0'   COMMENT '角色的父角色,同理:角色的父级可能还有父级',
  PRIMARY KEY  (`are_id`),
  KEY `role_id` (`role_id`),
  KEY `role_parent_id` (`role_parent_id`)
) ENGINE=[ENGINE] DEFAULT CHARSET=[CHARSET];",

//--角色与资源组的关系表
"DROP TABLE IF EXISTS ".MY_ACL_ROLE_RESGROUP_TAB,
"CREATE TABLE IF NOT EXISTS ".MY_ACL_ROLE_RESGROUP_TAB." (
  `arr_id` int(11) NOT NULL auto_increment,
  `role_id` int(11) NOT NULL default '0'     COMMENT '角色ID',
  `resgroup_id` int(11) NOT NULL default '0' COMMENT '资源组ID',
  `allow_deny` tinyint(4) default '1'        COMMENT '访问控制 0:禁止 1:允许 默认为1:允许',
  PRIMARY KEY  (`arr_id`),
  KEY `role_id` (`role_id`,`allow_deny`)
) ENGINE=[ENGINE] DEFAULT CHARSET=[CHARSET];",

//--用户与角色关系表
"DROP TABLE IF EXISTS ".MY_ACL_USER_ROLE_TAB,
"CREATE TABLE IF NOT EXISTS ".MY_ACL_USER_ROLE_TAB." (
  `aur_id` int(11) NOT NULL auto_increment,
  `user_id` varchar(50) NOT NULL default '' COMMENT '用户唯一标识',
  `role_id` int(11) default '0'             COMMENT '角色ID',
  `product_type`  varchar(50) default ''    COMMENT '产品唯一标识',
  PRIMARY KEY  (`aur_id`),
  KEY `role_id` (`role_id`),
  KEY `user_id` (`user_id`),
  KEY `product_type` (`product_type`)
) ENGINE=[ENGINE] DEFAULT CHARSET=[CHARSET];",

            );
?>