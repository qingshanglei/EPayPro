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
case 'getPayType':
	$id=intval($_GET['id']);
	$row=$DB->getRow("select * from pre_type where id='$id' limit 1");
	if(!$row)
		exit('{"code":-1,"msg":"当前支付方式不存在！"}');
	$result = ['code'=>0,'msg'=>'succ','data'=>$row];
	exit(json_encode($result));
break;
case 'setPayType':
	$id=intval($_GET['id']);
	$status=intval($_GET['status']);
	$row=$DB->getRow("select * from pre_type where id='$id' limit 1");
	if(!$row)
		exit('{"code":-1,"msg":"当前支付方式不存在！"}');
	$sql = "UPDATE pre_type SET status='$status' WHERE id='$id'";
	if($DB->exec($sql))exit('{"code":0,"msg":"修改支付方式成功！"}');
	else exit('{"code":-1,"msg":"修改支付方式失败"}');
break;
case 'delPayType':
	$id=intval($_GET['id']);
	$row=$DB->getRow("select * from pre_type where id='$id' limit 1");
	if(!$row)
		exit('{"code":-1,"msg":"当前支付方式不存在！"}');
	$row=$DB->getRow("select * from pre_channel where type='$id' limit 1");
	if($row)
		exit('{"code":-1,"msg":"删除失败，存在使用该支付方式的支付通道"}');
	$sql = "DELETE FROM pre_type WHERE id='$id'";
	if($DB->exec($sql))exit('{"code":0,"msg":"删除支付方式成功！"}');
	else exit('{"code":-1,"msg":"删除支付方式失败"}');
break;
case 'savePayType':
	if($_POST['action'] == 'add'){
		$name=trim($_POST['name']);
		$showname=trim($_POST['showname']);
		$device=intval($_POST['device']);
		if(!preg_match('/^[a-zA-Z0-9]+$/',$name)){
			exit('{"code":-1,"msg":"调用值不符合规则"}');
		}
		$row=$DB->getRow("select * from pre_type where name=? and device=? limit 1", [$name, $device]);
		if($row)
			exit('{"code":-1,"msg":"同一个调用值+支持设备不能重复"}');
		$sql = "INSERT INTO pre_type (name, showname, device, status) VALUES (?,?,?,1)";
		if($DB->exec($sql, [$name, $showname, $device]))exit('{"code":0,"msg":"新增支付方式成功！"}');
		else exit('{"code":-1,"msg":"新增支付方式失败"}');
	}else{
		$id=intval($_POST['id']);
		$name=trim($_POST['name']);
		$showname=trim($_POST['showname']);
		$device=intval($_POST['device']);
		if(!preg_match('/^[a-zA-Z0-9]+$/',$name)){
			exit('{"code":-1,"msg":"调用值不符合规则"}');
		}
		$row=$DB->getRow("select * from pre_type where name=? and device=? and id<>? limit 1", [$name, $device, $id]);
		if($row)
			exit('{"code":-1,"msg":"同一个调用值+支持设备不能重复"}');
		$sql = "UPDATE pre_type SET name=?,showname=?,device=? WHERE id=?";
		if($DB->exec($sql, [$name, $showname, $device, $id])!==false)exit('{"code":0,"msg":"修改支付方式成功！"}');
		else exit('{"code":-1,"msg":"修改支付方式失败"}');
	}
break;
case 'getPlugin':
	$name = trim($_GET['name']);
	$row=$DB->getRow("SELECT * FROM pre_plugin WHERE name='$name'");
	if($row){
		$result = ['code'=>0,'msg'=>'succ','data'=>$row];
		exit(json_encode($result));
	}
	else exit('{"code":-1,"msg":"当前支付插件不存在！"}');
break;
case 'getPlugins':
	$typeid = intval($_GET['typeid']);
	$type=$DB->getColumn("SELECT name FROM pre_type WHERE id='$typeid'");
	if(!$type)
		exit('{"code":-1,"msg":"当前支付方式不存在！"}');
	$list=$DB->getAll("SELECT name,showname FROM pre_plugin WHERE types LIKE '%$type%' ORDER BY name ASC");
	if($list){
		$result = ['code'=>0,'msg'=>'succ','data'=>$list];
		exit(json_encode($result));
	}
	else exit('{"code":-1,"msg":"没有找到支持该支付方式的插件"}');
