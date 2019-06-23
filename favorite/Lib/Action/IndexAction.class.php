<?php

/**
 * 前台内容
 * @aauthor wenbin.wu@foxmail.com
 * @copyright 2012
 */

class IndexAction extends Action {
	//用户
	private $_user = null;
	
	//主页导航用户ID
	private $_home_uid = 1;

	//主页导航最大数量
	private $_home_limit = 1000;

	//初始化操作
	public function _initialize() {
		$this->_user = session('user');
		$root = substr(PHP_FILE, 0, -(strlen(end(explode('/', PHP_FILE)))));
		$tpl_url = rtrim($root, '/').APP_TMPL_PATH.'Index/';
		$this->assign('_root', $root);
		$this->assign('_tpl_url', $tpl_url);
		$this->assign('_user', $this->_user);
	}
	
	//前台首页
    public function index() {
		//用户ID
		if(isset($_GET['uid'])) {
			$uid = (int)$_GET['uid'];
			$user = M('user')->where("uid = '{$uid}'")->find();
			if(empty($user)) redirect(U('Index/index'));
			$login = M('login')->where("uid = '{$uid}'")->order("id DESC")->limit(2)->select();
			$user['last_login_ip'] = (count($login) == 2) ? $login[1]['ip'] : null;
		}
		//分类ID
		if(isset($_GET['sid'])) {
			$sid = (int)$_GET['sid'];
			$sort = M('sort')->where("sid = '{$sid}'")->find();
			if(empty($sort)) redirect(U('Index/index'));
		}
		
		//查看类型
		$type = isset($_GET['type']) ? trim($_GET['type']) : '';
		//搜索
		if($type == 'search') {
			$key = isset($_GET['key']) ? trim($_GET['key']) : '';
			if($key === '') redirect(U('Index/index'));
			unset($sid);
		//热门
		} elseif($type == 'hot') {
			$hot = 1;
			unset($uid);
			unset($sid);
			unset($key);
		} else {
			$type = '';
		}
		
		
		$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
		if($page < 1) $page = 1;
		$each = 20;
		$offset = ($page - 1) * $each;

		$is_home = false;
		$order_append = '';
		if (!isset($_GET['uid']) && !isset($_GET['sid']) && !isset($_GET['type'])) {
			$is_home = true;
			$uid = $this->_home_uid;
			$each = $this->_home_limit;
			$offset = 0;
			$order_append = 'a.rank DESC,';
		}
		
		$map['a.own'] = 0;
		if(isset($uid)) {
			$map['a.uid'] = $uid;
			if(!empty($this->_user) && $uid == $this->_user['uid']) unset($map['a.own']);
		}
		if(isset($sid)) $map['a.sort'] = $sid;
		if(isset($key)) {
			$map['title'] = array('LIKE', "%{$key}%");
			unset($sid); unset($map['a.sid']);
		}
		if(isset($hot)) {
			unset($map);
			$map['a.own'] = 0;
			$map['a.cnum'] = array('GT', 0);
		}

		$prefix = C('DB_PREFIX');
		$list_link = M('')->field("a.*, b.uname")->table(array($prefix."link" => "a"))
		->join($prefix."user b ON a.uid = b.uid")->where($map)->order("$order_append a.lid DESC")->limit("{$offset},{$each}")->select();
		
		$count = M('')->table(array($prefix."link" => "a"))->where($map)->count();
		$page_count = ceil($count / $each);
		$page_count = $page_count ? $page_count : 1;
				
		//获取分类列表
		$list_sort = M('sort')->order("seq ASC, sid ASC")->select();
		
		//获取登录用户总收藏数
		if(!empty($this->_user)) {
			$my_count = M('link')->where("uid = '{$this->_user['uid']}'")->count();
		}
		
		//获取总记录数
		$total_count = M('link')->where("own=0")->count();
		
		//获取热门总数
		$hot_count = M('link')->where("cnum>0 AND own=0")->count();

		//获取点击数最多的10条记录
		$click_top_rows = M('link')->order("click desc")->limit(10)->select();
		$click_top_lids = array();
		foreach ($click_top_rows as $row) {
			$click_top_lids[] = $row['lid'];
		}
		
		//地区信息
		$area = @include_once(COMMON_PATH.'area.php');
		
		$this->assign('area', $area);
		
		$this->assign('user', isset($uid) ? $user : null);
		$this->assign('sort', isset($sid) ? $sort : null);
		$this->assign('type', $type);
		$this->assign('key', isset($key) ? $key : null);
		
		$this->assign('list_sort', $list_sort);
		$this->assign('list_link', $list_link);

		$this->assign('count', $count);
		$this->assign('page_count', $page_count);
		$this->assign('page', $page);
		
		$this->assign('total_count', $total_count);
		$this->assign('hot_count', $hot_count);
		$this->assign('my_count', $my_count);
		$this->assign('click_top_lids', $click_top_lids);
		
		$this->assign('is_intact', $this->checkUser());
		$this->assign('is_home', $is_home);
		
		$this->display();
    }
	
