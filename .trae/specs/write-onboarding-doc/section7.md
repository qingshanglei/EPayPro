# 七、开发规范与最佳实践

## 7.1 代码风格规范

### 7.1.1 PHP代码风格

本项目遵循以下PHP代码风格约定，所有新代码应保持一致：

**缩进**

使用Tab缩进（非空格）。编辑器应配置"Tab转换为Tab字符"而非空格。

```php
// 正确：使用Tab缩进
if($conf['proxy'] == 1){
	$proxy_server = $conf['proxy_server'];
	$proxy_port = intval($conf['proxy_port']);
}

// 错误：使用空格缩进
if($conf['proxy'] == 1){
    $proxy_server = $conf['proxy_server'];
    $proxy_port = intval($conf['proxy_port']);
}
```

**大括号风格**

- 类和方法定义使用Allman风格（大括号另起一行）
- 控制结构（if/else/while/for/switch等）使用K&R风格（大括号不另起一行）

```php
// 类定义：Allman风格
class PdoHelper
{
	function __construct($dbconfig)
	{
		// ...
	}

	public function getRow($_sql, $_array = null)
	{
		// ...
	}
}

// 控制结构：K&R风格
if($money <= 0){
	return;
}elseif($type == 'alipay'){
	return transferToAlipay($channel, $out_trade_no, $payee_account, $payee_real_name, $money);
}else{
	return false;
}

switch($type){
case 1:
	$panel = "success";
	break;
case 2:
	$panel = "info";
	break;
default:
	$panel = "danger";
	break;
}
```

**字符串引号**

- 普通字符串优先使用单引号
- SQL语句使用双引号（便于在SQL内部使用单引号包裹字段值）
- 变量拼接使用点号（.）而非双引号内嵌

```php
// 普通字符串：单引号
$name = 'epay';
$panel = "success";

// SQL语句：双引号
$DB->exec("INSERT INTO `pre_user` (`uid`, `key`) VALUES (:uid, :key)");

// 变量拼接：点号
$prestr = $prestr . $key;
$url = $siteurl . 'pay/submit/' . TRADE_NO . '/';
```

**命名规范**

| 类型 | 风格 | 示例 |
|------|------|------|
| 函数名 | snake_case | `changeUserMoney`, `get_curl`, `daddslashes`, `checkRefererHost` |
| 变量名 | snake_case | `$userrow`, `$trade_no`, `$clientip`, `$siteurl` |
| 类名 | PascalCase | `PdoHelper`, `PayUtils`, `Plugin`, `Template` |
| 类方法 | camelCase | `getRow`, `getAll`, `getColumn`, `dealPrefix` |
| 常量 | UPPER_SNAKE_CASE | `SYSTEM_ROOT`, `DB_VERSION`, `PLUGIN_ROOT`, `TRADE_NO` |
| 数据库表 | pre_前缀+snake_case | `pre_order`, `pre_user`, `pre_config`, `pre_channel` |
| 静态属性 | snake_case | `$info`（插件信息属性） |

```php
// 函数命名
function changeUserMoney($uid, $money, $add=true, $type=null, $orderid=null){}

// 类命名
class PdoHelper {}
class PayUtils {}

// 类方法命名
public function getRow($_sql, $_array = null)
public function findAll($table, $fields = '*', $where = array())

// 常量命名
define('SYSTEM_ROOT', dirname(__FILE__).'/');
define('DB_VERSION', '2024');
define('PLUGIN_ROOT', ROOT.'plugins/');

// 数据库表命名
pre_order, pre_user, pre_config, pre_channel, pre_type, pre_plugin, pre_cache
```

### 7.1.2 数据库操作规范

本项目使用PdoHelper类封装PDO操作，所有数据库操作必须通过该类进行。

**预处理语句防止SQL注入**

```php
// 正确：使用参数绑定
$userrow = $DB->getRow("SELECT * FROM pre_user WHERE uid=:uid LIMIT 1", [':uid'=>$uid]);
$DB->exec("INSERT INTO `pre_order` (`trade_no`,`uid`,`money`) VALUES (:trade_no, :uid, :money)", [':trade_no'=>$trade_no, ':uid'=>$pid, ':money'=>$money]);

// 错误：直接拼接变量（存在SQL注入风险）
$userrow = $DB->getRow("SELECT * FROM pre_user WHERE uid='{$uid}' LIMIT 1");
```

> **注意**：项目中部分旧代码仍存在直接拼接变量的写法，新代码必须使用参数绑定。

**表名前缀约定**

SQL语句中使用`pre_`前缀，PdoHelper会自动替换为实际配置的表前缀：

```php
// SQL中使用pre_前缀，PdoHelper::dealPrefix()自动替换
$DB->getRow("SELECT * FROM pre_order WHERE trade_no=:trade_no LIMIT 1", [':trade_no'=>$trade_no]);
// 实际执行的SQL：SELECT * FROM pay_order WHERE trade_no=:trade_no LIMIT 1
```

快捷方法中表名不需要加`pre_`前缀，方法内部会自动添加：

