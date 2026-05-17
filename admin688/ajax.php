<?php
include("../includes/common.php");
if($islogin==1){}else exit("<script language='javascript'>window.location.href='./login.php';</script>");
$act=isset($_GET['act'])?daddslashes($_GET['act']):null;

if(!checkRefererHost())exit('{"code":403}');

if($_SERVER['REQUEST_METHOD']=='POST'){
    if(empty($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']){
        exit('{"code":-1,"msg":"CSRF验证失败，请刷新页面重试"}');
    }
}

@header('Content-Type: application/json; charset=UTF-8');

switch($act){
case 'getcount':
	$thtime=date("Y-m-d").' 00:00:00';
	$count1=$DB->getColumn("SELECT count(*) from pre_order");
	$count2=$DB->getColumn("SELECT count(*) from pre_user");
	$plugincount=$DB->getColumn("SELECT count(*) from pre_plugin");
	if($plugincount<1){
		\lib\Plugin::updateAll();
	}

	$paytype = [];
	$rs = $DB->getAll("SELECT id,name,showname FROM pre_type WHERE status=1");
	foreach($rs as $row){
		$paytype[$row['id']] = $row['showname'];
	}
	unset($rs);

	$channel = [];
	$rs = $DB->getAll("SELECT id,name FROM pre_channel WHERE status=1");
	foreach($rs as $row){
		$channel[$row['id']] = $row['name'];
	}
	unset($rs);

	$tongji_cachetime=getSetting('tongji_cachetime', true);
	$tongji_cache = $CACHE->read('tongji');
	if($tongji_cachetime+3600>=time() && $tongji_cache && !isset($_GET['getnew'])){
		$array = unserialize($tongji_cache);
		$result=["code"=>0,"type"=>"cache","paytype"=>$paytype,"channel"=>$channel,"count1"=>$count1,"count2"=>$count2,"usermoney"=>round($array['usermoney'],2),"settlemoney"=>round($array['settlemoney'],2),"order_today"=>$array['order_today'],"order"=>[]];
	}else{
		$usermoney=$DB->getColumn("SELECT SUM(money) FROM pre_user WHERE money!='0.00'");
		$settlemoney=$DB->getColumn("SELECT SUM(money) FROM pre_settle");

		$today=date("Y-m-d");
		$rs=$DB->query("SELECT type,channel,money from pre_order where status=1 and date>='$today'");
		foreach($paytype as $id=>$type){
			$order_paytype[$id]=0;
		}
		foreach($channel as $id=>$type){
			$order_channel[$id]=0;
		}
		while($row = $rs->fetch())
		{
			$order_paytype[$row['type']]+=$row['money'];
			$order_channel[$row['channel']]+=$row['money'];
		}
		foreach($order_paytype as $k=>$v){
			$order_paytype[$k] = round($v,2);
		}
		foreach($order_channel as $k=>$v){
			$order_channel[$k] = round($v,2);
		}
		$allmoney=0;
		foreach($order_paytype as $order){
			$allmoney+=$order;
		}
		$order_today['all']=round($allmoney,2);
		$order_today['paytype']=$order_paytype;
		$order_today['channel']=$order_channel;

		saveSetting('tongji_cachetime',time());
		$CACHE->save('tongji',serialize(["usermoney"=>$usermoney,"settlemoney"=>$settlemoney,"order_today"=>$order_today]));

		$result=["code"=>0,"type"=>"online","paytype"=>$paytype,"channel"=>$channel,"count1"=>$count1,"count2"=>$count2,"usermoney"=>round($usermoney,2),"settlemoney"=>round($settlemoney,2),"order_today"=>$order_today,"order"=>[]];
	}
	for($i=1;$i<7;$i++){
		$day = date("Ymd", strtotime("-{$i} day"));
		if($order_tongji = $CACHE->read('order_'.$day)){
			$result["order"][$day] = unserialize($order_tongji);
		}else{
			break;
		}
	}
	exit(json_encode($result));
break;

case 'set':
	if(isset($_POST['localurl'])){
		if(!empty($_POST['localurl']) && (substr($_POST['localurl'],0,4)!='http' || substr($_POST['localurl'],-1)!='/'))exit('{"code":-1,"msg":"回调专用网址格式错误"}');
	}
	if(isset($_POST['apiurl'])){
		if(!empty($_POST['apiurl']) && (substr($_POST['apiurl'],0,4)!='http' || substr($_POST['apiurl'],-1)!='/'))exit('{"code":-1,"msg":"用户对接网址格式错误"}');
	}
	if(isset($_POST['login_apiurl'])){
		if(!empty($_POST['login_apiurl']) && (substr($_POST['login_apiurl'],0,4)!='http' || substr($_POST['login_apiurl'],-1)!='/'))exit('{"code":-1,"msg":"聚合登录API接口地址格式错误"}');
	}
	$allowed_keys = ['sitename','title','keywords','description','orgname','kfqq','qqqun','appurl','verifytype',
		'reg_open','user_review','reg_pay','reg_pay_price','reg_pay_uid','user_settings_edit',
		'test_open','test_pay_uid','captcha_id','captcha_key','captcha_open_login',
		'close_keylogin','user_style','cdnpublic','homepage','homepage_url',
		'localurl','apiurl','email',
		'pay_maxmoney','pay_minmoney','blockname','blockalert','ordername','pageordername','notifyordername',
		'forceqq','localurl_alipay','localurl_wxpay','recharge','onecode',
		'pay_payaddstart','pay_payaddmin','pay_payaddmax','pay_domain_open','pay_domain_forbid',
		'alipay_paymode','user_refund','blockips','blockusers',
		'settle_open','settle_type','settle_money','settle_rate','settle_fee_min','settle_fee_max',
		'settle_alipay','settle_wxpay','settle_qqpay','settle_bank',
		'modal','zhuce','footer',
		'transfer_name','transfer_desc','transfer_alipay','transfer_wxpay','transfer_qqpay',
		'cert_open','cert_channel','cert_appcode','cert_qcloudid','cert_qcloudkey',
		'cert_aliyunid','cert_aliyunkey','cert_aliyunsceneid','cert_corpopen','cert_appcode2',
		'cert_force','cert_money',
		'login_qq','login_qq_appid','login_qq_appkey','login_alipay','login_wx',
		'login_apiurl','login_appid','login_appkey',
		'mail_cloud','mail_smtp','mail_port','mail_name','mail_pwd',
		'mail_apiuser','mail_apikey','mail_name2','mail_recv',
		'sms_api','sms_appid','sms_appkey','sms_sign','sms_tpl_reg','sms_tpl_find','sms_tpl_edit','sms_tpl_login',
		'ip_type','template','proxy','proxy_server','proxy_port','proxy_user','proxy_pwd','proxy_type',
		'cronkey','pay_succ_range_minute'];
	foreach($_POST as $k=>$v){
		if(!in_array($k, $allowed_keys)) continue;
		saveSetting($k, $v);
	}
	$ad=$CACHE->clear();
	if($ad)exit('{"code":0,"msg":"succ"}');
	else exit('{"code":-1,"msg":"修改设置失败['.$DB->error().']"}');
break;
case 'setGonggao':
	$id=intval($_GET['id']);
	$status=intval($_GET['status']);
	$sql = "UPDATE pre_anounce SET status='$status' WHERE id='$id'";
	if($DB->exec($sql))exit('{"code":0,"msg":"修改状态成功！"}');
	else exit('{"code":-1,"msg":"修改状态失败['.$DB->error().']"}');
break;
case 'delGonggao':
	$id=intval($_GET['id']);
	$sql = "DELETE FROM pre_anounce WHERE id='$id'";
	if($DB->exec($sql))exit('{"code":0,"msg":"删除公告成功！"}');
	else exit('{"code":-1,"msg":"删除公告失败['.$DB->error().']"}');
break;
case 'iptype':
	$result = [
	['name'=>'0_REMOTE_ADDR', 'ip'=>real_ip(0), 'city'=>get_ip_city(real_ip(0))],
	['name'=>'1_代理模式', 'ip'=>real_ip(1), 'city'=>get_ip_city(real_ip(1))]
	];
	exit(json_encode($result));
break;
case 'alipayQuery':
	$alipay_user_id = isset($_POST['alipay_user_id'])?trim($_POST['alipay_user_id']):exit('{"code":-1,"msg":"支付宝UID不能为空"}');
	$channel = \lib\Channel::get($conf['transfer_alipay']);
	if(!$channel)exit('{"code":-1,"msg":"当前支付通道信息不存在"}');
	define("IN_PLUGIN", true);
	define("PAY_ROOT", PLUGIN_ROOT.'alipay/');
	require_once PAY_ROOT."inc/AlipayTransferService.php";
	$transfer = new AlipayTransferService($config);
	$result = $transfer->accountQuery($alipay_user_id);
	if(!empty($result['code'])&&$result['code'] == 10000){
		$data = ['code'=>0, 'amount'=>$result['available_amount']];
	}else{
		$data = ['code'=>-1, 'msg'=>'['.$result['sub_code'].']'.$result['sub_msg']];
	}
	exit(json_encode($data));
break;
default:
	exit('{"code":-4,"msg":"No Act"}');
break;
}