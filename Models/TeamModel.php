<?php
/**
 * 用户表模型示例 InnoDB
 *
 * id    int(10) ID
 * name  varchar(20)  用户名  INDEX
 *
 * 2012-x-xx 1.0 daniel 创建
 *
 * @author  xxx
 * @version 1.0
 */
class TeamModel extends Model
{
	// 定义要操作的表名及主键
	public $table   = 'team';
	public $pk      = 'id';

}

/* End */