```php
// 快捷方法：表名不加pre_前缀
$DB->find('user', '*', ['uid'=>$uid]);
$DB->findAll('order', '*', ['uid'=>$uid], 'id DESC', [0, 10]);
$DB->insert('user', ['key'=>$key, 'email'=>$email]);
$DB->update('user', ['money'=>$newmoney], ['uid'=>$uid]);
$DB->delete('record', ['id'=>$id]);
$DB->count('order', ['uid'=>$uid, 'status'=>1]);
```

**核心查询方法**

| 方法 | 用途 | 返回值 |
|------|------|--------|
| `getRow($sql, $params)` | 查询一行数据 | 关联数组或false |
| `getAll($sql, $params)` | 查询全部数据 | 关联数组或false |
| `getColumn($sql, $params)` | 查询单个字段值 | 标量值或false |
| `exec($sql, $params)` | 执行写操作 | 影响行数或false |
| `query($sql, $params)` | 获取PDOStatement | PDOStatement或false |
| `getCount($sql, $params)` | 获取结果行数 | 整数或false |
| `lastInsertId()` | 获取最后插入ID | 整数 |

**快捷方法**

| 方法 | 用途 | 示例 |
|------|------|------|
| `find($table, $fields, $where, $sort, $limit)` | 查询一行 | `$DB->find('user', '*', ['uid'=>$uid])` |
| `findAll($table, $fields, $where, $sort, $limit)` | 查询全部 | `$DB->findAll('order', '*', ['status'=>0], 'id DESC')` |
| `findColumn($table, $fields, $where, $sort)` | 查询单字段 | `$DB->findColumn('user', 'key', ['uid'=>$uid])` |
| `insert($table, $data)` | 插入数据 | `$DB->insert('user', ['email'=>$email])` |
| `update($table, $data, $where)` | 更新数据 | `$DB->update('user', ['money'=>$money], ['uid'=>$uid])` |
| `delete($table, $where)` | 删除数据 | `$DB->delete('record', ['id'=>$id])` |
| `count($table, $where)` | 统计行数 | `$DB->count('order', ['uid'=>$uid])` |

**事务操作**

涉及金额变动等关键操作必须使用事务，配合行锁保证数据一致性：

```php
function changeUserMoney($uid, $money, $add=true, $type=null, $orderid=null){
	global $DB;
	if($money<=0)return;

	// 退款去重检查
	if($type=='订单退款'){
		$isrefund = $DB->getColumn("SELECT id FROM pre_record WHERE type='订单退款' AND trade_no='{$orderid}' LIMIT 1");
		if($isrefund)return;
	}

	// 开启事务
	$DB->beginTransaction();

	// 使用FOR UPDATE行锁锁定用户行，防止并发修改
	$oldmoney = $DB->getColumn("SELECT money FROM pre_user WHERE uid='{$uid}' LIMIT 1 FOR UPDATE");

	if($add == true){
		$action = 1;
		$newmoney = round($oldmoney+$money, 2);
	}else{
		$action = 2;
		$newmoney = round($oldmoney-$money, 2);
	}

	$res = $DB->exec("UPDATE pre_user SET money='{$newmoney}' WHERE uid='{$uid}'");
	$DB->exec("INSERT INTO `pre_record` (...) VALUES (...)", [...]);

	// 提交事务
	$DB->commit();
	return $res;
}
```

**特殊值处理**

insert/update方法支持SQL函数作为值：

```php
// NOW()、CURDATE()、CURTIME()会原样写入SQL
$DB->insert('order', [
	'trade_no' => $trade_no,
	'addtime'  => 'NOW()',    // 直接使用SQL函数
	'money'    => $money,      // 普通值自动参数绑定
	'status'   => 0,
]);

// 空字符串会写入NULL
$DB->update('order', ['endtime'=>'NOW()', 'param'=>''], ['trade_no'=>$trade_no]);
```

### 7.1.3 全局变量使用规范

系统在`common.php`初始化阶段定义了以下全局变量，在函数或方法中使用时需通过`global`声明：

| 变量 | 类型 | 说明 | 定义位置 |
|------|------|------|----------|
| `$DB` | PdoHelper | 数据库操作实例 | common.php |
| `$CACHE` | Cache | 缓存操作实例 | common.php |
| `$conf` | array | 系统配置（从pre_config表加载） | common.php |
| `$clientip` | string | 客户端IP地址 | member.php |
| `$date` | string | 当前日期时间（Y-m-d H:i:s） | common.php |
| `$siteurl` | string | 站点URL（含协议和域名） | common.php |
| `$cdnpublic` | string | 公共CDN地址 | common.php |
| `$password_hash` | string | 密码哈希盐值 | common.php |
| `$islogin` | int | 管理员登录状态（1=已登录） | member.php |
| `$islogin2` | int | 商户登录状态（1=已登录） | member.php |
| `$userrow` | array | 当前登录商户信息 | member.php |
| `$order` | array | 当前订单信息（插件上下文） | Plugin.php |
| `$channel` | array | 当前支付通道信息（插件上下文） | Plugin.php |
| `$ordername` | string | 订单显示名称（经替换后） | Plugin.php |

