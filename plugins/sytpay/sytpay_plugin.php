<?php

class sytpay_plugin
{
	static public $info = [
		'name'        => 'sytpay', //支付插件英文名称，需和目录名称一致，不能有重复
		'showname'    => '一扫付', //支付插件显示名称
		'author'      => '一扫付', //支付插件作者
		'link'        => 'https://sytpay.cn/', //支付插件作者链接
		'types'       => ['alipay','wxpay','qqpay','bank','jdpay'], //支付插件支持的支付方式，可选的有alipay,qqpay,wxpay,bank
		'inputs' => [ //支付插件要求传入的参数以及参数显示名称，可选的有appid,appkey,appsecret,appurl,appmchid
			'appid' => [
				'name' => '商户号',
				'type' => 'input',
				'note' => '',
			],
			'appkey' => [
				'name' => '商户密钥',
				'type' => 'input',
				'note' => '',
			],
			'appswitch' => [
				'name' => '是否使用跳转支付',
				'type' => 'select',
				'options' => [0=>'否',1=>'是'],
			],
		],
		'select' => null,
		'note' => '', //支付密钥填写说明
		'bindwxmp' => false, //是否支持绑定微信公众号
		'bindwxa' => false, //是否支持绑定微信小程序
	];

	static public function submit(){
		global $siteurl, $channel, $order, $sitename;

		if($channel['appswitch']==1){
			return self::jump();
		}else{
			return ['type'=>'jump','url'=>'/pay/'.$order['typename'].'/'.TRADE_NO.'/?sitename='.$sitename];
		}
	}

	static public function mapi(){
		global $siteurl, $channel, $order, $device, $mdevice;

		if($channel['appswitch']==1){
			return self::jump();
		}else{
            $typename = $order['typename'];
            return self::$typename();
        }
	}

	//跳转支付
	static private function jump(){
		global $siteurl, $channel, $order, $ordername, $sitename, $conf, $mdevice;

		if($order['typename'] == 'alipay'){
			$payMethod = '2';
			$payType = '21';
		}elseif($order['typename'] == 'wxpay'){
			$payMethod = '1';
			if($mdevice == 'wechat' || strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger')!==false){
				$payType = '12';
			}else{
				$payType = '11';
			}
		}elseif($order['typename'] == 'qqpay'){
			$payMethod = '3';
			$payType = '31';
		}elseif($order['typename'] == 'jdpay'){
			$payMethod = '4';
			$payType = '41';
		}elseif($order['typename'] == 'bank'){
			$payMethod = '5';
			$payType = '51';
		}

		$apiurl = 'https://api.xiaowei.shop/api/add';
		$data = array(
			"orderAmount" => $order['realmoney'],
			"orderId" => TRADE_NO,
			"merchant" => $channel['appid'],
			"payMethod" => $payMethod,
			"payType" => $payType,
			"signType" => "MD5",
			"version" => "1.0",
			"outcome" => "no",
		);

		ksort($data);
		$postString = http_build_query($data);
		$sign = strtoupper(md5($postString.$channel['appkey']));
		$data['sign'] = $sign;
		$data["productName"] = $ordername;
		$data["notifyUrl"] = $conf['localurl'].'pay/notify/'.TRADE_NO.'/';
		$data["returnUrl"] = $siteurl.'pay/return/'.TRADE_NO.'/';
		$data["createTime"] = "".time();
		$data["phone"] = self::random_phone();

		$jump_url = $apiurl.'?'.http_build_query($data);

		return ['type'=>'jump','url'=>$jump_url];
	}

	//通用下单
	static private function addOrder($payMethod, $payType){
		global $siteurl, $channel, $order, $ordername, $sitename, $conf;

		session_start();

		$apiurl = 'https://api.xiaowei.shop/api/add';
		$param = array(
			"orderAmount" => $order['realmoney'],
			"orderId" => TRADE_NO,
			"merchant" => $channel['appid'],
			"payMethod" => $payMethod,
			"payType" => $payType,
			"signType" => "MD5",
			"version" => "1.0",
			"outcome" => "yes",
		);

		ksort($param);
		$postString = http_build_query($param);
		$sign = strtoupper(md5($postString.$channel['appkey']));
		$param['sign'] = $sign;
		$param["productName"] = $ordername;
		$param["notifyUrl"] = $conf['localurl'].'pay/notify/'.TRADE_NO.'/';
		$param["returnUrl"] = $siteurl.'pay/return/'.TRADE_NO.'/';
		$param["createTime"] = "".time();
		$param["phone"] = self::random_phone();

		if($_SESSION[TRADE_NO.'_pay']){
			$data = $_SESSION[TRADE_NO.'_pay'];
		}else{
			$data = get_curl($apiurl, http_build_query($param));
			$_SESSION[TRADE_NO.'_pay'] = $data;
		}

		$result = json_decode($data, true);

		if(isset($result['code']) && $result['code']==200){
			$code_url = $result['data']['url'];
		}else{
			$msg = $result['msg']?$result['msg']:$result['message'];
			throw new Exception($msg?$msg:'返回数据解析失败');
		}
		return $code_url;
	}

