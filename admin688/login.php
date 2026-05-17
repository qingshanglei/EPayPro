<?php
/**
 * 登录
**/
$verifycode = 1;//验证码开关

if(!function_exists("imagecreate") || !file_exists('code.php'))$verifycode=0;
include("../includes/common.php");
if(isset($_POST['user']) && isset($_POST['pass'])){
	if(!$_SESSION['pass_error'])$_SESSION['pass_error']=0;
	$user=daddslashes($_POST['user']);
	$pass=daddslashes($_POST['pass']);
	$code=daddslashes($_POST['code']);
	if ($verifycode==1 && (!$code || strtolower($code) != $_SESSION['vc_code'])) {
		unset($_SESSION['vc_code']);
		@header('Content-Type: text/html; charset=UTF-8');
		exit("<script language='javascript'>alert('验证码错误！');history.go(-1);</script>");
	}
	$lock_time = 0;
	if($_SESSION['pass_error']>=10) $lock_time = 3600;
	elseif($_SESSION['pass_error']>=8) $lock_time = 1800;
	elseif($_SESSION['pass_error']>=5) $lock_time = 900;
	elseif($_SESSION['pass_error']>=3) $lock_time = 300;
	if($lock_time > 0 && isset($_SESSION['lock_until']) && time() < $_SESSION['lock_until']){
		$remain = $_SESSION['lock_until'] - time();
		$min = floor($remain / 60);
		$sec = $remain % 60;
		@header('Content-Type: text/html; charset=UTF-8');
		exit("<script language='javascript'>alert('登录已锁定，请{$min}分{$sec}秒后再试');history.go(-1);</script>");
	}
	if($lock_time > 0 && (!isset($_SESSION['lock_until']) || time() >= $_SESSION['lock_until'])){
		$_SESSION['lock_until'] = time() + $lock_time;
		$remain = $lock_time;
		$min = floor($remain / 60);
		$sec = $remain % 60;
		@header('Content-Type: text/html; charset=UTF-8');
		exit("<script language='javascript'>alert('登录错误次数过多，请{$min}分{$sec}秒后再试');history.go(-1);</script>");
	}
	if($user==$conf['admin_user'] && password_verify($pass, $conf['admin_pwd'])) {
		//$city=get_ip_city($clientip);
		$_SESSION['pass_error']=0;
		unset($_SESSION['lock_until']);
		$DB->exec("insert into `pre_log` (`uid`,`type`,`date`,`ip`,`city`) values (0,'登录后台','".$date."','".$clientip."','".$city."')");
		$session=md5($user.$conf['admin_pwd'].$password_hash);
		$expiretime=time()+604800;
		$token=authcode("{$user}\t{$session}\t{$expiretime}", 'ENCODE', SYS_KEY);
		setcookie("admin_token", "", time() - 604800, '/admin688/');
		setcookie("admin_token", $token, time() + 604800, '/', '', (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'), true);
		@header('Content-Type: text/html; charset=UTF-8');
		exit("<script language='javascript'>alert('登陆管理中心成功！');window.location.href='./';</script>");
	}else {
		$_SESSION['pass_error']++;
		$remaining = 3 - $_SESSION['pass_error'];
		if($remaining < 0) $remaining = 0;
		@header('Content-Type: text/html; charset=UTF-8');
		if($_SESSION['pass_error'] < 3){
			exit("<script language='javascript'>alert('用户名或密码不正确！您还可以尝试{$remaining}次');history.go(-1);</script>");
		}else{
			exit("<script language='javascript'>alert('用户名或密码不正确！');history.go(-1);</script>");
		}
	}
}elseif(isset($_GET['logout'])){
	if(!checkRefererHost())exit();
	setcookie("admin_token", "", time() - 604800, '/');
	setcookie("admin_token", "", time() - 604800, '/admin688/');
	unset($_COOKIE['admin_token']);
	@header('Content-Type: text/html; charset=UTF-8');
	exit("<script language='javascript'>alert('您已成功注销本次登陆！');window.location.href='./login.php';</script>");
}elseif($islogin==1){
	exit("<script language='javascript'>alert('您已登陆！');window.location.href='./';</script>");
}
$title='用户登录';
include './head.php';
?>
  <nav class="navbar navbar-fixed-top navbar-default">
    <div class="container">
      <div class="navbar-header">
        <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar" aria-expanded="false" aria-controls="navbar">
          <span class="sr-only">导航按钮</span>
          <span class="icon-bar"></span>
          <span class="icon-bar"></span>
          <span class="icon-bar"></span>
        </button>
        <a class="navbar-brand" href="./">彩虹易支付管理中心</a>
      </div><!-- /.navbar-header -->
      <div id="navbar" class="collapse navbar-collapse">
        <ul class="nav navbar-nav navbar-right">
          <li class="active">
            <a href="./login.php"><span class="glyphicon glyphicon-user"></span> 登陆</a>
          </li>
        </ul>
      </div><!-- /.navbar-collapse -->
    </div><!-- /.container -->
  </nav><!-- /.navbar -->
  <div class="container" style="padding-top:70px;">
    <div class="col-xs-12 col-sm-10 col-md-8 col-lg-6 center-block" style="float: none;">
      <div class="panel panel-primary">
        <div class="panel-heading"><h3 class="panel-title">用户登陆</h3></div>
        <div class="panel-body">
          <form action="./login.php" method="post" class="form-horizontal" role="form">
            <div class="input-group">
              <span class="input-group-addon"><span class="glyphicon glyphicon-user"></span></span>
              <input type="text" name="user" value="<?php echo htmlspecialchars(@$_POST['user'], ENT_QUOTES, 'UTF-8');?>" class="form-control input-lg" placeholder="用户名" required="required"/>
            </div><br/>
            <div class="input-group">
              <span class="input-group-addon"><span class="glyphicon glyphicon-lock"></span></span>
              <input type="password" name="pass" class="form-control input-lg" placeholder="密码" required="required"/>
            </div><br/>
			<?php if($verifycode==1){?>
			<div class="input-group">
				<span class="input-group-addon"><span class="glyphicon glyphicon-adjust"></span></span>
				<input type="text" class="form-control input-lg" name="code" placeholder="输入验证码" autocomplete="off" required>
				<span class="input-group-addon" style="padding: 0">
					<img src="./code.php?r=<?php echo time();?>"height="45"onclick="this.src='./code.php?r='+Math.random();" title="点击更换验证码">
				</span>
			</div><br/>
			<?php }?>
            <div class="form-group">
              <div class="col-xs-12"><input type="submit" value="登陆" class="btn btn-primary btn-block btn-lg"/></div>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>