使用示例：

```php
function changeUserMoney($uid, $money, $add=true, $type=null, $orderid=null){
	global $DB;  // 声明使用全局变量
	// ...
}

static public function submit(){
	global $siteurl, $channel, $order, $ordername, $sitename, $conf;
	// ...
}
```

**系统常量**

| 常量 | 说明 | 定义位置 |
|------|------|----------|
| `SYSTEM_ROOT` | includes目录绝对路径 | common.php |
| `ROOT` | 项目根目录绝对路径 | common.php |
| `PAYPAGE_ROOT` | 支付页面模板目录 | common.php |
| `TEMPLATE_ROOT` | 前台模板目录 | common.php |
| `PLUGIN_ROOT` | 插件目录 | common.php |
| `VERSION` | 系统版本号 | common.php |
| `DB_VERSION` | 数据库版本号 | common.php |
| `SYS_KEY` | 系统密钥 | common.php |
| `DBQZ` | 数据库表前缀标识 | common.php |
| `IN_PLUGIN` | 是否在插件上下文中 | Plugin.php |
| `PAY_PLUGIN` | 当前插件名称 | Plugin.php |
| `PAY_ROOT` | 当前插件目录绝对路径 | Plugin.php |
| `TRADE_NO` | 当前订单号 | Plugin.php |
| `IN_REFUND` | 是否在退款上下文中 | Plugin.php |
| `INDEX_ROOT` | 当前模板目录绝对路径 | Template.php |
| `STATIC_ROOT` | 当前模板静态资源URL路径 | Template.php |

## 7.2 安全规范

### 7.2.1 SQL注入防护

**第一道防线：PdoHelper预处理语句**

所有数据库操作必须使用参数绑定，禁止直接拼接用户输入到SQL语句中：

```php
// 正确：参数绑定
$userrow = $DB->getRow("SELECT * FROM pre_user WHERE uid=:uid LIMIT 1", [':uid'=>$uid]);
$DB->exec("INSERT INTO `pre_order` (`trade_no`,`uid`) VALUES (:trade_no, :uid)", [':trade_no'=>$trade_no, ':uid'=>$pid]);

// 错误：直接拼接
$userrow = $DB->getRow("SELECT * FROM pre_user WHERE uid='{$uid}' LIMIT 1");
```

**第二道防线：daddslashes()函数**

对无法使用预处理的外部输入进行转义处理。该函数支持递归处理数组：

```php
function daddslashes($string) {
	if(is_array($string)) {
		foreach($string as $key => $val) {
			$string[$key] = daddslashes($val);
		}
	} else {
		$string = addslashes($string);
	}
	return $string;
}

// 使用示例（submit.php）
$type = daddslashes($queryArr['type']);
$out_trade_no = daddslashes($queryArr['out_trade_no']);
$money = daddslashes($queryArr['money']);
```

**最佳实践**：优先使用预处理语句，`daddslashes()`仅作为辅助手段。

### 7.2.2 XSS防护

**输出转义**

在HTML中输出用户可控数据时，必须使用`htmlspecialchars()`进行转义：

```php
// 正确：输出前转义
$notify_url = htmlspecialchars(daddslashes($queryArr['notify_url']));
$return_url = htmlspecialchars(daddslashes($queryArr['return_url']));
$name = htmlspecialchars(daddslashes($queryArr['name']));
$param = isset($queryArr['param']) ? htmlspecialchars(daddslashes($queryArr['param'])) : null;

// 模板中输出
<title><?php echo $conf['title']?></title>
<meta name="keywords" content="<?php echo $conf['keywords']?>">
<div class="ban2_middle">欢迎使用<?php echo $conf['sitename']?></div>
```

**URL参数编码**

URL参数使用`urlencode()`编码：

```php
// sitename参数使用base64+urlencode双重编码
$sitename = urlencode(base64_encode(htmlspecialchars($queryArr['sitename'])));
```

**禁止直接输出用户输入**

```php
// 错误：直接输出用户输入
echo $_GET['name'];
echo "<div>".$_POST['content']."</div>";

// 正确：转义后输出
echo htmlspecialchars($_GET['name']);
echo "<div>".htmlspecialchars($_POST['content'])."</div>";
```

### 7.2.3 CSRF防护

**表单提交携带csrf_token**

表单中必须包含CSRF令牌，服务端验证Session中的token：

```php
// 表单中添加隐藏字段
// <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']?>">

// 服务端验证
if(!$_POST['csrf_token'] || $_POST['csrf_token']!=$_SESSION['csrf_token'])
	exit('{"code":-1,"msg":"CSRF TOKEN ERROR"}');

// 操作完成后销毁token
unset($_SESSION['csrf_token']);
```

**AJAX请求检查Referer**

AJAX接口使用`checkRefererHost()`函数验证请求来源：

