<?php

/**
 * 安装脚本
 * @author wenbin.wu@foxmail.com
 * @copyright 2012
 */

header('Content-type: text/html; charset=utf-8');

//安装检查
@include_once('config.php');
$is_install = defined('DB_HOST') ? true : false;
$re_install = isset($_GET['key']) ? $_GET['key'] : '';
if($re_install != 'E5uQ28' && $is_install) {header('location:index.php'); exit;}

//处理配置信息
if($_POST) {
	$db_server = isset($_POST['db_server']) ? trim($_POST['db_server']) : '';
	$temp = explode(':', $db_server);
	$db_host = isset($temp[0]) ? ($temp[0] ? $temp[0] : 'localhost') : 'localhost';
	$db_port = isset($temp[1]) ? ($temp[1] ? $temp[1] : '3306') : '3306';
	$db_user = isset($_POST['db_user']) ? trim($_POST['db_user']) : 'root';
	$db_pwd = isset($_POST['db_pwd']) ? trim($_POST['db_pwd']) : '';
	$db_name = isset($_POST['db_name']) ? trim($_POST['db_name']) : 'favorite';
	$db_prefix = isset($_POST['db_prefix']) ? trim($_POST['db_prefix']) : '';
	
	$hd = @mysql_connect($db_host.':'.$db_port, $db_user, $db_pwd);
	if(!$hd) exit('提示：数据库连接失败！<a href="">返回</a>');
	if(!@mysql_select_db($db_name, $hd)) exit('提示：数据库不存在！<a href="">返回</a>');
	mysql_query('set names utf8');
	
	$sys_usr = 'administrator';
	$sys_pwd = md5('123456');
	$ctime = time();
	
	$sql = <<<END

CREATE TABLE IF NOT EXISTS `{$db_prefix}user` (
  `uid` int(11) NOT NULL AUTO_INCREMENT,
  `usr` varchar(255) NOT NULL,
  `pwd` varchar(255) NOT NULL,
  `lvl` tinyint(4) NOT NULL,
  `uname` varchar(255) NOT NULL,
  `phone` varchar(255) NOT NULL,
  `area1` varchar(255) NOT NULL,
  `area2` varchar(255) NOT NULL,
  `area3` varchar(255) NOT NULL,
  `extra` text,
  `ctime` int(11) NOT NULL,
  `state` tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`uid`),
  UNIQUE KEY `usr` (`usr`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 ;

INSERT INTO `{$db_prefix}user` (`uid`, `usr`, `pwd`, `lvl`, `uname`, `phone`, `area1`, `area2`, `area3`, `extra`, `ctime`, `state`) VALUES
(1, '{$sys_usr}', '{$sys_pwd}', 1, '管理员', '', '', '', '', NULL, {$ctime}, 0);

CREATE TABLE IF NOT EXISTS `{$db_prefix}sort` (
  `sid` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `ctime` int(11) NOT NULL,
  `seq` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`sid`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 ;

INSERT INTO `{$db_prefix}sort` (`sid`, `title`, `ctime`, `seq`) VALUES
(1, '默认分类', {$ctime}, 1);

CREATE TABLE IF NOT EXISTS `{$db_prefix}link` (
  `lid` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `url` varchar(255) NOT NULL,
  `sort` int(11) NOT NULL,
  `rank` int(11) NOT NULL,
  `own` tinyint(4) NOT NULL,
  `cnum` int(11) NOT NULL DEFAULT '0',
  `fnum` int(11) NOT NULL DEFAULT '0',
  `ctime` int(11) NOT NULL,
  PRIMARY KEY (`lid`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 ;

CREATE TABLE IF NOT EXISTS `{$db_prefix}feed` (
  `fid` int(11) NOT NULL AUTO_INCREMENT,
  `content` text,
  `ctime` int(11) NOT NULL,
  `state` tinyint(4) NOT NULL DEFAULT '0',
  `uname` varchar(255) NOT NULL,
  `phone` varchar(255) NOT NULL,
  `hidden` tinyint(4) NOT NULL DEFAULT '0',
  `uid` int(11) NOT NULL,
  `reply` text,
  PRIMARY KEY (`fid`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 ;

CREATE TABLE IF NOT EXISTS `{$db_prefix}login` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) NOT NULL,
  `ip` varchar(255) NOT NULL,
  `ctime` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 ;

END;

	$sqls = array_map('trim', explode(';', $sql));
	foreach($sqls as $v) @mysql_query($v, $hd);
	//清除缓存
	@include_once('./favorite/Common/common.php');
	define('RUNTIME_PATH', './favorite/Runtime/');
	$handle = @opendir(RUNTIME_PATH);
	while($path = @readdir($handle)) {
		if($path == '.' || $path == '..') continue;
		del_path(RUNTIME_PATH.$path, $list);
	}
	
	$config = <<<END
<?php

define('DB_HOST', '{$db_host}');
define('DB_PORT', '{$db_port}');
define('DB_USER', '{$db_user}');
define('DB_PWD', '{$db_pwd}');
define('DB_NAME', '{$db_name}');
define('DB_PREFIX', '{$db_prefix}');

END;
	//写入配置文件
	file_put_contents('config.php', $config);
	exit('提示：安装成功！<a href="index.php?a=index&m=Index">网站前台</a> <a href="index.php?a=index&m=Admin">管理后台</a>');
}

?>

<!--注册表单-->
<form method="post" action="">
	<table>
		<tr>
			<th>&nbsp;</th>
			<th>安装程序</th>
		</tr>
		<tr>
			<td>数据库地址：</td>
			<td><input type="text" name="db_server" value="localhost:3306" /></td>
		</tr>
		<tr>
			<td>用户名：</td>
			<td><input type="text" name="db_user" value="root" /></td>
		</tr>
		<tr>
			<td>密  码：</td>
			<td><input type="text" name="db_pwd" /></td>
		</tr>
		<tr>
			<td>数据库名称：</td>
			<td><input type="text" name="db_name" value="favorite" /></td>
		</tr>
		<tr>
			<td>表前缀：</td>
			<td><input type="text" name="db_prefix" value="pre_" /></td>
		</tr>
		<tr>
			<td>&nbsp;</td>
			<td><input type="submit" value="安装" /></td>
		</tr>
	</table>
</form>