break;
case 'getChannel':
	$id=intval($_GET['id']);
	$row=$DB->getRow("SELECT * FROM pre_channel WHERE id='$id'");
	if(!$row)
		exit('{"code":-1,"msg":"当前支付通道不存在！"}');
	$result = ['code'=>0,'msg'=>'succ','data'=>$row];
	exit(json_encode($result));
break;
case 'getChannels':
	$typeid = intval($_GET['typeid']);
	$type=$DB->getColumn("SELECT name FROM pre_type WHERE id='$typeid'");
	if(!$type)
		exit('{"code":-1,"msg":"当前支付方式不存在！"}');
	$list=$DB->getAll("SELECT id,name FROM pre_channel WHERE type='$typeid' and status=1 ORDER BY id ASC");
	if($list){
		$result = ['code'=>0,'msg'=>'succ','data'=>$list];
		exit(json_encode($result));
	}
	else exit('{"code":-1,"msg":"没有找到支持该支付方式的通道"}');
break;
case 'setChannel':
	$id=intval($_GET['id']);
	$status=intval($_GET['status']);
	$row=$DB->getRow("SELECT * FROM pre_channel WHERE id='$id'");
	if(!$row)
		exit('{"code":-1,"msg":"当前支付通道不存在！"}');
	if($status==1 && (empty($row['appid']) || empty($row['appkey']) && empty($row['appsecret']))){
		exit('{"code":-1,"msg":"请先配置好密钥后再开启"}');
	}
	$sql = "UPDATE pre_channel SET status='$status' WHERE id='$id'";
	if($DB->exec($sql))exit('{"code":0,"msg":"修改支付通道成功！"}');
	else exit('{"code":-1,"msg":"修改支付通道失败"}');
break;
case 'delChannel':
	$id=intval($_GET['id']);
	$row=$DB->getRow("SELECT * FROM pre_channel WHERE id='$id'");
	if(!$row)
		exit('{"code":-1,"msg":"当前支付通道不存在！"}');
	$sql = "DELETE FROM pre_channel WHERE id='$id'";
	if($DB->exec($sql))exit('{"code":0,"msg":"删除支付通道成功！"}');
	else exit('{"code":-1,"msg":"删除支付通道失败"}');