```php
// user/ajax.php 中的使用
if(!checkRefererHost()) exit('{"code":403}');

// checkRefererHost()实现
function checkRefererHost(){
	if(!$_SERVER['HTTP_REFERER']) return false;
	$url_arr = parse_url($_SERVER['HTTP_REFERER']);
	$http_host = $_SERVER['HTTP_HOST'];
	if(strpos($http_host, ':')) $http_host = substr($http_host, 0, strpos($http_host, ':'));
	return $url_arr['host'] === $http_host;
}
```

### 7.2.4 签名验证机制

本项目使用`PayUtils`类实现MD5签名和验签，确保支付请求和回调数据的完整性。

**签名流程**

```php
// 1. 过滤空值和签名参数
$arg = \lib\PayUtils::paraFilter($array);  // 移除sign、sign_type和空值

// 2. 按键名升序排序
$arg = \lib\PayUtils::argSort($arg);  // ksort排序

// 3. 拼接为key=value&格式字符串
$prestr = \lib\PayUtils::createLinkstring($arg);  // "key1=val1&key2=val2"

// 4. 追加密钥后进行MD5
$sign = \lib\PayUtils::md5Sign($prestr, $key);  // md5($prestr . $key)
```

**验签流程**

```php
// submit.php中的验签示例
$prestr = PayUtils::createLinkstring(PayUtils::argSort(PayUtils::paraFilter($queryArr)));
$pid = intval($queryArr['pid']);
$userrow = $DB->getRow("SELECT `uid`,`key` FROM `pre_user` WHERE `uid`='{$pid}' LIMIT 1");
if(!PayUtils::md5Verify($prestr, $queryArr['sign'], $userrow['key']))
	sysmsg('签名校验失败，请返回重试！');
```

**核心规则**

- 所有支付请求必须验签（submit.php入口处即验证）
- 回调通知必须验签（插件notify方法中验证）
- 签名算法：`md5(参数排序拼接字符串 + 商户密钥)`
- 参与签名的参数排除`sign`和`sign_type`字段及空值

### 7.2.5 CC防护机制

系统通过`security.php`中的`cc_defender()`函数实现CC攻击防护：

```php
function cc_defender(){
	// 1. 基于IP+日期生成令牌
	$iptoken = md5(x_real_ip().date('Ymd')).md5(time().rand(11111,99999));

	// 2. 验证Cookie中的令牌
	if(!isset($_COOKIE['sec_defend']) || substr($_COOKIE['sec_defend'],0,32)!==substr($iptoken,0,32)){
		// 3. Cookie验证失败，设置Cookie并刷新页面
		$sec_defend_time = $_COOKIE['sec_defend_time']+1;
		$x = new \lib\hieroglyphy();  // 使用hieroglyphy混淆
		$setCookie = $x->hieroglyphyString($iptoken);
		// 输出JS设置Cookie后重新加载
		if($sec_defend_time>=10) exit('浏览器不支持COOKIE或者不正常访问！');
		// ... JavaScript设置Cookie并reload
		exit;
	}elseif(isset($_COOKIE['sec_defend_time'])){
		setcookie("sec_defend_time", "", time() - 604800, '/');
	}
}
```

**防护层次**

1. **Cookie验证**：首次访问需通过JavaScript设置Cookie，过滤无Cookie的爬虫
2. **hieroglyphy混淆**：Cookie值使用hieroglyphy编码，增加自动化攻击难度
3. **重试限制**：Cookie验证失败超过10次则直接拒绝访问
4. **IP绑定**：令牌与IP+日期绑定，防止令牌跨IP使用

**启用条件**

```php
// common.php中根据$is_defend变量决定是否启用
if($is_defend==true){
	if(!isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest')
		include_once(SYSTEM_ROOT."txprotect.php");
	cc_defender();
}
```

### 7.2.6 其他安全措施

**腾讯云防护（txprotect.php）**

屏蔽各类搜索引擎蜘蛛和异常浏览器请求：

```php
// 屏蔽的User-Agent包括：
// - 搜索引擎蜘蛛：Baiduspider, 360Spider, YisouSpider, Sogou web spider等
// - 自动化工具：python, libcurl/, Go-http-client, HeadlessChrome
// - 异常浏览器：含ozilla但不含Mozilla、微信开发者工具等
// - 特征Cookie：ASPSESSIONIDQASBQDRC
// - 无User-Agent或无Accept头的请求
if(strpos($_SERVER['HTTP_USER_AGENT'], 'Baiduspider')!==false || ...){
	header("HTTP/1.1 404 Not Found");
	exit;
}
```

**IP黑名单（blockips）**

```php
// submit.php中的IP黑名单检查
if($conf['blockips']){
	$blockips = explode('|', $conf['blockips']);
	if(in_array($clientip, $blockips))
		sysmsg('系统异常无法完成付款');
}
```

**域名白名单（pay_domain_forbid）**

```php
// 开启域名白名单后，仅允许已授权域名发起支付
if($conf['pay_domain_forbid']==1){
	if(!$DB->getRow("SELECT * FROM pre_domain WHERE uid=:uid AND (domain=:domain OR domain=:domain2) AND status=1 LIMIT 1",
		[':uid'=>$pid, ':domain'=>get_host($notify_url), ':domain2'=>'*.'.get_main_host($notify_url)])){
		sysmsg('该域名不可发起支付，原因：域名没过白，请前往支付平台授权支付域名');
	}
}
```

