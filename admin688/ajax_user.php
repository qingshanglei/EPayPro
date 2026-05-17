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
case 'userList':
	$usergroup = [0=>'默认用户组'];
	$rs = $DB->getAll("SELECT * FROM pre_group");
	foreach($rs as $row){
		$usergroup[$row['gid']] = $row['name'];
	}
	unset($rs);

	$sql=" 1=1";
	$params = [];
	if(isset($_POST['dstatus']) && !empty($_POST['dstatus'])) {
		$dstatus = explode('_',$_POST['dstatus']);
		$allowed_dstatus = ['status','pay','settle','cert','mode'];
		if(!in_array($dstatus[0], $allowed_dstatus)) exit('{"code":-1,"msg":"非法参数"}');
		$sql.=" AND `{$dstatus[0]}`=?";
		$params[] = $dstatus[1];
	}
	if(isset($_POST['gid']) && $_POST['gid']!=='') {
		$gid = intval($_POST['gid']);
		$sql.=" AND `gid`='$gid'";
	}
	if(isset($_POST['upid']) && $_POST['upid']!=='') {
		$upid = intval($_POST['upid']);
		$sql.=" AND `upid`='$upid'";
	}
	if(isset($_POST['value']) && !empty($_POST['value'])) {
		$allowed_columns = ['uid','username','account','email','phone','qq','url','domain','gid','status','pay','settle','cert','mode'];
		if(!in_array($_POST['column'], $allowed_columns)) exit('{"code":-1,"msg":"非法参数"}');
		$sql.=" AND `{$_POST['column']}`=?";
		$params[] = $_POST['value'];
	}
	$offset = intval($_POST['offset']);
	$limit = intval($_POST['limit']);
	$total = $DB->getColumn("SELECT count(*) from pre_user WHERE{$sql}", $params);
	$list = $DB->getAll("SELECT * FROM pre_user WHERE{$sql} order by uid desc limit $offset,$limit", $params);
	$list2 = [];
	foreach($list as $row){
		if($row['endtime']!=null && strtotime($row['endtime'])<time()){
			$DB->exec("UPDATE pre_user SET gid=0,endtime=NULL WHERE uid='{$row['uid']}'");
			$row['gid']=0;
		}elseif($row['endtime']!=null){
			$row['endtime'] = date("Y-m-d", strtotime($row['endtime']));
		}
		$row['groupname'] = $usergroup[$row['gid']];
		$list2[] = $row;
	}

	exit(json_encode(['total'=>$total, 'rows'=>$list2]));
break;

case 'recordList':
	$sql=" 1=1";
	$params = [];
	if(isset($_POST['value']) && !empty($_POST['value'])) {
		$allowed_columns = ['uid','type','money','trade_no','addtime'];
		if(!in_array($_POST['column'], $allowed_columns)) exit('{"code":-1,"msg":"非法参数"}');
		$sql.=" AND `{$_POST['column']}`=?";
		$params[] = $_POST['value'];
	}
	$offset = intval($_POST['offset']);
	$limit = intval($_POST['limit']);
	$total = $DB->getColumn("SELECT count(*) from pre_record WHERE{$sql}", $params);
	$list = $DB->getAll("SELECT * FROM pre_record WHERE{$sql} order by id desc limit $offset,$limit", $params);

	exit(json_encode(['total'=>$total, 'rows'=>$list]));
break;

case 'userPayStat':
	$day = trim($_POST['day']);
	$method = trim($_POST['method']);
	if(!$day)exit(json_encode(['code'=>0, 'msg'=>'no day']));
	$starttime = date("Y-m-d H:i:s", strtotime($day));
	$endtime = date("Y-m-d H:i:s", strtotime($day) + 3600 * 24);
	$data = [];
	$columns = ['uid'=>'商户ID', 'total'=>'总计'];

	if($method == 'type'){
		$paytype = [];
		$rs = $DB->getAll("SELECT id,name,showname FROM pre_type WHERE status=1");
		foreach($rs as $row){
			$paytype[$row['id']] = $row['showname'];
			$columns['type_'.$row['id']] = $row['showname'];
		}
		unset($rs);
	}else{
		$channel = [];
		$rs = $DB->getAll("SELECT id,name FROM pre_channel WHERE status=1");
		foreach($rs as $row){
			$channel[$row['id']] = $row['name'];
		}
		unset($rs);
	}

	$rs=$DB->query("SELECT uid,type,channel,money from pre_order where status=1 and date='$day'");
	while($row = $rs->fetch())
	{
		$money = (float)$row['money'];
		if(!array_key_exists($row['uid'], $data)) $data[$row['uid']] = ['uid'=>$row['uid'], 'total'=>0];
		$data[$row['uid']]['total'] += $money;
		if($method == 'type'){
			$ukey = 'type_'.$row['type'];
			if(!array_key_exists($ukey, $data[$row['uid']])) $data[$row['uid']][$ukey] = $money;
			else $data[$row['uid']][$ukey] += $money;
		}else{
			$ukey = 'channel_'.$row['channel'];
			if(!array_key_exists($ukey, $data[$row['uid']])) $data[$row['uid']][$ukey] = $money;
			else $data[$row['uid']][$ukey] += $money;
			if(!in_array($ukey, $columns)) $columns[$ukey] = $channel[$row['channel']];
		}
	}
	ksort($data);
	$list = [];
	foreach($data as $row){
		$list[] = $row;
	}
	exit(json_encode(['code'=>0, 'columns'=>$columns, 'data'=>$list]));