break;
case 'saveChannel':
	if($_POST['action'] == 'add'){
		$name=trim($_POST['name']);
		$rate=trim($_POST['rate']);
		$type=intval($_POST['type']);
		$plugin=trim($_POST['plugin']);
		$daytop=intval($_POST['daytop']);
		$mode=intval($_POST['mode']);
		$paymin=trim($_POST['paymin']);
		$paymax=trim($_POST['paymax']);
		if(!preg_match('/^[0-9.]+$/',$rate)){
			exit('{"code":-1,"msg":"分成比例不符合规则"}');
		}
		if($paymin && !preg_match('/^[0-9.]+$/',$paymin)){
			exit('{"code":-1,"msg":"最小支付金额不符合规则"}');
		}
		if($paymax && !preg_match('/^[0-9.]+$/',$paymax)){
			exit('{"code":-1,"msg":"最大支付金额不符合规则"}');
		}
		$row=$DB->getRow("SELECT * FROM pre_channel WHERE name=? LIMIT 1", [$name]);
		if($row)
			exit('{"code":-1,"msg":"支付通道名称重复"}');
		$sql = "INSERT INTO pre_channel (name, rate, mode, type, plugin, daytop, paymin, paymax) VALUES (?,?,?,?,?,?,?,?)";
		if($DB->exec($sql, [$name, $rate, $mode, $type, $plugin, $daytop, $paymin, $paymax]))exit('{"code":0,"msg":"新增支付通道成功！"}');
		else exit('{"code":-1,"msg":"新增支付通道失败"}');
	}else{
		$id=intval($_POST['id']);
		$row=$DB->getRow("SELECT * FROM pre_channel WHERE id='$id'");
		if(!$row) exit('{"code":-1,"msg":"当前支付通道不存在！"}');
		$name=trim($_POST['name']);
		$rate=trim($_POST['rate']);
		$type=intval($_POST['type']);
		$plugin=trim($_POST['plugin']);
		$daytop=intval($_POST['daytop']);
		$mode=intval($_POST['mode']);
		$paymin=trim($_POST['paymin']);
		$paymax=trim($_POST['paymax']);
		if(!preg_match('/^[0-9.]+$/',$rate)){
			exit('{"code":-1,"msg":"分成比例不符合规则"}');
		}
		if($paymin && !preg_match('/^[0-9.]+$/',$paymin)){
			exit('{"code":-1,"msg":"最小支付金额不符合规则"}');
		}
		if($paymax && !preg_match('/^[0-9.]+$/',$paymax)){
			exit('{"code":-1,"msg":"最大支付金额不符合规则"}');
		}
		$nrow=$DB->getRow("SELECT * FROM pre_channel WHERE name=? AND id<>? LIMIT 1", [$name, $id]);
		if($nrow)
			exit('{"code":-1,"msg":"支付通道名称重复"}');
		$sql = "UPDATE pre_channel SET name=?,rate=?,type=?,plugin=?,daytop=?,mode=?,paymin=?,paymax=? WHERE id=?";
		if($DB->exec($sql, [$name, $rate, $type, $plugin, $daytop, $mode, $paymin, $paymax, $id])!==false){
			if($row['daystatus']==1 && ($daytop==0 || $daytop>$row['daytop'])){
				$DB->exec("UPDATE pre_channel SET daystatus=0 WHERE id='$id'");
			}
			exit('{"code":0,"msg":"修改支付通道成功！"}');
		}else exit('{"code":-1,"msg":"修改支付通道失败"}');
	}
break;
case 'channelInfo':
	$id=intval($_GET['id']);
	$row=$DB->getRow("SELECT * FROM pre_channel WHERE id='$id'");
	if(!$row)
		exit('{"code":-1,"msg":"当前支付通道不存在！"}');
	$typename = $DB->getColumn("SELECT name FROM pre_type WHERE id='{$row['type']}'");
	$apptype = explode(',',$row['apptype']);
	$plugin = \lib\Plugin::getConfig($row['plugin']);
	if(!$plugin)
		exit('{"code":-1,"msg":"当前支付插件不存在！"}');

	$data = '<div class="modal-body"><form class="form" id="form-info">';
	$select_list = [];
	if(!empty($plugin['select_'.$typename])){
		$select_list = $plugin['select_'.$typename];
	}
	elseif(!empty($plugin['select'])){
		$select_list = $plugin['select'];
	}
	if(count($select_list) > 0){
		$select = '';
		foreach($select_list as $key=>$input){
			$select .= '<label><input type="checkbox" '.(in_array($key,$apptype)?'checked':null).' name="apptype[]" value="'.$key.'">'.$input.'</label>&nbsp;';
		}
		$data .= '<div class="form-group"><input type="hidden" id="isapptype" name="isapptype" value="1"/><label>请选择可用的接口：</label><br/><div class="checkbox">'.$select.'</div></div>';
	}
	foreach($plugin['inputs'] as $key=>$input){
		if($input['type'] == 'textarea'){
			$data .= '<div class="form-group"><label>'.$input['name'].'：</label><br/><textarea id="'.$key.'" name="'.$key.'" rows="2" class="form-control" placeholder="'.$input['note'].'">'.htmlspecialchars($row[$key], ENT_QUOTES, 'UTF-8').'</textarea></div>';
		}elseif($input['type'] == 'select'){
			$addOptions = '';
			foreach($input['options'] as $k=>$v){
				$addOptions.='<option value="'.$k.'" '.($row[$key]==$k?'selected':'').'>'.$v.'</option>';
			}
			$data .= '<div class="form-group"><label>'.$input['name'].'：</label><br/><select class="form-control" name="'.$key.'" default="'.htmlspecialchars($row[$key], ENT_QUOTES, 'UTF-8').'">'.$addOptions.'</select></div>';
		}else{
			$data .= '<div class="form-group"><label>'.$input['name'].'：</label><br/><input type="text" id="'.$key.'" name="'.$key.'" value="'.htmlspecialchars($row[$key], ENT_QUOTES, 'UTF-8').'" class="form-control" placeholder="'.$input['note'].'"/></div>';
		}
	}
	if($plugin['bindwxmp'] && $row['type']==2){
		$wxmplist = $DB->getAll("SELECT * FROM pre_weixin WHERE type=0 ORDER BY id ASC");
		$addOptions = '<option value="0">不绑定</option>';
		foreach($wxmplist as $wxmp){
			$addOptions.='<option value="'.$wxmp['id'].'" '.($row['appwxmp']==$wxmp['id']?'selected':'').'>'.$wxmp['name'].'（'.$wxmp['appid'].'）'.'</option>';
		}
		$data .= '<div class="form-group"><label>绑定微信公众号：</label><br/><select class="form-control" name="appwxmp" default="'.$row[$key].'">'.$addOptions.'</select></div>';
	}
	if($plugin['bindwxa'] && $row['type']==2){
		$wxalist = $DB->getAll("SELECT * FROM pre_weixin WHERE type=1 ORDER BY id ASC");
		$addOptions = '<option value="0">不绑定</option>';
		foreach($wxalist as $wxa){
			$addOptions.='<option value="'.$wxa['id'].'" '.($row['appwxa']==$wxa['id']?'selected':'').'>'.$wxa['name'].'（'.$wxa['appid'].'）'.'</option>';
		}
		$data .= '<div class="form-group"><label>绑定微信小程序：</label><br/><select class="form-control" name="appwxa" default="'.$row[$key].'">'.$addOptions.'</select></div>';
	}

	$note = str_replace(['[siteurl]','[channel]'],[$siteurl,$id],$plugin['note']);

	$data .= '<button type="button" id="save" onclick="saveInfo('.$id.')" class="btn btn-primary btn-block">保存</button></form><br/><font color="green">'.$note.'</font></div>';
	$result=array("code"=>0,"msg"=>"succ","data"=>$data);
	exit(json_encode($result));