	//用户登录[ajax]
	public function login() {
		$map['usr'] = isset($_POST['usr']) ? trim($_POST['usr']) : '';
		$map['pwd'] = isset($_POST['pwd']) ? md5($_POST['pwd']) : '';
		$verify = isset($_POST['verify']) ? $_POST['verify'] : '';
		if(session('verify') != md5($verify) || empty($verify)) $this->error('验证码错误!', true);
		if($usr === '' || $pwd === '') $this->error('用户名或密码错误!', true);
		$user = M('user')->where($map)->find();
		if(empty($user)) $this->error('用户不存在!', true);
		if($user['state']) $this->error('账户被锁定!', true);
		session('user', $user);
		//记录登录信息
		M('login')->add(array('uid' => $user['uid'], 'ip' => get_client_ip(), 'ctime' => time()));
		$this->success('登录成功!', true);
	}
	
	//用户退出
	public function logout() {
		session('user', null);
		redirect(U('Index/index'));
	}
	
	//验证码
	public function verify() {
		import("ORG.Util.Image");
		Image::buildImageVerify(4, 1, 'png', null, 24);
	}
	
	//用户注册[ajax]
	public function register() {
		//禁用注册功能
		$this->error('用户注册已停用!', true);
		$data['usr'] = isset($_POST['usr']) ? trim($_POST['usr']) : '';
		$data['pwd'] = isset($_POST['pwd']) ? trim($_POST['pwd']) : '';
		$verify = isset($_POST['verify']) ? $_POST['verify'] : '';
		if(session('verify') != md5($verify) || empty($verify)) $this->error('验证码错误!', true);
		if(!preg_match('/^[0-9a-z]{6,20}$/', $data['usr'])) $this->error('用户名不得少于6位,且只能是[0-9a-z]!', true);
		if(strlen($data['pwd']) < 6) $this->error('密码长度不得少于6位', true);
		if(M('user')->where(array('usr' => $data['usr']))->find()) $this->error('用户名已被注册!', true);
		$data['pwd'] = md5($data['pwd']);
		$data['lvl'] = 0;
		$data['ctime'] = time();
		$data['state'] = 0;
		$uid = (int)M('user')->add($data);
		$user = M('user')->where("uid = '{$uid}'")->find();
		if(empty($user)) $this->error('用户注册失败!', true);
		else {
			session('user', $user);
			$this->success('用户注册成功!', true);
		}
	}
	
	//检查用户信息是否完整
	private function checkUser() {
		if(empty($this->_user)) return true;
		if(
			$this->_user['uname'] === '' ||
			$this->_user['phone'] === '' ||
			$this->_user['area1'] === '' ||
			$this->_user['area2'] === '' ||
			$this->_user['area3'] === ''
		) return false;
		else return true;
	}
	
	//修改用户信息
	public function setting() {
		if(empty($this->_user)) $this->error('你还未登录!', true);
		$data['uname'] = isset($_POST['uname']) ? trim($_POST['uname']) : '';
		$data['phone'] = isset($_POST['phone']) ? trim($_POST['phone']) : '';
		$data['area1'] = isset($_POST['area1']) ? trim($_POST['area1']) : '';
		$data['area2'] = isset($_POST['area2']) ? trim($_POST['area2']) : '';
		$data['area3'] = isset($_POST['area3']) ? trim($_POST['area3']) : '';
		if(!preg_match('/^[0-9a-z\-\x{4e00}-\x{9fa5}]+$/u', $data['uname'])) $this->error('昵称只能使用这些字符[中文,0-9a-z-]', true);
		if(str_cut($data['uname'], 20, '') !== $data['uname']) $this->error('昵称最多允许20个字符', true);
		if(!preg_match('/^[0-9\-\+]+$/', $data['phone'])) $this->error('电话格式错误!', true);
		$area = @include_once(COMMON_PATH.'area.php');
		if(!in_array($data['area2'], $area[$data['area1']]) || $data['area3'] === '') $this->error('提示：地址信息不能为空!', true);
		if(M('user')->where("uid = '{$this->_user['uid']}'")->save($data)) {
			session('user', M('user')->where("uid = '{$this->_user['uid']}'")->find());
			$this->success('个人资料保存成功!', true);
		}
		else $this->error('个人资料保存失败!', true);
	}
	
	//修改密码
	public function setpwd() {
		if(empty($this->_user)) $this->error('你还未登录!', true);
		$pwd = isset($_POST['pwd']) ? trim($_POST['pwd']) : '';
		$pwd_new = isset($_POST['pwd_new']) ? trim($_POST['pwd_new']) : '';
		if($pwd_new === '') $this->error('请输入新密码!', true);
		$user = M('user')->where("uid = '{$this->_user['uid']}'")->find();
		if($user['pwd'] != md5($pwd)) $this->error('原密码输入错误!', true);
		if(M('user')->where("uid = '{$user['uid']}'")->save(array('pwd' => md5($pwd_new)))) {
			session('user', M('user')->where("uid = '{$user['uid']}'")->find());
			$this->error('修改密码成功!', true);
		} else {
			$this->error('修改密码失败!', true);
		}
	}
	