break;

case 'logList':
	$sql=" 1=1";
	$params = [];
	if(isset($_POST['value']) && $_POST['value']!=='') {
		$allowed_columns = ['uid','type','action','addtime','ip'];
		if(!in_array($_POST['column'], $allowed_columns)) exit('{"code":-1,"msg":"非法参数"}');
		$sql.=" AND `{$_POST['column']}`=?";
		$params[] = $_POST['value'];
	}
	$offset = intval($_POST['offset']);
	$limit = intval($_POST['limit']);
	$total = $DB->getColumn("SELECT count(*) from pre_log WHERE{$sql}", $params);
	$list = $DB->getAll("SELECT * FROM pre_log WHERE{$sql} order by id desc limit $offset,$limit", $params);

	exit(json_encode(['total'=>$total, 'rows'=>$list]));
break;

case 'domainList':
	$sql=" 1=1";
	$params = [];
	if(isset($_POST['uid']) && !empty($_POST['uid'])) {
		$uid = intval($_POST['uid']);
		$sql.=" AND `uid`='$uid'";
	}
	if(isset($_POST['kw']) && !empty($_POST['kw'])) {
		$kw = trim($_POST['kw']);
		$sql.=" AND `domain`=?";
		$params[] = $kw;
	}
	if(isset($_POST['dstatus']) && $_POST['dstatus']>-1) {
		$dstatus = intval($_POST['dstatus']);
		$sql.=" AND `status`={$dstatus}";
	}
	$offset = intval($_POST['offset']);
	$limit = intval($_POST['limit']);
	$total = $DB->getColumn("SELECT count(*) from pre_domain WHERE{$sql}", $params);
	$list = $DB->getAll("SELECT * FROM pre_domain WHERE{$sql} order by id desc limit $offset,$limit", $params);

	exit(json_encode(['total'=>$total, 'rows'=>$list]));
break;

case 'getGroup':
	$gid=intval($_GET['gid']);
	$row=$DB->getRow("select * from pre_group where gid='$gid' limit 1");
	if(!$row)
		exit('{"code":-1,"msg":"当前用户组不存在！"}');
	$result = ['code'=>0,'msg'=>'succ','gid'=>$gid,'name'=>$row['name'],'info'=>json_decode($row['info'],true),'settle_open'=>$row['settle_open'],'settle_type'=>$row['settle_type'],'settings'=>$row['settings']];
	exit(json_encode($result));
break;
case 'delGroup':
	$gid=intval($_GET['gid']);
	$row=$DB->getRow("select * from pre_group where gid='$gid' limit 1");
	if(!$row)
		exit('{"code":-1,"msg":"当前用户组不存在！"}');
	$sql = "DELETE FROM pre_group WHERE gid='$gid'";
	if($DB->exec($sql))exit('{"code":0,"msg":"删除用户组成功！"}');
	else exit('{"code":-1,"msg":"删除用户组失败"}');