break;
case 'saveChannelInfo':
	$id=intval($_GET['id']);
	$appid=isset($_POST['appid'])?trim($_POST['appid']):null;
	$appkey=isset($_POST['appkey'])?trim($_POST['appkey']):null;
	$appsecret=isset($_POST['appsecret'])?trim($_POST['appsecret']):null;
	$appurl=isset($_POST['appurl'])?trim($_POST['appurl']):null;
	$appmchid=isset($_POST['appmchid'])?trim($_POST['appmchid']):null;
	$appwxmp=isset($_POST['appwxmp'])?intval($_POST['appwxmp']):null;
	$appwxa=isset($_POST['appwxa'])?intval($_POST['appwxa']):null;
	$appswitch=isset($_POST['appswitch'])?intval($_POST['appswitch']):null;
	if(isset($_POST['isapptype'])){
		if(!isset($_POST['apptype']) || count($_POST['apptype'])<=0)exit('{"code":-1,"msg":"请至少选择一个可用的支付接口"}');
		$apptype=implode(',',$_POST['apptype']);
	}else{
		$apptype=null;
	}
	$data = ['appid'=>$appid, 'appkey'=>$appkey, 'appsecret'=>$appsecret, 'appurl'=>$appurl, 'appmchid'=>$appmchid, 'apptype'=>$apptype, 'appwxmp'=>$appwxmp, 'appwxa'=>$appwxa, 'appswitch'=>$appswitch];
	if($DB->update('channel', $data, ['id'=>$id])!==false)exit('{"code":0,"msg":"修改支付密钥成功！"}');
	else exit('{"code":-1,"msg":"修改支付密钥失败"}');
break;
case 'getRoll':
	$id=intval($_GET['id']);
	$row=$DB->getRow("select * from pre_roll where id='$id' limit 1");
	if(!$row)
		exit('{"code":-1,"msg":"当前轮询组不存在！"}');
	$result = ['code'=>0,'msg'=>'succ','data'=>$row];
	exit(json_encode($result));