**商品名黑名单（blockname）**

```php
// 检测违禁商品名称，同时记录风险日志
if(!empty($conf['blockname'])){
	$block_name = explode('|', $conf['blockname']);
	foreach($block_name as $rows){
		if(!empty($rows) && strpos($name, $rows)!==false){
			$DB->exec("INSERT INTO `pre_risk` (`uid`, `url`, `content`, `date`) VALUES (:uid, :domain, :rows, NOW())",
				[':uid'=>$pid, ':domain'=>$domain, ':rows'=>$rows]);
			sysmsg($conf['blockalert']?$conf['blockalert']:'该商品禁止出售');
		}
	}
}
```

**用户黑名单（blockusers）**

```php
// 支付成功后检查付款人是否在黑名单中
function checkBlockUser($openid, $trade_no){
	global $DB, $conf;
	$DB->update('order', ['buyer'=>$openid], ['trade_no'=>$trade_no]);
	if($conf['blockusers']){
		$blockusers = explode('|', $conf['blockusers']);
		if(in_array($openid, $blockusers))
			return ['type'=>'error', 'msg'=>'系统异常无法完成付款'];
	}
	return false;
}
```

**其他安全实践**

- 金额校验：`if($money<=0 || !is_numeric($money) || !preg_match('/^[0-9.]+$/', $money))`
- 订单号格式校验：`if(!preg_match('/^[a-zA-Z0-9.\_\-|]+$/', $out_trade_no))`
- 实名认证强制：`if($conf['cert_force']==1 && $userrow['cert']==0)`
- 安装锁文件检测：检查`install.lock`文件是否存在
- Cookie认证：用户Token使用`authcode()`加密存储，包含过期时间验证

## 7.3 插件开发规范

### 7.3.1 插件目录结构

```
plugins/
  └── myplugin/                  # 插件目录（与插件name一致）
      ├── myplugin_plugin.php    # 插件主文件（必须，命名规则：{name}_plugin.php）
      └── inc/                   # SDK和辅助类（可选）
          ├── Config.php          # 配置文件
          └── Api.php             # API封装类
```

**命名规则**

- 插件目录名必须与`$info['name']`一致
- 插件主文件名必须为`{name}_plugin.php`
- 插件类名必须为`{name}_plugin`（无命名空间）
- 类必须为静态类，所有方法均为`static public`

### 7.3.2 $info属性定义

插件必须定义静态属性`$info`，描述插件的基本信息和配置参数：

```php
static public $info = [
	'name'        => 'epay',       // 支付插件英文名称，需和目录名称一致，不能有重复
	'showname'    => '彩虹易支付',  // 支付插件显示名称
	'author'      => '彩虹',       // 支付插件作者
	'link'        => '',           // 支付插件作者链接
	'types'       => ['alipay','qqpay','wxpay','bank','jdpay'],  // 支持的支付方式
	'inputs'      => [...],        // 配置参数定义
	'select'      => null,         // 下拉选项配置（可选）
	'note'        => '',           // 密钥填写说明
	'bindwxmp'    => false,        // 是否支持绑定微信公众号
	'bindwxa'     => false,        // 是否支持绑定微信小程序
];
```

**各字段详细说明**

| 字段 | 类型 | 必填 | 说明 |
|------|------|------|------|
| `name` | string | 是 | 插件唯一标识，必须与目录名一致 |
| `showname` | string | 是 | 后台显示的插件名称 |
| `author` | string | 是 | 插件作者名称 |
| `link` | string | 否 | 作者链接 |
| `types` | array | 是 | 支持的支付方式，可选值：`alipay`, `wxpay`, `qqpay`, `bank`, `jdpay` |
| `inputs` | array | 是 | 通道配置参数定义 |
| `select` | array/null | 否 | 额外下拉选项配置 |
| `note` | string | 否 | 密钥填写说明，显示在通道配置页面 |
| `bindwxmp` | bool | 否 | 是否支持绑定微信公众号（获取openid） |
| `bindwxa` | bool | 否 | 是否支持绑定微信小程序（获取openid） |

**inputs配置参数定义**

`inputs`定义通道配置页面需要填写的参数，每个参数包含`name`、`type`、`note`字段：

```php
'inputs' => [
	'appurl' => [
		'name' => '接口地址',       // 参数显示名称
		'type' => 'input',          // 表单类型：input（文本框）或select（下拉框）
		'note' => '必须以http://或https://开头，以/结尾',  // 填写提示
	],
	'appid' => [
		'name' => '商户ID',
		'type' => 'input',
		'note' => '',
	],
	'appkey' => [
		'name' => '商户密钥',
		'type' => 'input',
		'note' => '',
	],
	'appswitch' => [
		'name' => '是否使用mapi接口',
		'type' => 'select',         // 下拉框类型
		'options' => [0=>'否', 1=>'是'],  // 下拉选项
	],
],
```

**常用input键名约定**

