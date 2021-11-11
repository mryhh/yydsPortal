<?php
return array(
	//开启模板布局
	'LAYOUT_ON'				=>	false,
	'LAYOUT_NAME'			=>	'layout',

	//'配置项'=>'配置值'
	'URL_MODEL'				=>	'2',					//URL模式

	//session配置
	'SESSION_AUTO_START'	=>	true,					//开启session
	//文件缓存配置
	'SESSION_PREFIX'		=>	'Portal',				//session前缀
	'SESSION_OPTIONS'		=>	array(
		'expire'				=>	3600 * 24 * 28,		//有效时间28天
	),
	//数据库缓存配置
	//'SESSION_TYPE'			=>	'Redis',				//session驱动
	//'SESSION_PREFIX'			=>	'Portal',				//session前缀
	//'SESSION_REDIS_SELECT'	=>	'0',					//redis库id
	//'SESSION_REDIS_EXPIRE'	=>	3600 * 24 * 14,			//有效时间14天
	//'SESSION_TABLE'			=>	'think_session',
	//'SESSION_REDIS_HOST'		=>	'',
	//'SESSION_REDIS_POST'		=>	'',
	//'SESSION_REDIS_PSW'		=>	'',	//密钥

	//配置模块信息
	'MODULE_DENY_LIST'		=>	array('Common','_Runtime'),						//禁止访问的模块列表
	'MODULE_ALLOW_LIST'		=>	array('Admin', 'AdminApi', ),					//允许访问的模块列表
	'DEFAULT_MODULE'		=>	'Admin',										//默认模块

	//URL不区分大小写
	'URL_CASE_INSENSITIVE'	=>	true,

	//URL伪静态后缀设置
	'TMPL_CONTENT_TYPE'		=>	'text/html',		//默认模板输出类型
	'TMPL_TEMPLATE_SUFFIX'	=>	'.html',			//默认模板文件后缀

	//后台名称
	'ADMIN_NAME'			=>	'1234',
	'COMPANY_NAME'			=>	'1234',

	//数据库
	define('DBTYPE', 'Oracle'),
	'DEFAULT_DB'			=>	array(
		'DB_HOST'				=>	'127.0.0.1:1521',
		'DB_USER'				=>	'yyds',
		'DB_PWD'				=>	'oracle123',
		'DB_NAME'				=>	'orcl',
	),

	//OSS上传密钥
	'OSS'					=>	array(
		'TYPE'					=>	'TencentOSS',
		'LOCAL'					=>	'',							//上传地址
		'KEY_ID'				=>	'',							//从OSS获得的AccessKeyId
		'KEY_SECRET'			=>	'',							//从OSS获得的AccessKeySecret
		'ENDPOINT'				=>	'',							//您选定的OSS数据中心访问域名，例如oss-cn-hangzhou.aliyuncs.com
		'BUCKET'				=>	'',							//桶名称
		'REGION'				=>	'',							//地域名称
	),

	//加载扩展配置文件
	'LOAD_EXT_CONFIG'		=>	'program',
);