break;
case 'setRoll':
	$id=intval($_GET['id']);
	$status=intval($_GET['status']);
	$row=$DB->getRow("select * from pre_roll where id='$id' limit 1");
	if(!$row)
		exit('{"code":-1,"msg":"当前轮询组不存在！"}');
	if($status==1 && empty($row['info'])){
		exit('{"code":-1,"msg":"请先配置好支付通道后再开启"}');
	}
	$sql = "UPDATE pre_roll SET status='$status' WHERE id='$id'";
	if($DB->exec($sql))exit('{"code":0,"msg":"修改轮询组成功！"}');
	else exit('{"code":-1,"msg":"修改轮询组失败"}');
break;
case 'delRoll':
	$id=intval($_GET['id']);
	$row=$DB->getRow("select * from pre_roll where id='$id' limit 1");
	if(!$row)
		exit('{"code":-1,"msg":"当前轮询组不存在！"}');
	$sql = "DELETE FROM pre_roll WHERE id='$id'";
	if($DB->exec($sql))exit('{"code":0,"msg":"删除轮询组成功！"}');
	else exit('{"code":-1,"msg":"删除轮询组失败"}');
break;
case 'saveRoll':
	if($_POST['action'] == 'add'){
		$name=trim($_POST['name']);
		$type=intval($_POST['type']);
		$kind=intval($_POST['kind']);
		$row=$DB->getRow("select * from pre_roll where name=? limit 1", [$name]);
		if($row)
			exit('{"code":-1,"msg":"轮询组名称重复"}');
		$sql = "INSERT INTO pre_roll (name, type, kind) VALUES (?, ?, ?)";
		if($DB->exec($sql, [$name, $type, $kind]))exit('{"code":0,"msg":"新增轮询组成功！"}');
		else exit('{"code":-1,"msg":"新增轮询组失败"}');
	}else{
		$id=intval($_POST['id']);
		$name=trim($_POST['name']);
		$type=intval($_POST['type']);
		$kind=intval($_POST['kind']);
		$row=$DB->getRow("select * from pre_roll where name=? and id<>? limit 1", [$name, $id]);
		if($row)
			exit('{"code":-1,"msg":"轮询组名称重复"}');
		$sql = "UPDATE pre_roll SET name=?,type=?,kind=? WHERE id=?";
		if($DB->exec($sql, [$name, $type, $kind, $id])!==false)exit('{"code":0,"msg":"修改轮询组成功！"}');
		else exit('{"code":-1,"msg":"修改轮询组失败"}');
	}
break;
case 'rollInfo':
	$id=intval($_GET['id']);
	$row=$DB->getRow("select * from pre_roll where id='$id' limit 1");
	if(!$row)
		exit('{"code":-1,"msg":"当前轮询组不存在！"}');
	$list=$DB->getAll("select id,name from pre_channel where type='{$row['type']}' and status=1 ORDER BY id ASC");
	if(!$list)exit('{"code":-1,"msg":"没有找到支持该支付方式的通道"}');
	if(!empty($row['info'])){
		$arr = explode(',',$row['info']);
		$info = [];
		foreach($arr as $item){
			$a = explode(':',$item);
			$info[] = ['channel'=>$a[0], 'weight'=>$a[1]?$a[1]:1];
		}
	}else{
		$info = null;
	}
	$result=array("code"=>0,"msg"=>"succ","channels"=>$list,"info"=>$info);
	exit(json_encode($result));
break;
case 'saveRollInfo':
	$id=intval($_GET['id']);
	$row=$DB->getRow("select * from pre_roll where id='$id' limit 1");
	if(!$row)
		exit('{"code":-1,"msg":"当前轮询组不存在！"}');
	$list=$_POST['list'];
	if(empty($list))
		exit('{"code":-1,"msg":"通道配置不能为空！"}');
	$info = '';
	foreach($list as $a){
		$info .= $row['kind']==1 ? $a['channel'].':'.$a['weight'].',' : $a['channel'].',';
	}
	$info = trim($info,',');
	if(empty($info))
		exit('{"code":-1,"msg":"通道配置不能为空！"}');
	$sql = "UPDATE pre_roll SET info=? WHERE id=?";
	if($DB->exec($sql, [$info, $id])!==false)exit('{"code":0,"msg":"修改轮询组成功！"}');
	else exit('{"code":-1,"msg":"修改轮询组失败"}');