| 键名 | 说明 |
|------|------|
| `appid` | 应用ID/商户ID |
| `appkey` | 应用密钥/商户密钥 |
| `appsecret` | 应用Secret |
| `appurl` | 接口地址 |
| `appmchid` | 商户号 |

### 7.3.3 必须实现的方法

插件类必须实现以下核心方法：

**submit() — 页面支付提交（必须）**

处理PC/移动端页面支付请求，返回支付结果：

```php
static public function submit(){
	global $siteurl, $channel, $order, $ordername, $sitename, $conf;
	// 构建支付参数
	// 调用支付接口
	// 返回结果数组
	return ['type'=>'jump', 'url'=>$jump_url];
}
```

**notify() — 异步通知处理（必须）**

处理支付平台的异步回调通知：

```php
static public function notify(){
	global $channel, $order;
	// 验证签名
	// 验证订单金额
	// 处理订单
	processNotify($order, $trade_no);
	return ['type'=>'html', 'data'=>'success'];
}
```

**return() — 同步回调处理（必须）**

处理支付平台的同步跳转回调：

```php
static public function return(){
	global $channel, $order;
	// 验证签名
	// 验证订单金额
	// 处理订单
	processReturn($order, $trade_no);
}
```

**可选方法**

| 方法 | 说明 | 参数 |
|------|------|------|
| `mapi()` | API支付接口 | 无（通过全局变量获取参数） |
| `jsapi($type, $money, $name, $openid)` | JSAPI支付（微信公众号/小程序） | 支付方式、金额、商品名、openid |
| `refund($order)` | 退款接口 | 订单信息数组 |

> **注意**：如果插件未实现`mapi()`方法但实现了`submit()`方法，系统会自动降级为页面跳转方式。

### 7.3.4 返回值格式

插件方法必须返回一个包含`type`字段的关联数组，不同`type`对应不同的处理方式：

**页面支付（submit）返回类型**

| type | 说明 | 必需字段 | 可选字段 | 处理方式 |
|------|------|----------|----------|----------|
| `jump` | 跳转到URL | `url` | - | JavaScript跳转 |
| `html` | 显示HTML内容 | `data` | - | 直接输出HTML |
| `page` | 显示支付页面模板 | `page` | `data` | 包含pages目录下的模板文件 |
| `qrcode` | 扫码支付页面 | `page`, `url` | - | 显示二维码页面，`$code_url`变量可用 |
| `scheme` | URL Scheme跳转 | `page`, `url` | - | 显示URL Scheme页面，`$code_url`变量可用 |
| `return` | 同步回调跳转 | `url` | - | 跳转到回调URL |
| `error` | 错误提示 | `msg` | - | 显示错误信息 |
| `json` | JSON数据 | `data` | - | 输出JSON数据 |

**返回值示例**

```php
// 跳转到支付URL
return ['type'=>'jump', 'url'=>'https://pay.example.com/order/123'];

// 显示自动提交表单HTML
return ['type'=>'html', 'data'=>'<form>...</form><script>document.forms[0].submit()</script>'];

// 显示扫码支付页面（使用pages/alipay_qrcode.php模板）
return ['type'=>'qrcode', 'page'=>'alipay_qrcode', 'url'=>'https://qr.alipay.com/xxx'];

// 显示微信小程序跳转页面
return ['type'=>'scheme', 'page'=>'wxpay_mini', 'url'=>'weixin://dl/business/xxx'];

// 显示自定义支付页面（传递额外数据）
return ['type'=>'page', 'page'=>'custom_pay', 'data'=>['extra_info'=>$info]];

// 同步回调跳转
return ['type'=>'return', 'url'=>$return_url];

// 错误提示
return ['type'=>'error', 'msg'=>'支付通道暂时不可用'];

// JSON数据输出
return ['type'=>'json', 'data'=>['code'=>0, 'msg'=>'success']];
```

**API支付（mapi）返回类型**

mapi方法的返回值由`Payment::echoJson()`处理，支持的type较少：

| type | 说明 | JSON字段 |
|------|------|----------|
| `jump` | 返回支付URL | `payurl` |
| `qrcode` | 返回二维码链接 | `qrcode` |
| `scheme` | 返回URL Scheme | `urlscheme` |
| `error` | 错误 | `code=-2`, `msg` |

**异步通知（notify）返回类型**

notify方法通常返回以下两种类型：

```php
// 验证成功，返回success给支付平台
return ['type'=>'html', 'data'=>'success'];

// 验证失败
return ['type'=>'html', 'data'=>'fail'];
```

### 7.3.5 插件开发示例

以下是一个完整的支付插件代码框架：

