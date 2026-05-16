<?php
/**
 * 支付宝第三方应用授权服务类
 */
require_once PAY_ROOT.'inc/AlipayService.php';
require_once PAY_ROOT.'inc/model/request/AlipayOpenAuthTokenAppRequest.php';
require_once PAY_ROOT.'inc/model/request/AlipayOpenAuthTokenAppQueryRequest.php';

class AlipayAppAuthService extends AlipayService {

	function __construct($alipay_config){
		parent::__construct($alipay_config);

		$this->redirect_uri = $alipay_config['redirect_uri'];
	}

	//跳转支付宝授权页面
	public function oauth($state = null) {

		$param = array('app_id'=>$this->appid, 'redirect_uri'=>$this->redirect_uri);
		if($state) $param['state'] = $state;
		$url = 'https://openauth.alipay.com/oauth2/appToAppAuth.htm?'.http_build_query($param);

		Header("Location: $url");
		exit();

	}

	//换取授权访问令牌
	public function getToken($code, $grant_type = 'authorization_code') {
		$BizContent = [
			'grant_type' => $grant_type,
		];
		if($grant_type == 'refresh_token'){
			$BizContent['refresh_token'] = $code;
		}else{
			$BizContent['code'] = $code;
		}
		$request = new AlipayOpenAuthTokenAppRequest();
		$request->setBizContent(json_encode($BizContent));

		$response = $this->aopclientRequestExecute($request);
		$response = $response->alipay_open_auth_token_app_response ?? $response->error_response;
		
		if(!empty($response) && $response->code == "10000"){
			$result = array('user_id'=>$response->user_id, 'auth_app_id'=>$response->auth_app_id, 'app_auth_token'=>$response->app_auth_token, 'app_refresh_token'=>$response->app_refresh_token);
		}else{
			$result = array('code'=>$response->code, 'msg'=>$response->msg, 'sub_code'=>$response->sub_code, 'sub_msg'=>$response->sub_msg);
		}

		return $result;

	}

	//查询授权商家信息
	public function query($appAuthToken) {

		$request = new AlipayOpenAuthTokenAppQueryRequest();

		$response = $this->aopclientRequestExecute($request, $appAuthToken);
		$response = $response->alipay_open_auth_token_app_query_response ?? $response->error_response;
		
		if(!empty($response) && $response->code == "10000"){
			$result = json_decode(json_encode($response), true);
		}else{
			$result = array('code'=>$response->code, 'msg'=>$response->msg, 'sub_code'=>$response->sub_code, 'sub_msg'=>$response->sub_msg);
		}

		return $result;

	}

}