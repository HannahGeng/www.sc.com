<?php
return array(
	'tableName' => 'p39_admin_role',    // 表名
	'tableCnName' => '管理员角色',  // 表的中文名
	'moduleName' => 'Admin',  // 代码生成到的模块
	'withPrivilege' => FALSE,  // 是否生成相应权限的数据
	'topPriName' => '',        // 顶级权限的名称
	'digui' => 0,             // 是否无限级（递归）
	'diguiName' => '',        // 递归时用来显示的字段的名字，如cat_name（分类名称）
	'pk' => 'id',    // 表中主键字段名称
	/********************* 要生成的模型文件中的代码 ******************************/
	// 添加时允许接收的表单中的字段
	'insertFields' => "array('admin_id','role_id')",
	// 修改时允许接收的表单中的字段
	'updateFields' => "array('id','admin_id','role_id')",
	'validate' => "
		array('admin_id', 'require', '管理员id不能为空！', 1, 'regex', 3),
		array('admin_id', 'number', '管理员id必须是一个整数！', 1, 'regex', 3),
		array('role_id', 'require', '角色id不能为空！', 1, 'regex', 3),
		array('role_id', 'number', '角色id必须是一个整数！', 1, 'regex', 3),
	",
	/********************** 表中每个字段信息的配置 ****************************/
	'fields' => array(
		'admin_id' => array(
			'text' => '管理员id',
			'type' => 'text',
			'default' => '',
		),
		'role_id' => array(
			'text' => '角色id',
			'type' => 'text',
			'default' => '',
		),
	),
	/**************** 搜索字段的配置 **********************/
	'search' => array(
		array('admin_id', 'normal', '', 'eq', '管理员id'),
		array('role_id', 'normal', '', 'eq', '角色id'),
	),
);