break;
case 'saveGroup':
	if($_POST['action'] == 'add'){
		$name=trim($_POST['name']);
		$row=$DB->getRow("select * from pre_group where name='$name' limit 1");
		if($row)
			exit('{"code":-1,"msg":"用户组名称重复"}');
		$info=$_POST['info'];
		$info=json_encode($info);
		$settle_open=intval($_POST['settle_open']);
		$settle_type=intval($_POST['settle_type']);
		$settings=trim($_POST['settings']);
		if($settings && !checkGroupSettings($settings))exit('{"code":-1,"msg":"用户变量格式不正确"}');
		$sql = "INSERT INTO pre_group (name, info, settle_open, settle_type, settings) VALUES ('{$name}', '{$info}', '{$settle_open}', '{$settle_type}', '{$settings}')";
		if($DB->exec($sql))exit('{"code":0,"msg":"新增用户组成功！"}');
		else exit('{"code":-1,"msg":"新增用户组失败"}');
	}elseif($_POST['action'] == 'changebuy'){
		$gid=intval($_POST['gid']);
		$status=intval($_POST['status']);
		$sql = "UPDATE pre_group SET isbuy='{$status}' WHERE gid='$gid'";
		if($DB->exec($sql))exit('{"code":0,"msg":"修改上架状态成功！"}');
		else exit('{"code":-1,"msg":"修改上架状态失败"}');
	}else{
		$gid=intval($_POST['gid']);
		$name=trim($_POST['name']);
		$row=$DB->getRow("select * from pre_group where name='$name' and gid<>$gid limit 1");
		if($row)
			exit('{"code":-1,"msg":"用户组名称重复"}');
		$info=$_POST['info'];
		$info=json_encode($info);
		$settle_open=intval($_POST['settle_open']);
		$settle_type=intval($_POST['settle_type']);
		$settings=trim($_POST['settings']);
		if($settings && !checkGroupSettings($settings))exit('{"code":-1,"msg":"用户变量格式不正确"}');
		$sql = "UPDATE pre_group SET name='{$name}',info='{$info}',settle_open='{$settle_open}',settle_type='{$settle_type}',settings='{$settings}' WHERE gid='$gid'";
		if($DB->exec($sql)!==false)exit('{"code":0,"msg":"修改用户组成功！"}');
		else exit('{"code":-1,"msg":"修改用户组失败"}');
	}
break;
case 'saveGroupPrice':
	$prices = $_POST['price'];
	$expires = $_POST['expire'];
	$sorts = $_POST['sort'];
	foreach($prices as $gid=>$item){
		$price = trim($item);
		$expire = intval($expires[$gid]);
		$sort = trim($sorts[$gid]);
		if(empty($price)||!is_numeric($price))exit('{"code":-1,"msg":"GID:'.$gid.'的售价填写错误"}');
		$DB->exec("UPDATE pre_group SET price='{$price}',expire='{$expire}',sort='{$sort}' WHERE gid='$gid'");
	}
	exit('{"code":0,"msg":"保存成功！"}');
break;
case 'setUser':
	$uid=intval($_POST['uid']);
	$type=trim($_POST['type']);
	$status=intval($_POST['status']);
	if($type=='pay')$sql = "UPDATE pre_user SET pay='$status' WHERE uid='$uid'";
	elseif($type=='settle')$sql = "UPDATE pre_user SET settle='$status' WHERE uid='$uid'";
	elseif($type=='group')$sql = "UPDATE pre_user SET gid='$status' WHERE uid='$uid'";
	else $sql = "UPDATE pre_user SET status='$status' WHERE uid='$uid'";
	if($DB->exec($sql)!==false)exit('{"code":0,"msg":"修改用户成功！"}');
	else exit('{"code":-1,"msg":"修改用户失败"}');
break;
case 'setUserGroup':
	$uid=intval($_POST['uid']);
	$gid=intval($_POST['gid']);
	$endtime=trim($_POST['endtime']);
	if(changeUserGroup($uid, $gid, $endtime)!==false)exit('{"code":0,"msg":"修改用户成功！"}');
	else exit('{"code":-1,"msg":"修改用户失败"}');
break;
case 'resetUser':
	$uid=intval($_GET['uid']);
	$key = random(32);
	$sql = "UPDATE pre_user SET `key`='$key' WHERE uid='$uid'";
	if($DB->exec($sql)!==false)exit('{"code":0,"msg":"重置密钥成功","key":"'.$key.'"}');
	else exit('{"code":-1,"msg":"重置密钥失败"}');