```php
<?php

class mypay_plugin
{
	static public $info = [
		'name'        => 'mypay',
		'showname'    => '我的支付',
		'author'      => '开发者',
		'link'        => 'https://www.example.com',
		'types'       => ['alipay', 'wxpay'],
		'inputs' => [
			'appid' => [
				'name' => '商户ID',
				'type' => 'input',
				'note' => '',
			],
			'appkey' => [
				'name' => '商户密钥',
				'type' => 'input',
				'note' => '',
			],
			'appurl' => [
				'name' => '接口地址',
				'type' => 'input',
				'note' => '必须以http://或https://开头，以/结尾',
			],
		],
		'select' => null,
		'note' => '请填写商户ID和密钥',
		'bindwxmp' => false,
		'bindwxa' => false,
	];

	static public function submit(){
		global $siteurl, $channel, $order, $ordername, $sitename, $conf;

		require(PAY_ROOT . "inc/Config.php");
		require(PAY_ROOT . "inc/Api.php");

		$parameter = array(
			"pid" => $channel['appid'],
			"type" => $order['typename'],
			"notify_url" => $conf['localurl'] . 'pay/notify/' . TRADE_NO . '/',
			"return_url" => $siteurl . 'pay/return/' . TRADE_NO . '/',
			"out_trade_no" => TRADE_NO,
			"name" => $ordername,
			"money" => (float)$order['realmoney']
		);

		$api = new MyPayApi($channel['appid'], $channel['appkey'], $channel['appurl']);
		$result = $api->createOrder($parameter);

		if(isset($result['payurl'])){
			return ['type'=>'jump', 'url'=>$result['payurl']];
		}elseif(isset($result['qrcode'])){
			return ['type'=>'qrcode', 'page'=>$order['typename'].'_qrcode', 'url'=>$result['qrcode']];
		}else{
			return ['type'=>'error', 'msg'=>'获取支付链接失败'];
		}
	}

	static public function notify(){
		global $channel, $order;

		require(PAY_ROOT . "inc/Config.php");
		require(PAY_ROOT . "inc/Api.php");

		$api = new MyPayApi($channel['appid'], $channel['appkey'], $channel['appurl']);
		$verify_result = $api->verifyNotify();

		if($verify_result){
			$out_trade_no = daddslashes($_GET['out_trade_no']);
			$trade_no = daddslashes($_GET['trade_no']);
			$money = $_GET['money'];

			if($_GET['trade_status'] == 'TRADE_SUCCESS'){
				if($out_trade_no == TRADE_NO && round($money, 2) == round($order['realmoney'], 2)){
					processNotify($order, $trade_no);
				}
			}
			return ['type'=>'html', 'data'=>'success'];
		}else{
			return ['type'=>'html', 'data'=>'fail'];
		}
	}

	static public function return(){
		global $channel, $order;

		require(PAY_ROOT . "inc/Config.php");
		require(PAY_ROOT . "inc/Api.php");

		$api = new MyPayApi($channel['appid'], $channel['appkey'], $channel['appurl']);
		$verify_result = $api->verifyReturn();

		if($verify_result){
			$out_trade_no = daddslashes($_GET['out_trade_no']);
			$trade_no = daddslashes($_GET['trade_no']);
			$money = $_GET['money'];

			if($_GET['trade_status'] == 'TRADE_SUCCESS'){
				if($out_trade_no == TRADE_NO && round($money, 2) == round($order['realmoney'], 2)){
					processReturn($order, $trade_no);
				}else{
					return ['type'=>'error', 'msg'=>'订单信息校验失败'];
				}
			}else{
				return ['type'=>'error', 'msg'=>'trade_status='.$_GET['trade_status']];
			}
		}else{
			return ['type'=>'error', 'msg'=>'验证失败！'];
		}
	}

	static public function mapi(){
		global $siteurl, $channel, $order, $conf, $device, $mdevice;

		$typename = $order['typename'];
		if(method_exists(__CLASS__, $typename)){
			return self::$typename();
		}
		return ['type'=>'jump', 'url'=>$siteurl.'pay/submit/'.TRADE_NO.'/'];
	}

	static public function alipay(){
		// 支付宝API支付逻辑
		return ['type'=>'qrcode', 'page'=>'alipay_qrcode', 'url'=>$qrcode_url];
	}

	static public function wxpay(){
		// 微信API支付逻辑
		return ['type'=>'qrcode', 'page'=>'wxpay_qrcode', 'url'=>$qrcode_url];
	}
}
```

**插件开发注意事项**

1. 插件类不使用命名空间，类名格式为`{name}_plugin`
2. 所有方法必须是`static public`
3. 通过`global`声明使用全局变量（`$channel`, `$order`, `$conf`等）
4. 使用`PAY_ROOT`常量引入SDK文件，使用`TRADE_NO`常量获取订单号
5. notify方法中必须验签并校验订单金额
6. 使用`daddslashes()`处理回调中的外部参数
7. 金额比较使用`round()`避免浮点精度问题
8. 处理订单使用`processNotify()`（异步）和`processReturn()`（同步）函数

## 7.4 模板开发规范

### 7.4.1 模板目录结构

```
template/
  └── mytemplate/               # 模板目录（自定义名称）
      ├── index.php              # 首页模板（必须）
      ├── head.php               # HTML头部（可选，推荐）
      ├── foot.php               # HTML底部（可选，推荐）
      ├── agreement.php          # 用户协议页面（可选）
      ├── doc.php                # 开发文档页面（可选）
      └── assets/                # 静态资源目录
          ├── css/               # 样式文件
          │   ├── common.css
          │   └── index.css
          ├── js/                # JavaScript文件
          └── images/            # 图片资源
```

