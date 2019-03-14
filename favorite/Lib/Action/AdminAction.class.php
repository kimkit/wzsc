<?php

/**
 * 后台管理
 * @author webin.wu@foxmail.com
 * @copyright 2012
 */
 
class AdminAction extends Action {
	//初始化操作
	public function _initialize() {
		$user = session('user');
		if(ACTION_NAME == 'login' || ACTION_NAME == 'verify') {
			if(!empty($user) && $user['lvl']) redirect(U('Admin/index'));
		} else {
			if(empty($user) || !$user['lvl']) redirect(U('Admin/login'));
		}
		$root = substr(PHP_FILE, 0, -(strlen(end(explode('/', PHP_FILE)))));
		$tpl_url = rtrim($root, '/').APP_TMPL_PATH.'Admin/';
		$this->assign('_root', $root);
		$this->assign('_tpl_url', $tpl_url);
		$this->assign('_user', $user);
	}

	//后台首页
    public function index() {
		//上部导航
		$channel = array(
			'index'	=> '首页',
			'extra' => '扩展',
		);
		//左边导航
		$menu['index'] = array(
			'首页' => array(
				'系统信息' => U('Admin/sys_info'),
				'清理缓存' => U('Admin/clean_cache')
			)
		);
		$menu['extra'] = array(
			'扩展' => array(
				'用户管理' => U('Admin/user'),
				'收藏管理' => U('Admin/link'),
				'分类管理' => U('Admin/sort'),
				'用户留言' => U('Admin/feed'),
				'登录历史' => U('Admin/llog')
			)
		);
		
		$this->assign('channel', $channel);
		$this->assign('menu', $menu);
		$this->display();
    }
	
	//用户登录
	public function login() {
		if($_POST) {
			$map['usr'] = isset($_POST['usr']) ? trim($_POST['usr']) : '';
			$map['pwd'] = isset($_POST['pwd']) ? md5($_POST['pwd']) : '';
			$verify = isset($_POST['verify']) ? $_POST['verify'] : '';
			if(session('verify') != md5($verify) || empty($verify)) $this->error('验证码错误');
			if($usr === '' || $pwd === '') $this->error('用户名或密码错误');
			$user = M('user')->where($map)->find();
			if(empty($user)) $this->error('用户不存在');
			if(!$user['lvl']) $this->error('操作权限不够');
			session('user', $user);
			//记录登录信息
			M('login')->add(array('uid' => $user['uid'], 'ip' => get_client_ip(), 'ctime' => time()));
			redirect(U('Admin/index'));
			exit;
		}
		$this->display();
	}
	
	//用户退出
	public function logout() {
		session('user', null);
		redirect(U('Admin/login'));
	}
	
	//验证码
	public function verify() {
		import("ORG.Util.Image");
		Image::buildImageVerify(4, 1, 'png', null, 26);
	}
	
	//系统信息
	public function sys_info() {
		//系统信息
		$sys['php'] = PHP_OS.' / PHP v'.PHP_VERSION;
		$sys['server'] = $_SERVER['SERVER_SOFTWARE'];
		$temp = M('')->query("SELECT VERSION() as version");
		$sys['mysql'] = $temp[0]['version'];
		$dbsize = 0;
		$temp = M('')->query("SHOW TABLE STATUS LIKE '".C('DB_PREFIX')."%'");
        foreach ($temp as $k) $dbsize += $k['Data_length'] + $k['Index_length'];
		if($dbsize / (1024 * 1024) >= 1) $sys['dbsize'] = round($dbsize / (1024 * 1024), 2).' MB';
		elseif($dbsize / 1024 >= 1) $sys['dbsize'] = round($dbsize / 1024, 2).' KB';
        else $sys['dbsize'] = round($dbsize, 2).' B';
		//统计信息
		$sys['user'] = M('user')->count();
		$sys['link'] = M('link')->count();
		$sys['sort'] = M('sort')->count();
		$sys['feed'] = M('feed')->where("state=0")->count();
		
		$this->assign('sys', $sys);
		$this->display();
	}
	
	//清除缓存
	public function clean_cache() {
		$handle = @opendir(RUNTIME_PATH);
		while($path = @readdir($handle)) {
			if($path == '.' || $path == '..') continue;
			del_path(RUNTIME_PATH.$path, $list);
		}
		echo '<pre>';
		echo '# 清除缓存完成!';
		echo '<br />';
		echo '--------------------------------------------------------------';
		echo '<br />';
		foreach($list as $v) echo $v.'<br />';
		echo '--------------------------------------------------------------';
		echo '</pre>';
	}
	