break;
case 'user_settle_info':
	$uid=intval($_GET['uid']);
	$rows=$DB->getRow("select * from pre_user where uid='$uid' limit 1");
	if(!$rows)
		exit('{"code":-1,"msg":"当前用户不存在！"}');
	$data = '<div class="form-group"><div class="input-group"><div class="input-group-addon">结算方式</div><select class="form-control" id="pay_type" default="'.$rows['settle_id'].'">'.($conf['settle_alipay']?'<option value="1">支付宝</option>':null).''.($conf['settle_wxpay']?'<option value="2">微信</option>':null).''.($conf['settle_qqpay']?'<option value="3">QQ钱包</option>':null).''.($conf['settle_bank']?'<option value="4">银行卡</option>':null).'</select></div></div>';
	$data .= '<div class="form-group"><div class="input-group"><div class="input-group-addon">结算账号</div><input type="text" id="pay_account" value="'.htmlspecialchars($rows['account'], ENT_QUOTES, 'UTF-8').'" class="form-control" required/></div></div>';
	$data .= '<div class="form-group"><div class="input-group"><div class="input-group-addon">真实姓名</div><input type="text" id="pay_name" value="'.htmlspecialchars($rows['username'], ENT_QUOTES, 'UTF-8').'" class="form-control" required/></div></div>';
	$data .= '<input type="submit" id="save" onclick="saveInfo('.$uid.')" class="btn btn-primary btn-block" value="保存">';
	$result=array("code"=>0,"msg"=>"succ","data"=>$data,"pay_type"=>$rows['settle_id']);
	exit(json_encode($result));
break;
case 'user_settle_save':
	$uid=intval($_POST['uid']);
	$pay_type=trim(daddslashes($_POST['pay_type']));
	$pay_account=trim(daddslashes($_POST['pay_account']));
	$pay_name=trim(daddslashes($_POST['pay_name']));
	$sds=$DB->exec("update `pre_user` set `settle_id`='$pay_type',`account`='$pay_account',`username`='$pay_name' where `uid`='$uid'");
	if($sds!==false)
		exit('{"code":0,"msg":"修改记录成功！"}');
	else
		exit('{"code":-1,"msg":"数据库操作失败"}');
break;
case 'user_cert':
	$uid=intval($_GET['uid']);
	$rows=$DB->getRow("select cert,certtype,certmethod,certno,certname,certcorpno,certcorpname,certtime from pre_user where uid='$uid' limit 1");
	if(!$rows)
		exit('{"code":-1,"msg":"当前用户不存在！"}');
	$rows['certmethodname'] = show_cert_method($rows['certmethod']);
	$result = ['code'=>0,'msg'=>'succ','uid'=>$uid,'data'=>$rows];
	exit(json_encode($result));
break;
case 'recharge':
	$uid=intval($_POST['uid']);
	$do=$_POST['actdo'];
	$rmb=floatval($_POST['rmb']);
	$row=$DB->getRow("select uid,money from pre_user where uid='$uid' limit 1");
	if(!$row)
		exit('{"code":-1,"msg":"当前用户不存在！"}');
	if($do==1 && $rmb>$row['money'])$rmb=$row['money'];
	if($do==0){
		changeUserMoney($uid, $rmb, true, '后台加款');
	}else{
		changeUserMoney($uid, $rmb, false, '后台扣款');
	}
	exit('{"code":0,"msg":"succ"}');
break;

case 'addDomain':
	$uid=intval($_POST['uid']);
	$domain = trim(daddslashes($_POST['domain']));
	if(empty($domain))exit('{"code":-1,"msg":"域名不能为空"}');
	if(!checkDomain($domain))exit('{"code":-1,"msg":"域名格式不正确"}');
	$row=$DB->getRow("select uid from pre_user where uid='$uid' limit 1");
	if(!$row)
		exit('{"code":-1,"msg":"当前用户不存在！"}');
	if($DB->getRow("select * from pre_domain where uid=:uid and domain=:domain limit 1", [':uid'=>$uid, ':domain'=>$domain]))
		exit('{"code":-1,"msg":"该域名已存在，请勿重复添加"}');
	if(!$DB->exec("INSERT INTO `pre_domain` (`uid`,`domain`,`status`,`addtime`,`endtime`) VALUES (:uid, :domain, 1, NOW(), NOW())", [':uid'=>$uid, ':domain'=>$domain]))exit('{"code":-1,"msg":"添加域名失败"}');
	exit(json_encode(['code'=>0, 'msg'=>'添加域名成功！']));
break;
case 'setDomainStatus':
	$id=intval($_POST['id']);
	$status=intval($_POST['status']);
	if($DB->exec("UPDATE pre_domain SET status='$status',endtime=NOW() WHERE id='$id'")!==false)exit('{"code":0,"msg":"succ"}');
	else exit('{"code":-1,"msg":"修改失败"}');
break;
case 'delDomain':
	$id=intval($_POST['id']);
	if($DB->exec("DELETE FROM pre_domain WHERE id='$id'")!==false)exit('{"code":0,"msg":"succ"}');
	else exit('{"code":-1,"msg":"删除失败"}');
break;
default:
	exit('{"code":-4,"msg":"No Act"}');
break;
}