**模板文件要求**

- `index.php`是唯一必须的模板文件，模板是否存在通过检查此文件判断
- 所有模板文件必须以`<?php if(!defined('IN_CRONLITE'))exit();`开头，防止直接访问
- 模板中PHP代码与HTML混合编写，使用`<?php echo ?>`输出变量

### 7.4.2 模板加载机制

**Template::load()方法**

```php
static public function load($name = 'index'){
	global $conf;
	$template = $conf['template'] ? $conf['template'] : 'default';

	// 安全检查：模板名只允许字母数字
	if(!preg_match('/^[a-zA-Z0-9]+$/', $name)) exit('error');

	// 优先加载当前模板
	$filename = TEMPLATE_ROOT.$template.'/'.$name.'.php';
	$filename_default = TEMPLATE_ROOT.'default/'.$name.'.php';

	if(file_exists($filename)){
		define("INDEX_ROOT", TEMPLATE_ROOT.$template.'/');
		define("STATIC_ROOT", '/template/'.$template.'/assets/');
		return $filename;
	}elseif(file_exists($filename_default)){
		// fallback到default模板
		define("INDEX_ROOT", TEMPLATE_ROOT.'default/');
		define("STATIC_ROOT", '/template/default/assets/');
		return $filename_default;
	}else{
		exit('Template file not found');
	}
}
```

**加载流程**

1. 从`$conf['template']`获取当前模板名称，默认为`default`
2. 优先加载当前模板目录下的文件
3. 若当前模板中不存在，则fallback到`default`模板
4. 加载成功后定义`INDEX_ROOT`和`STATIC_ROOT`常量

**定义的常量**

| 常量 | 说明 | 示例值 |
|------|------|--------|
| `INDEX_ROOT` | 当前模板目录绝对路径 | `/www/wwwroot/pay/template/mytemplate/` |
| `STATIC_ROOT` | 当前模板静态资源URL路径 | `/template/mytemplate/assets/` |

**模板中引入子模板**

```php
// index.php中引入头部和底部
<?php
if(!defined('IN_CRONLITE'))exit();
require INDEX_ROOT.'head.php';
?>
<!-- 页面内容 -->
<?php echo $conf['sitename']?>
<!-- 引入底部 -->
<?php require INDEX_ROOT.'foot.php';?>
```

### 7.4.3 可用全局变量

模板中可以直接使用以下全局变量：

**系统配置变量**

| 变量 | 类型 | 说明 | 示例值 |
|------|------|------|--------|
| `$conf` | array | 系统配置数组 | 见下方详细字段 |
| `$siteurl` | string | 站点URL | `https://pay.example.com/` |
| `$cdnpublic` | string | 公共CDN地址 | `//cdn.staticfile.org/` |

**$conf常用字段**

| 字段 | 说明 |
|------|------|
| `$conf['sitename']` | 站点名称 |
| `$conf['title']` | 页面标题 |
| `$conf['keywords']` | SEO关键词 |
| `$conf['description']` | SEO描述 |
| `$conf['template']` | 当前模板名称 |
| `$conf['test_open']` | 是否开启支付测试 |
| `$conf['reg_open']` | 是否开放注册 |
| `$conf['captcha_open_login']` | 登录是否开启验证码 |
| `$conf['captcha_id']` | 极验验证ID |
| `$conf['captcha_key']` | 极验验证Key |
| `$conf['user_review']` | 商户是否需要审核 |
| `$conf['verifytype']` | 验证方式（0=邮箱，1=手机） |

**用户状态变量**

| 变量 | 类型 | 说明 |
|------|------|------|
| `$islogin` | int | 管理员是否登录（1=是） |
| `$islogin2` | int | 商户是否登录（1=是） |
| `$userrow` | array/null | 当前登录商户信息 |

**模板中使用示例**

```php
// head.php - 输出站点信息
<title><?php echo $conf['title']?></title>
<meta name="keywords" content="<?php echo $conf['keywords']?>">
<meta name="description" content="<?php echo $conf['description']?>" />

// 引入CDN资源
<link rel="stylesheet" href="<?php echo $cdnpublic?>font-awesome/4.7.0/css/font-awesome.min.css" />
<link rel="stylesheet" href="<?php echo $cdnpublic?>twitter-bootstrap/3.4.1/css/bootstrap.min.css" />
<script src="<?php echo $cdnpublic?>jquery/1.12.4/jquery.min.js"></script>

// 引入模板静态资源
<link rel="stylesheet" href="<?php echo STATIC_ROOT?>css/common.css">
<link rel="stylesheet" href="<?php echo STATIC_ROOT?>css/index-top.css">
<img src="<?php echo STATIC_ROOT?>images/banner4.png" class="img-responsive">

// 条件输出
<?php if($conf['test_open']){?>
<li><a href="/user/test.php">支付测试</a></li>
<?php }?>

// 输出站点名称
<div class="ban2_middle">欢迎使用<?php echo $conf['sitename']?></div>
```