	//用户列表
	public function user() {
		$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
		if($page < 1) $page = 1;
		$each = 10;
		$offset = ($page - 1) * $each;
		$filter = isset($_GET['filter']) ? @unserialize(base64_decode($_GET['filter'])) : '';
		$map = empty($filter) ? '1' : (array)$filter;
		$list_user = M('user')->where($map)->order("uid ASC")->limit("{$offset},{$each}")->select();
		$url = empty($filter) ? U('Admin/user', array('page' => '__P__')) :
			U('Admin/user', array('filter' => base64_encode(serialize($filter)), 'page' => '__P__'));
		$page_html = pager($page, M('user')->where($map)->count(), $url);
		$this->assign('list_user', $list_user);
		$this->assign('page_html', $page_html);
		$this->display();
	}
	
	//添加用户
	public function user_add() {
		if($_POST) {
			//用户名
			$data['usr'] = isset($_POST['usr']) ? $_POST['usr'] : '';
			if(!preg_match('/^\w{6,20}$/', $data['usr'])) $this->error('用户名格式错误');
			//密码
			$data['pwd'] = isset($_POST['pwd']) ? $_POST['pwd'] : '';
			if(trim($data['pwd']) === '') $this->error('密码不能为空');
			if(strlen($data['pwd']) < 6) $this->error('密码长度不能少于6位');
			$data['pwd'] = md5($data['pwd']);
			//用户昵称
			$data['uname'] = isset($_POST['uname']) ? trim($_POST['uname']) : '';
			if(!preg_match('/^[0-9a-z\-\x{4e00}-\x{9fa5}]+$/u', $data['uname'])) $this->error('昵称格式错误');
			if(str_cut($data['uname'], 20, '') !== $data['uname']) $this->error('昵称最多允许20个字符');
			if(M('user')->where(array('uname' => $data['uname']))->find()) $this->error('昵称已被使用');
			//状态
			$data['state'] = isset($_POST['state']) ? ($_POST['state'] ? 1 : 0) : 0;
			//省市
			$data['area1'] = isset($_POST['area1']) ? $_POST['area1'] : '';
			$data['area2'] = isset($_POST['area2']) ? $_POST['area2'] : '';
			$area = @include_once(COMMON_PATH.'area.php');
			if(!isset($area[$data['area1']]) || !in_array($data['area2'], $area[$data['area1']])) $this->error('省市选择错误');
			//详细地址
			$data['area3'] = isset($_POST['area3']) ? trim($_POST['area3']) : '';
			//电话
			$data['phone'] = isset($_POST['phone']) ? trim($_POST['phone']) : '';
			if(!preg_match('/[0-9-+]+/', $data['phone'])) $this->error('电话包含非法字符');
			//附加信息
			$extra = array();
			if(isset($_POST['extra_0'])) $extra[0] = trim($_POST['extra_0']);
			$data['extra'] = serialize($extra);
			//权限
			$data['lvl'] = 0;
			$data['ctime'] = time();
			if(M('user')->add($data)) $this->success('添加用户成功');
			else $this->error('添加用户失败');
			exit;
		}
		$area = @include_once(COMMON_PATH.'area.php');
		$this->assign('area', $area);
		$this->display();
	}
	
