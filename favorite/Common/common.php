<?php

/**
 * 通用函数
 * @author wenbin.wu@foxmail.com
 * @copyright 2012
 */

//分页函数
function pager($page, $count, $url, $each = 10, $size = 5) {
	$page_count = ceil($count/$each);
	if($page > $page_count || $page < 1) return '';
	if($page_count == 1) return '';
	$size_count = ceil($page_count/$size);
	$size_cur = ceil($page/$size);
	$begin = ($size_cur - 1)*$size+1;
	$end = $begin+$size;
	if($end > $page_count) $end = $page_count;
	$page_html = '共'.$count.'条记录&nbsp;&nbsp;';
	$page_html .= ($page == 1) ? '<span class="disabled">上一页</span>' :
		'<a href="'.str_replace('__P__', $page-1, $url).'">上一页</a>';
	$page_html .= ($size_cur == 1) ? '' :
		'<a href="'.str_replace('__P__', 1, $url).'">1</a>...';
	for($i = $begin; $i <= $end; $i++) {
		if($page == $i) $page_html .= '<span class="current">'.$i.'</span>';
		else $page_html .= '<a href="'.str_replace('__P__', $i, $url).'">'.$i.'</a>';
	}
	$page_html .= ($size_cur == $size_count) ? '' :
		'...<a href="'.str_replace('__P__', $page_count, $url).'">'.$page_count.'</a>';
	$page_html .= ($page == $page_count) ? '<span class="disabled">下一页</span>' :
		'<a href="'.str_replace('__P__', $page+1, $url).'">下一页</a>';
	return $page_html;
}

//字符串截取
function str_cut($str, $length, $suffix = '...') {
	$regex = "/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|[\xe0-\xef][\x80-\xbf]{2}|[\xf0-\xff][\x80-\xbf]{3}/";
	preg_match_all($regex, $str, $match);
	$_str = '';
	$cnum = 0; //记录字符长度;中文:2;英文:1
	for($i = 0; $i < count($match[0]); $i++) {
		$cnum += strlen($match[0][$i]) > 1 ? 2 : 1;
		$_str .= $match[0][$i];
		if($cnum >= $length) {
			if(strlen($_str) < strlen($str)) $_str .= $suffix;
			break;
		}
	}
	return $_str;
}

//删除目录,文件
function del_path($path, &$list = null) {
	if(!is_array($list)) $list = array();
	$handle = @opendir($path);
	while($file = @readdir($handle)) {
		if($file == '.' || $file == '..') continue;
		if(is_dir($path.'/'.$file)) {
			del_path($path.'/'.$file);
		} else {
			if(!@unlink($path.'/'.$file)) {
				@chmod($path, 0777);
				@unlink($path);
			}
			$list[] = $path.'/'.$file;
		}
	}
	@closedir($handle);
	if(!@rmdir($path)) {
		@chmod($path, 0777);
		@rmdir($path);
	}
	if(!@unlink($path)) {
		@chmod($path, 0777);
		@unlink($path);
	}
	$list[] = $path;
}


