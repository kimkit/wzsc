<?php
//定义项目名称和路径
define('APP_NAME', 'favorite');
define('APP_PATH', './favorite/');
//引入配置文件
@include_once('./config.php');
//检查是否安装
if(!defined('DB_HOST')) header("Location:install.php");

header("Content-Type:text/html; charset=utf-8");
date_default_timezone_set('Asia/Shanghai');
// 加载框架入口文件
include_once( "./ThinkPHP/ThinkPHP.php");