	//编辑用户
	public function user_edit() {
		if($_POST) {
			$uid = isset($_POST['uid']) ? (int)$_POST['uid'] : 0;
			if(!($user = M('user')->where("uid = '{$uid}'")->find())) $this->error('用户不存在');
			$data = array();
			//用户名
			if(isset($_POST['usr'])) {
				$data['usr'] = $_POST['usr'];
				if(!preg_match('/^[0-9a-z]{6,20}$/', $data['usr'])) $this->error('用户名格式错误');
			}
			//密码
			if(isset($_POST['pwd'])) {
				$data['pwd'] = $_POST['pwd'];
				if(trim($data['pwd']) !== '') {
					if(strlen($data['pwd']) < 6) $this->error('密码长度不能少于6位');
					$data['pwd'] = md5($data['pwd']);
				} else {
					unset($data['pwd']);
				}
			}
			//用户昵称
			if(isset($_POST['uname'])) {
				$data['uname'] = trim($_POST['uname']);
				if(!preg_match('/^[0-9a-z\-\x{4e00}-\x{9fa5}]+$/u', $data['uname'])) $this->error('昵称格式错误');
				if(str_cut($data['uname'], 20, '') !== $data['uname']) $this->error('昵称最多允许20个字符');
				$map['uname'] = $data['uname'];
				$map['uid'] = array('NEQ', $uid);
				if(M('user')->where($map)->find()) $this->error('昵称已被使用');
			}
			//状态
			if(isset($_POST['state'])) {
				$data['state'] = $_POST['state'] ? 1 : 0;
			}
			//省市
			if(isset($_POST['area1']) && isset($_POST['area2'])) {
				$data['area1'] = $_POST['area1'];
				$data['area2'] = $_POST['area2'];
				$area = @include_once(COMMON_PATH.'area.php');
				if(!isset($area[$data['area1']]) || !in_array($data['area2'], $area[$data['area1']])) $this->error('省市选择错误');
			}
			//详细地址
			if(isset($_POST['area3'])) {
				$data['area3'] = trim($_POST['area3']);
			}
			//电话
			if(isset($_POST['phone'])) {
				$data['phone'] = trim($_POST['phone']);
				if(!preg_match('/[0-9-+]+/', $data['phone'])) $this->error('电话包含非法字符');
			}
			//附加信息
			if(isset($_POST['extra_0'])) {
				$extra = array();
				$extra[0] = trim($_POST['extra_0']);
				$data['extra'] = serialize($extra);
			}
			M('user')->where("uid = '{$uid}'")->save($data);
			$this->success('用户信息修改成功');
			exit;
		}
		$uid = isset($_GET['uid']) ? (int)$_GET['uid'] : 0;
		$user = M('user')->where("uid = '{$uid}'")->find();
		if(empty($user)) $this->error('用户不存在');
		$area = include_once(COMMON_PATH.'area.php');
		$this->assign('user', $user);
		$this->assign('area', $area);
		$this->display();
	}
	
	//搜索用户
	public function user_search() {
		if($_POST) {
			$map = array();
			if(isset($_POST['uid']) && (int)$_POST['uid']) $map['uid'] = (int)$_POST['uid'];
			if(isset($_POST['usr']) && trim($_POST['usr']) !== '') 
				$map['usr'] = array('LIKE', '%'.trim($_POST['usr']).'%');
			if(isset($_POST['uname']) && trim($_POST['uname']) !== '') 
				$map['uname'] = array('LIKE', '%'.trim($_POST['uname']).'%');
			if(empty($map)) $this->error('请输入搜索信息');
			$filter = base64_encode(serialize($map));
			redirect(U('Admin/user', array('filter' => $filter)));
			exit;
		}
		$this->display();
	}
	
	//删除用户
	public function user_del() {
		$uid = isset($_POST['uid']) ? $_POST['uid'] : '';
		$uid = array_unique(array_map('intval', explode(',', $uid)));
		if(empty($uid) || in_array(0, $uid)) $this->error('请选择删除的选项');
		$in = implode(',', $uid);
		$where = count($uid) == 1 ? "uid = '{$in}'" : "uid IN ($in)";
		$where .= " AND lvl=0";
		if(M('user')->where($where)->delete()) $this->success('删除用户成功');
		else $this->error('删除用户失败');
	}
	
	//分类列表
	public function sort() {
		$list_sort = M('sort')->order("seq ASC, sid ASC")->select();
		$this->assign('list_sort', $list_sort);
		$this->display();
	}
	
	//添加分类
	public function sort_add() {
		if($_POST) {
			$data['title'] = isset($_POST['title']) ? trim($_POST['title']) : '';
			if($data['title'] === '') $this->error('分类名称不能为空');
			$data['ctime'] = time();
			$data['seq'] = 0;
			$sid = M('sort')->add($data);
			if(!$sid) $this->error('添加分类失败');
			M('sort')->where("sid = '{$sid}'")->save(array('seq' => $sid));
			$this->success('添加分类成功');
			exit;
		}
		$this->display();
	}
	
	//编辑分类
	public function sort_edit() {
		if($_POST) {
			$sid = isset($_POST['sid']) ? (int)$_POST['sid'] : 0;
			if(!(M('sort')->where("sid = '{$sid}'")->find())) $this->error('分类不存在');
			$data['title'] = isset($_POST['title']) ? trim($_POST['title']) : '';
			if($data['title'] === '') $this->error('分类名称不能为空');
			M('sort')->where("sid = '{$sid}'")->save($data);
			$this->success('分类信息修改成功');
			exit;
		}
		$sid = isset($_GET['sid']) ? (int)$_GET['sid'] : 0;
		$sort = M('sort')->where("sid = '{$sid}'")->find();
		if(empty($sort)) $this->error('分类不存在');
		$this->assign('sort', $sort);
		$this->display();
	}
	
