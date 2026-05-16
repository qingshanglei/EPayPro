<?php
$nosession=true;
include("../includes/common.php");
if(isset($_GET['sid'])){
	$sid = trim(daddslashes($_GET['sid']));
	if(!preg_match('/^(.[a-zA-Z0-9]+)$/',$sid))exit("Access Denied");
	session_id($sid);
	session_start();
}

@header('Content-Type: text/html; charset=UTF-8');

if(isset($_GET['channel'])){
	$channelid = intval($_GET['channel']);
}else{
	if(!$conf['transfer_wxpay'])sysmsg("未开启微信企业付款");
	$channelid = $conf['transfer_wxpay'];
}
$channel = \lib\Channel::get($channelid);
if(!$channel)sysmsg('当前支付通道信息不存在');
$wxinfo = \lib\Channel::getWeixin($channel['appwxmp']);
if(!$wxinfo)sysmsg('支付通道绑定的微信公众号不存在');

$tools = new \lib\wechat\JsApiPay($wxinfo['appid'], $wxinfo['appsecret']);
$openId = $tools->GetOpenid();

if(!$openId)sysmsg('OpenId获取失败('.$tools->data['errmsg'].')');

$_SESSION['openid'] = $openId;

$openid_name = 'OpenId';
$openid_content = $openId;
include PAYPAGE_ROOT.'openid.php';