break;

case 'getChannelMoney':
	$type=intval($_GET['type']);
	$channel=intval($_GET['channel']);
	$today=$type==1 ? date("Y-m-d", strtotime("-1 day")) : date("Y-m-d");
	$money=$DB->getColumn("SELECT SUM(realmoney) FROM pre_order WHERE date='$today' AND channel='$channel' AND status>0");
	exit('{"code":0,"msg":"succ","money":"'.round($money,2).'"}');
break;
case 'getTypeMoney':
	$type=intval($_GET['type']);
	$typeid=intval($_GET['typeid']);
	$today=$type==1 ? date("Y-m-d", strtotime("-1 day")) : date("Y-m-d");
	$money=$DB->getColumn("SELECT SUM(realmoney) FROM pre_order WHERE date='$today' AND type='$typeid' AND status>0");
	exit('{"code":0,"msg":"succ","money":"'.round($money,2).'"}');
break;
case 'getChannelRate':
	$channel=intval($_GET['channel']);
	$thtime = date("Y-m-d").' 00:00:00';
	$all = 0;
	$success = 0;
	$orders=$DB->getAll("SELECT * FROM pre_order WHERE addtime>='$thtime' AND channel='$channel'");
	foreach($orders as $order){
		$all++;
		if($order['status']>0)$success++;
	}
	$rate = $all > 0 ? round($success*100/$all, 2) : 0;
	exit('{"code":0,"msg":"succ","rate":"'.$rate.'"}');
break;

case 'testpay':
	$channel=intval($_POST['channel']);
	$row=$DB->getRow("select * from pre_channel where id='$channel' limit 1");
	if(!$row)
		exit('{"code":-1,"msg":"当前支付通道不存在！"}');
	if(empty($row['appid']) || empty($row['appkey']) && empty($row['appsecret']))exit('{"code":-1,"msg":"请先配置好密钥"}');
	if(!$conf['test_pay_uid'])exit('{"code":-1,"msg":"请先配置测试支付收款商户ID"}');
	$money=trim(daddslashes($_POST['money']));
	$name=trim(daddslashes($_POST['name']));
	if($money<=0 || !is_numeric($money) || !preg_match('/^[0-9.]+$/', $money))exit('{"code":-1,"msg":"金额不合法"}');
	if($conf['pay_maxmoney']>0 && $money>$conf['pay_maxmoney'])exit('{"code":-1,"msg":"最大支付金额是'.$conf['pay_maxmoney'].'元"}');
	if($conf['pay_minmoney']>0 && $money<$conf['pay_minmoney'])exit('{"code":-1,"msg":"最小支付金额是'.$conf['pay_minmoney'].'元"}');
	$trade_no=date("YmdHis").rand(11111,99999);
	$return_url=$siteurl.'user/test.php?ok=1&trade_no='.$trade_no;
	$domain=getdomain($return_url);
	if(!$DB->exec("INSERT INTO `pre_order` (`trade_no`,`out_trade_no`,`uid`,`tid`,`addtime`,`name`,`money`,`type`,`channel`,`realmoney`,`getmoney`,`notify_url`,`return_url`,`domain`,`ip`,`status`) VALUES (:trade_no, :out_trade_no, :uid, 3, NOW(), :name, :money, :type, :channel, :realmoney, :getmoney, :notify_url, :return_url, :domain, :clientip, 0)", [':trade_no'=>$trade_no, ':out_trade_no'=>$trade_no, ':uid'=>$conf['test_pay_uid'], ':name'=>$name, ':money'=>$money, ':type'=>$row['type'], ':channel'=>$channel, ':realmoney'=>$money, ':getmoney'=>$money, ':notify_url'=>$return_url, ':return_url'=>$return_url, ':domain'=>$domain, ':clientip'=>$clientip]))exit('{"code":-1,"msg":"创建订单失败，请返回重试！"}');
	$result = ['code'=>0, 'msg'=>'succ', 'url'=>'./testsubmit.php?trade_no='.$trade_no];
	exit(json_encode($result));