	//删除分类
	public function sort_del() {
		$sid = isset($_POST['sid']) ? $_POST['sid'] : '';
		$sid = array_unique(array_map('intval', explode(',', $sid)));
		if(empty($sid) || in_array(0, $sid)) $this->error('请选择删除的选项');
		$in = implode(',', $sid);
		$where = count($sid) == 1 ? "sid = '{$in}'" : "sid IN ($in)";
		if(M('sort')->where($where)->delete()) {
			$_where = count($sid) == 1 ? "sort = '{$in}'" : "sort IN ($in)";
			M('link')->where($_where)->delete();
			$this->success('删除分类成功');
		}
		else $this->error('删除分类失败');
	}
	
	//交换分类
	public function sort_exchange() {
		$sid = isset($_POST['sid']) ? $_POST['sid'] : '';
		$sid = array_map('intval', explode(',', $sid));
		if(count($sid) != 2) $this->error('必须选择要交换的两项');
		$sort[0] = M('sort')->where("sid = '{$sid[0]}'")->find();
		$sort[1] = M('sort')->where("sid = '{$sid[1]}'")->find();
		if(empty($sort[0]) || empty($sort[1])) $this->error('分类不存在');
		M('sort')->where("sid = '{$sid[0]}'")->save(array('seq' => $sort[1]['seq']));
		M('sort')->where("sid = '{$sid[1]}'")->save(array('seq' => $sort[0]['seq']));
		$this->success('交换分类完成');
	}
	
	//收藏列表
	public function link() {
		$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
		if($page < 1) $page = 1;
		$each = 10;
		$offset = ($page - 1) * $each;
		$filter = isset($_GET['filter']) ? @unserialize(base64_decode($_GET['filter'])) : '';
		$map = empty($filter) ? '1' : (array)$filter;
		$list_link = M('link')->where($map)->order("lid ASC")->limit("{$offset},{$each}")->select();
		foreach($list_link as &$link) {
			$link['_data']['user'] = M('user')->where("uid = '{$link['uid']}'")->find();
			$link['_data']['sort'] = M('sort')->where("sid = '{$link['sort']}'")->find();
		}
		$url = empty($filter) ? U('Admin/link', array('page' => '__P__')) :
			U('Admin/link', array('filter' => base64_encode(serialize($filter)), 'page' => '__P__'));
		$page_html = pager($page, M('link')->where($map)->count(), $url);
		$this->assign('list_link', $list_link);
		$this->assign('page_html', $page_html);
		$this->display();
	}
	
	//编辑收藏
	public function link_edit() {
		if($_POST) {
			$lid = isset($_POST['lid']) ? (int)$_POST['lid'] : 0;
			if(!(M('link')->where("lid = '{$lid}'")->find())) $this->error('收藏不存在');
			$data = array();
			if(isset($_POST['title'])) {
				$data['title'] = trim($_POST['title']);
				if($data['title'] === '') $this->error('标题不能为空'); 
			}
			if(isset($_POST['url'])) {
				$data['url'] = trim($_POST['url']);
				if($data['url'] === '') $this->error('网址不能为空');
				if(!preg_match('/^(http|https|ftp):\/\//i', $data['url'])) {
					$data['url'] = 'http://'.$data['url'];
				}
			}
			if(isset($_POST['sort'])) {
				$data['sort'] = (int)$_POST['sort'];
			}
			if(isset($_POST['own'])) {
				$data['own'] = $_POST['own'] ? 1 : 0;
			}
			M('link')->where("lid = '{$lid}'")->save($data);
			$this->success('收藏信息保存成功');
			exit;
		}
		$lid = isset($_GET['lid']) ? (int)$_GET['lid'] : 0;
		$link = M('link')->where("lid = '{$lid}'")->find();
		if(empty($link)) $this->error('收藏不存在');
		$list_sort = M('sort')->order("seq ASC,sid ASC")->select();
		$this->assign('link', $link);
		$this->assign('list_sort', $list_sort);
		$this->display();
	}
	
