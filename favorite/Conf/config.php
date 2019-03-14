<?php
return array(
	//数据库配置
	'DB_TYPE'			=> 'mysql',
	'DB_HOST'			=> defined('DB_HOST') ? DB_HOST : 'localhost',
	'DB_PORT'			=> defined('DB_PORT') ? DB_PORT : '3306',
	'DB_USER'			=> defined('DB_USER') ? DB_USER : 'root',
	'DB_PWD'			=> defined('DB_PWD') ? DB_PWD : '',
	'DB_NAME'			=> defined('DB_NAME') ? DB_NAME : '',
	'DB_PREFIX'			=> defined('DB_PREFIX') ? DB_PREFIX : 'pre_',
	//URL网址模式
	'URL_MODEL'			=> 0
);
?>