	//支付宝扫码支付
	static public function alipay(){
		try{
			$code_url = self::addOrder('2','21');
		}catch(Exception $ex){
			return ['type'=>'error','msg'=>'支付宝下单失败！'.$ex->getMessage()];
		}

		return ['type'=>'qrcode','page'=>'alipay_qrcode','url'=>$code_url];
	}

	//微信扫码支付
	static public function wxpay(){
		global $siteurl, $device, $mdevice;
		if($mdevice == 'wechat' || strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger')!==false){
			$payType = '12';
		}elseif (checkmobile()==true) {
			return ['type'=>'qrcode','page'=>'wxpay_wap','url'=>$siteurl.'pay/wxpay/'.TRADE_NO.'/'];
		}else{
			$payType = '11';
		}
		try{
			$code_url = self::addOrder('1',$payType);
		}catch(Exception $ex){
			return ['type'=>'error','msg'=>'微信支付下单失败！'.$ex->getMessage()];
		}

		if($mdevice == 'wechat' || strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger')!==false){
			return ['type'=>'jump','url'=>$code_url];
		} elseif (checkmobile()==true) {
			return ['type'=>'qrcode','page'=>'wxpay_wap','url'=>$code_url];
		} else {
			return ['type'=>'qrcode','page'=>'wxpay_qrcode','url'=>$code_url];
		}
	}

	//QQ钱包支付
	static public function qqpay(){
		try{
			$code_url = self::addOrder('3','31');
		}catch(Exception $ex){
			return ['type'=>'error','msg'=>'QQ钱包下单失败！'.$ex->getMessage()];
		}

		return ['type'=>'qrcode','page'=>'bank_qrcode','url'=>$code_url];
	}

	//京东支付
	static public function jdpay(){
		try{
			$code_url = self::addOrder('4','41');
		}catch(Exception $ex){
			return ['type'=>'error','msg'=>'京东支付下单失败！'.$ex->getMessage()];
		}

		return ['type'=>'qrcode','page'=>'jdpay_qrcode','url'=>$code_url];
	}

	//云闪付扫码支付
	static public function bank(){
		try{
			$code_url = self::addOrder('5','51');
		}catch(Exception $ex){
			return ['type'=>'error','msg'=>'银联云闪付下单失败！'.$ex->getMessage()];
		}

		return ['type'=>'qrcode','page'=>'bank_qrcode','url'=>$code_url];
	}

	//异步回调
	static public function notify(){
		global $channel, $order;

		$json = file_get_contents('php://input');
		//file_put_contents('logs.txt', $json);
		$arr = json_decode($json,true);
		$signstr = substr($json, strpos($json,'"paramsJson":')+13, -1);
		$jsonBase64 = base64_encode($signstr);
		$jsonBase64Md5 = md5($jsonBase64);
		$sign = strtoupper(md5($channel['appkey'].$jsonBase64Md5));
        if ($sign === $arr['sign']) {
			if($arr['paramsJson']['code'] == 200){
				$out_trade_no = daddslashes($arr['paramsJson']['data']['orderId']);
				$trade_no = daddslashes($arr['paramsJson']['data']['outTradeNo']);
				$money = $arr['paramsJson']['data']['orderAmount'];
				if($out_trade_no == TRADE_NO && round($money,2)==round($order['realmoney'],2)){
					processNotify($order, $trade_no);
				}
			}
			return ['type'=>'html','data'=>'success'];
        }else{
			return ['type'=>'html','data'=>'fail'];
		}

	}

	//同步回调
	static public function return(){
		return ['type'=>'page','page'=>'return'];
	}

	static private function random_phone(){
		return '13'.rand(1111,9999).rand(11111,99999);
	}

	//退款
	static public function refund($order){
		global $channel, $conf;
		if(empty($order))exit();

		$apiurl = 'http://api.xiaowei.shop/api/refund';
		$param = array(
			"orderId" => $order['trade_no'],
			"reason" => "申请退款",
			"refund" => $order['refundmoney']*100,
		);

		ksort($param);
		$postString = http_build_query($param);
		$sign = strtoupper(md5($postString.$channel['appkey']));
		$param['sign'] = $sign;

		$data = get_curl($apiurl, http_build_query($param));
		$result = json_decode($data, true);

		if(isset($result['code']) && $result['code']==200){
			return ['code'=>0];
		}else{
			$msg = $result['msg']?$result['msg']:$result['message'];
			return ['code'=>-1, 'msg'=>$msg];
		}
	}

}