	//搜索收藏
	public function link_search() {
		if($_POST) {
			$map = array();
			if(isset($_POST['lid']) && (int)$_POST['lid']) $map['lid'] = (int)$_POST['lid'];
			if(isset($_POST['uid']) && (int)$_POST['uid']) $map['uid'] = (int)$_POST['uid'];
			if(isset($_POST['sort']) && (int)$_POST['sort']) $map['sort'] = (int)$_POST['sort'];
			if(isset($_POST['title']) && trim($_POST['title']) !== '') 
				$map['title'] = array('LIKE', '%'.trim($_POST['title']).'%');
			
			if(empty($map)) $this->error('请输入搜索信息');
			$filter = base64_encode(serialize($map));
			redirect(U('Admin/link', array('filter' => $filter)));
			exit;
		}
		$this->display();
	}
	
	//删除收藏
	public function link_del() {
		$lid = isset($_POST['lid']) ? $_POST['lid'] : '';
		$lid = array_unique(array_map('intval', explode(',', $lid)));
		if(empty($lid) || in_array(0, $lid)) $this->error('请选择删除的选项');
		$in = implode(',', $lid);
		$where = count($lid) == 1 ? "lid = '{$in}'" : "lid IN ($in)";
		if(M('link')->where($where)->delete()) $this->success('删除收藏成功');
		else $this->error('删除收藏失败');
	}
	
	//留言列表
	public function feed() {
		$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
		if($page < 1) $page = 1;
		$each = 10;
		$offset = ($page - 1) * $each;
		$list_feed = M('feed')->order("fid DESC")->limit("{$offset},{$each}")->select();
		$url = U('Admin/feed', array('page' => '__P__'));
		$page_html = pager($page, M('feed')->count(), $url);
		$this->assign('list_feed', $list_feed);
		$this->assign('page_html', $page_html);
		$this->display();
	}
	
	//查看留言
	public function feed_view() {
		if($_POST) {
			$reply = isset($_POST['reply']) ? trim($_POST['reply']) : '';
			$fid = isset($_POST['fid']) ? (int)$_POST['fid'] : 0;
			if(M('feed')->where("fid = '{$fid}'")->save(array('reply' => $reply))) $this->success('留言回复成功');
			else $this->error('留言回复失败');
			exit;
		}
		$fid = isset($_GET['fid']) ? (int)$_GET['fid'] : 0;
		$feed = M('feed')->where("fid = '{$fid}'")->find();
		if(empty($feed)) $this->error('留言不存在');
		$this->assign('feed', $feed);
		$this->display();
	}
	
	//删除留言
	public function feed_del() {
		$fid = isset($_POST['fid']) ? $_POST['fid'] : '';
		$fid = array_unique(array_map('intval', explode(',', $fid)));
		if(empty($fid) || in_array(0, $fid)) $this->error('请选择删除的选项');
		$in = implode(',', $fid);
		$where = count($fid) == 1 ? "fid = '{$in}'" : "fid IN ($in)";
		if(M('feed')->where($where)->delete()) $this->success('删除留言成功');
		else $this->error('删除留言失败');
	}
	
	//标记留言
	public function feed_mark() {
		$fid = isset($_POST['fid']) ? $_POST['fid'] : '';
		$fid = array_unique(array_map('intval', explode(',', $fid)));
		if(empty($fid) || in_array(0, $fid)) $this->error('请选择标记的选项');
		$in = implode(',', $fid);
		$where = count($fid) == 1 ? "fid = '{$in}'" : "fid IN ($in)";
		$state = isset($_POST['state']) ? ($_POST['state'] ? 1 : 0) : 0;
		M('feed')->where($where)->save(array('state' => $state));
		$this->success('标记留言成功');
	}
	
	//登录历史列表
	public function llog() {
		$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
		if($page < 1) $page = 1;
		$each = 10;
		$offset = ($page - 1) * $each;
		$list_llog = M('login')->order("id DESC")->limit("{$offset},{$each}")->select();
		foreach($list_llog as &$llog) {
			$llog['_data']['user'] = M('user')->where("uid = '{$llog['uid']}'")->find();
		}
		$url = U('Admin/llog', array('page' => '__P__'));
		$page_html = pager($page, M('login')->count(), $url);
		$this->assign('list_llog', $list_llog);
		$this->assign('page_html', $page_html);
		$this->display();
	}
	
	//删除留言
	public function llog_del() {
		$id = isset($_POST['id']) ? $_POST['id'] : '';
		$id = array_unique(array_map('intval', explode(',', $id)));
		if(empty($id) || in_array(0, $id)) $this->error('请选择删除的选项');
		$in = implode(',', $id);
		$where = count($id) == 1 ? "id = '{$in}'" : "id IN ($in)";
		if(M('login')->where($where)->delete()) $this->success('删除记录成功');
		else $this->error('删除记录失败');
	}
}