	//添加用户留言
	public function feed() {
		$data['uname'] = isset($_POST['uname']) ? trim($_POST['uname']) : '';
		$data['phone'] = isset($_POST['phone']) ? trim($_POST['phone']) : '';
		$data['content'] = isset($_POST['content']) ? trim($_POST['content']) : '';
		if($data['uname'] === '') $this->error('姓名不能为空!', true);
		if(!preg_match('/^[0-9\-\+]+$/', $data['phone'])) $this->error('电话格式错误!', true);
		if($data['content'] === '') $this->error('请输入留言内容!', true);
		$data['ctime'] = time();
		$data['uid'] = empty($this->_user) ? 0 : $this->_user['uid'];
		if(M('feed')->add($data)) $this->success('留言发布成功!', true);
		else $this->error('留言发布失败!', true);
	}
	
	//获取单条收藏[ajax]
	public function getLink() {
		if(empty($this->_user)) $this->error('你还未登录!', true);
		$lid = isset($_POST['lid']) ? (int)$_POST['lid'] : 0;
		$link = M('link')->where("lid = '{$lid}'")->find();
		if(empty($link)) $this->error('获取收藏失败!', true);
		else $this->ajaxReturn($link, '获取收藏成功!', 1, 'json');
	}
	
	//查询用户留言
	public function feedSearch() {
		$action = U('Index/feedSearch');
		echo <<<END
<form method="post" action="$action">
	姓名：<input type="text" name="uname" />
	电话：<input type="text" name="phone" />
	<input type="submit" value="查询" />
</form>
END;
		if($_POST) {
			$map['uname'] = isset($_POST['uname']) ? trim($_POST['uname']) : '';
			$map['phone'] = isset($_POST['phone']) ? trim($_POST['phone']) : '';
			$list = M('feed')->where($map)->order("fid DESC")->limit(10)->select();
			foreach($list as $v) {
				echo '<p>姓名：'.$v['uname'].'&nbsp;&nbsp;时间：'.date('Y-m-d H:i', $v['ctime'])
				.'<br />留言内容：'.$v['content'].'<br />回复信息：'.($v['reply'] ? $v['reply'] : '暂无回复!').'</p>';
			}
		}
	}
	
	//添加|编辑|转发收藏[ajax]
	public function link() {
		if(empty($this->_user)) $this->error('你还未登录!', true);
		if(!$this->checkUser()) $this->error('个人资料不完整!', true);
		$type = isset($_POST['type']) ? $_POST['type'] : '';
		$data['title'] = isset($_POST['title']) ? trim($_POST['title']) : '';
		$data['url'] = isset($_POST['url']) ? trim($_POST['url']) : '';
		$data['sort'] = isset($_POST['sort']) ? (int)$_POST['sort'] : 0;
		$data['own'] = isset($_POST['own']) ? ($_POST['own'] ? 1 : 0) : 0;
		$lid = isset($_POST['lid']) ? (int)$_POST['lid'] : 0;
		if($data['title'] === '') $this->error('标题不能为空!', true);
		if($data['url'] === '') $this->error('网址不能为空!', true);
		$data['url'] = preg_replace('/\r|\n/', '', $data['url']);
		if(!preg_match('/^(http|https|ftp):\/\//i', $data['url'])) $data['url'] = 'http://'.$data['url'];
		if($type == 'edit') {
			if(M('link')->where("lid = '{$lid}'")->save($data)) $this->success('编辑收藏成功!', true);
			else $this->success('编辑收藏失败!', true);
		} else {
			$data['uid'] = $this->_user['uid'];
			$data['ctime'] = time();
			if(M('link')->add($data)) {
				if($lid) {
					M('link')->where("lid = '{$lid}'")->setInc('cnum');
					$this->success('转发收藏成功!', true);
				} else {
					$this->success('添加收藏成功!', true);
				}
			} else {
				$this->error('添加收藏失败!', true);
			}
		}
	}
	
	//删除收藏[ajax]
	public function linkDel() {
		if(empty($this->_user)) $this->error('你还未登录!', true);
		if(!$this->checkUser()) $this->error('个人资料不完整!', true);
		
		$lid = isset($_POST['lid']) ? (int)$_POST['lid'] : 0;
		if(M('link')->where("lid = '{$lid}' AND uid = '{$this->_user['uid']}'")->delete())
			$this->success('删除收藏成功!', true);
		else
			$this->error('删除收藏失败!', true);
	}

	//增加点击
	public function linkClickAdd() {
		$lid = isset($_GET['lid']) ? (int)$_GET['lid'] : 0;
		$link = M('link')->where("lid = '{$lid}'")->find();
		if(empty($link)) {
			header("location: ".U("Index/index"));
		} else {
			M('link')->where("lid = '{$lid}'")->setInc('click');
			header("location: ".$link['url']);
		}
	}
}