break;

case 'getWeixin':
	$id=intval($_GET['id']);
	$row=$DB->getRow("select * from pre_weixin where id='$id' limit 1");
	if(!$row)
		exit('{"code":-1,"msg":"当前公众号/小程序不存在！"}');
	$result = ['code'=>0,'msg'=>'succ','data'=>$row];
	exit(json_encode($result));
break;
case 'setWeixin':
	$id=intval($_GET['id']);
	$status=intval($_GET['status']);
	$row=$DB->getRow("select * from pre_weixin where id='$id' limit 1");
	if(!$row)
		exit('{"code":-1,"msg":"当前公众号/小程序不存在！"}');
	$sql = "UPDATE pre_weixin SET status='$status' WHERE id='$id'";
	if($DB->exec($sql))exit('{"code":0,"msg":"修改公众号/小程序成功！"}');
	else exit('{"code":-1,"msg":"修改公众号/小程序失败"}');
break;
case 'delWeixin':
	$id=intval($_GET['id']);
	$row=$DB->getRow("select * from pre_weixin where id='$id' limit 1");
	if(!$row)
		exit('{"code":-1,"msg":"当前公众号/小程序不存在！"}');
	$row=$DB->getRow("select * from pre_channel where appwxmp='$id' limit 1");
	if($row)
		exit('{"code":-1,"msg":"删除失败，存在使用该微信公众号的支付通道"}');
	$row=$DB->getRow("select * from pre_channel where appwxa='$id' limit 1");
	if($row)
		exit('{"code":-1,"msg":"删除失败，存在使用该微信小程序的支付通道"}');
	$sql = "DELETE FROM pre_weixin WHERE id='$id'";
	if($DB->exec($sql))exit('{"code":0,"msg":"删除公众号/小程序成功！"}');
	else exit('{"code":-1,"msg":"删除公众号/小程序失败"}');
break;
case 'saveWeixin':
	if($_POST['action'] == 'add'){
		$type=intval($_POST['type']);
		$name=trim($_POST['name']);
		$appid=trim($_POST['appid']);
		$appsecret=trim($_POST['appsecret']);
		$row=$DB->getRow("select * from pre_weixin where name='$name' limit 1");
		if($row)
			exit('{"code":-1,"msg":"名称重复"}');
		if($DB->insert('weixin', ['type'=>$type, 'name'=>$name, 'appid'=>$appid, 'appsecret'=>$appsecret, 'status'=>1]))exit('{"code":0,"msg":"新增公众号/小程序成功！"}');
		else exit('{"code":-1,"msg":"新增公众号/小程序失败"}');
	}else{
		$id=intval($_POST['id']);
		$type=intval($_POST['type']);
		$name=trim($_POST['name']);
		$appid=trim($_POST['appid']);
		$appsecret=trim($_POST['appsecret']);
		$row=$DB->getRow("select * from pre_weixin where name='$name' and id<>$id limit 1");
		if($row)
			exit('{"code":-1,"msg":"名称重复"}');
		if($DB->update('weixin', ['type'=>$type, 'name'=>$name, 'appid'=>$appid, 'appsecret'=>$appsecret], ['id'=>$id])!==false)exit('{"code":0,"msg":"修改公众号/小程序成功！"}');
		else exit('{"code":-1,"msg":"修改公众号/小程序失败"}');
	}
break;
case 'testweixin':
	$id=intval($_POST['id']);
	$row=$DB->getRow("select * from pre_weixin where id='$id' limit 1");
	if(!$row)
		exit('{"code":-1,"msg":"当前公众号/小程序不存在！"}');
	try{
		$access_token = wx_get_access_token($row['appid'], $row['appsecret']);
	}catch(Exception $e){
		exit('{"code":-1,"msg":"'.$e->getMessage().'"}');
	}
	exit('{"code":0,"msg":"接口连接测试成功！"}');
break;

default:
	exit('{"code":-4,"msg":"No Act"}');
break;
}
