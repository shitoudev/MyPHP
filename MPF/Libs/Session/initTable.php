<?php
/**
 * 这个文件是SESSION基于数据库管理表的初始化
 */
return array(
//--SESSION表
"DROP TABLE IF EXISTS ".MY_SESSION_TABLE,
"CREATE TABLE ".MY_SESSION_TABLE." (                 
 `sess_id` char(32) NOT NULL DEFAULT '',            
 `sess_time` int(11) unsigned NOT NULL DEFAULT 0,  
 `sess_value` text,                      
PRIMARY KEY  (`sess_id`),               
KEY `sess_time` (`sess_time`)           
) ENGINE=[ENGINE] DEFAULT CHARSET=[CHARSET];",
);
?>