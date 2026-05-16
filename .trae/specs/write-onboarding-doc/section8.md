# 八、功能模块扩展点说明

聚合易支付采用插件化架构设计，核心业务逻辑与具体支付实现解耦，开发者可以通过扩展支付插件、首页模板、支付方式、用户组、实名认证和转账通道等模块来增强系统功能。本章将详细说明每个扩展点的机制、接口定义和开发步骤。

---

## 8.1 支付插件扩展指南

支付插件是聚合易支付最核心的扩展点。系统通过统一的插件接口将不同的支付网关（如支付宝、微信、QQ钱包、易支付等）接入到聚合支付平台中，每个插件以独立目录的形式存放，遵循固定的命名规范和接口约定。

### 8.1.1 新增支付插件的步骤

开发一个全新的支付插件需要遵循以下完整流程：

**步骤一：创建插件目录**

在 `plugins/` 目录下创建以插件英文名称命名的子目录，目录名只能包含小写字母和数字：

```
plugins/myplugin/
```

**步骤二：创建插件主文件**

在插件目录下创建与目录同名的主文件，命名格式为 `{插件名}_plugin.php`：

```
plugins/myplugin/myplugin_plugin.php
```

**步骤三：定义 `$info` 静态属性**

在插件主类中声明 `$info` 静态属性，描述插件元信息。该属性在插件注册时被系统读取并存入 `pre_plugin` 数据表。

**步骤四：实现核心方法**

至少实现以下三个核心方法：
- `submit()` — 页面支付提交
- `notify()` — 异步通知处理
- `return()` — 同步回调处理

**步骤五：实现可选方法（按需）**

- `mapi()` — API支付提交（供商户服务端调用）
- `jsapi($type, $money, $name, $openid=null)` — JSAPI支付（聚合收款码场景）
- `refund($order)` — 退款功能

**步骤六：在管理后台添加支付通道**

登录管理后台 → 支付通道管理 → 添加通道 → 选择刚创建的插件，填写通道参数。

**步骤七：配置插件参数**

在通道配置页面填写插件 `$info['inputs']` 中声明的各项参数（如商户ID、密钥等）。

**步骤八：刷新插件列表**

在管理后台点击"更新插件"按钮，系统会调用 `Plugin::updateAll()` 扫描 `plugins/` 目录，将新插件信息写入 `pre_plugin` 表。

---

### 8.1.2 插件接口定义

所有支付插件必须以 PHP 类的形式实现，类名为 `\\{插件名}_plugin`（带全局命名空间前缀），所有方法均为 `static public`。

#### `submit()` — 页面支付提交

```php
static public function submit()
```

**调用时机**：用户在浏览器中发起支付请求，系统加载插件后首先调用此方法。

**可用全局变量**：`$siteurl`, `$channel`, `$order`, `$ordername`, `$sitename`, `$conf`

**返回值格式**：返回一个关联数组，通过 `type` 字段区分不同的响应类型：

| type 值 | 含义 | 附加字段 | 说明 |
|---------|------|---------|------|
| `jump` | URL跳转 | `url` — 跳转地址 | 浏览器直接跳转到支付页面 |
| `html` | 输出HTML | `data` — HTML内容 | 直接输出表单自动提交的HTML |
| `page` | 渲染页面 | `page` — 页面文件名，`data` — 传递给页面的变量 | 渲染 `includes/pages/{page}.php` 页面 |
| `qrcode` | 扫码支付 | `url` — 二维码内容，`page` — 展示页面 | 显示二维码支付页面 |
| `scheme` | URL Scheme | `url` — scheme地址，`page` — 展示页面 | 微信小程序 scheme 跳转 |
| `return` | 同步回调 | `url` — 回调地址 | 直接执行同步回调跳转 |
| `error` | 错误提示 | `msg` — 错误信息 | 显示错误提示页面 |

**返回值示例**：

```php
// 跳转到外部支付网关
return ['type'=>'jump', 'url'=>'https://pay.example.com/order/12345'];

// 输出自动提交的HTML表单
return ['type'=>'html', 'data'=>'<form>...</form><script>document.forms[0].submit()</script>'];

// 渲染扫码支付页面
return ['type'=>'qrcode', 'page'=>'alipay_qrcode', 'url'=>'https://qr.alipay.com/xxx'];

// 渲染自定义页面，传递数据
return ['type'=>'page', 'page'=>'wxpay_jspay', 'data'=>['jsApiParameters'=>$jsApiParameters, 'redirect_url'=>$redirect_url]];

// 显示错误
return ['type'=>'error', 'msg'=>'支付通道暂不可用'];
```

#### `notify()` — 异步通知处理

```php
static public function notify()
```

**调用时机**：支付网关在用户完成支付后，向系统发送异步通知时调用。

**可用全局变量**：`$channel`, `$order`

**处理逻辑**：
1. 从 `$_GET` / `$_POST` 中获取支付网关的回调参数
2. 使用网关提供的签名验证方法验证回调的合法性
3. 校验订单号（与 `TRADE_NO` 常量比对）和金额（与 `$order['realmoney']` 比对）
4. 调用 `processNotify($order, $api_trade_no, $buyer)` 完成订单状态更新
5. 返回网关要求的成功响应

**返回值格式**：

```php
// 验证成功，返回网关要求的成功标识
return ['type'=>'html', 'data'=>'success'];

// 验证失败
return ['type'=>'html', 'data'=>'fail'];
```

**完整示例**：

```php
static public function notify(){
    global $channel, $order;

    // 1. 验证签名
    $verify_result = $this->verifySign($_POST);
    if(!$verify_result){
        return ['type'=>'html', 'data'=>'fail'];
    }

    // 2. 获取回调参数
    $out_trade_no = daddslashes($_POST['out_trade_no']);
    $trade_no = daddslashes($_POST['trade_no']);
    $money = $_POST['total_amount'];
    $buyer = daddslashes($_POST['buyer_id']);

    // 3. 校验订单并处理
    if($_POST['trade_status'] == 'TRADE_SUCCESS'){
        if($out_trade_no == TRADE_NO && round($money,2)==round($order['realmoney'],2)){
            processNotify($order, $trade_no, $buyer);
        }
    }

    return ['type'=>'html', 'data'=>'success'];
}
```

#### `return()` — 同步回调处理

```php
static public function return()
```

**调用时机**：用户在支付网关完成支付后，浏览器跳转回本系统时调用。

**可用全局变量**：`$channel`, `$order`

**处理逻辑**：
1. 从 `$_GET` 中获取支付网关的同步回调参数
2. 验证回调签名的合法性
3. 校验订单号和金额
4. 调用 `processReturn($order, $api_trade_no, $buyer)` 完成同步回调处理（该方法会自动跳转到商户的 return_url）

**返回值格式**：

```php
// 校验成功（processReturn内部会自动跳转，通常不需要额外返回）
// 校验失败
return ['type'=>'error', 'msg'=>'订单信息校验失败'];
```

#### `mapi()` — API支付提交

```php
static public function mapi()
```

**调用时机**：商户通过API接口发起支付请求时调用（`ismapi=true`）。

**可用全局变量**：`$siteurl`, `$channel`, `$order`, `$conf`, `$device`, `$mdevice`

**`$device` 和 `$mdevice` 说明**：
- `$device` — 客户端设备类型：`pc` 或 `mobile`
- `$mdevice` — 移动端内嵌环境：`wechat`（微信内）、`alipay`（支付宝内）、`qq`（QQ内）或空

**返回值格式**：与 `submit()` 相同，但返回值会通过 `Payment::echoJson()` 转换为 JSON 格式输出给商户：

```php
// 返回跳转URL → 输出 {"code":1,"trade_no":"xxx","payurl":"https://..."}
return ['type'=>'jump', 'url'=>$payurl];

// 返回二维码 → 输出 {"code":1,"trade_no":"xxx","qrcode":"https://..."}
return ['type'=>'qrcode', 'url'=>$qrcode_url];

// 返回URL Scheme → 输出 {"code":1,"trade_no":"xxx","urlscheme":"weixin://..."}
return ['type'=>'scheme', 'url'=>$scheme_url];

// 错误 → 输出 {"code":-2,"msg":"错误信息"}
return ['type'=>'error', 'msg'=>'支付通道不可用'];
```

**降级机制**：如果插件未实现 `mapi()` 方法但实现了 `submit()` 方法，系统会自动降级为页面支付模式，返回跳转URL：

```php
['type'=>'jump', 'url'=>$siteurl.'pay/submit/'.TRADE_NO.'/']
```

#### `jsapi($type, $money, $name, $openid=null)` — JSAPI支付

```php
static public function jsapi($type, $money, $name, $openid=null)
```

**调用时机**：聚合收款码场景下，用户扫码后系统通过 JSAPI 接口在微信/支付宝内直接拉起支付。

**参数说明**：

| 参数 | 类型 | 说明 |
|------|------|------|
| `$type` | string | 支付方式名称，如 `wxpay`、`alipay` |
| `$money` | float | 支付金额（元） |
| `$name` | string | 商品名称 |
| `$openid` | string \| null | 用户的 OpenID（微信支付时必填） |

**返回值**：直接返回支付参数字符串（如微信的 `jsApiParameters` 或支付宝的 `trade_no`），由调用方自行处理。异常时抛出 `Exception`。

**示例**：

```php
static public function jsapi($type, $money, $name, $openid){
    global $siteurl, $channel, $conf;

    // 调用支付网关下单接口
    $result = $this->createOrder(TRADE_NO, $money, $name, $openid);

    if($result['code'] == 'SUCCESS'){
        return $result['jsapi_parameters'];
    }else{
        throw new Exception('下单失败：'.$result['msg']);
    }
}
```

#### `refund($order)` — 退款

```php
static public function refund($order)
```

**调用时机**：管理员在后台对订单执行退款操作时调用。仅当插件实现了此方法时，退款按钮才会显示。

**参数说明**：

| 参数 | 类型 | 说明 |
|------|------|------|
| `$order` | array | 订单信息数组，包含以下关键字段 |

**`$order` 关键字段**：

| 字段 | 说明 |
|------|------|
| `trade_no` | 系统订单号 |
| `api_trade_no` | 支付网关交易号 |
| `realmoney` | 实际支付金额 |
| `refundmoney` | 退款金额 |

**返回值格式**：

```php
// 退款成功
return ['code'=>0, 'trade_no'=>'网关交易号', 'refund_fee'=>'退款金额(分)', 'refund_time'=>'退款时间'];

// 退款失败（业务级错误，如余额不足）
return ['code'=>-1, 'msg'=>'错误描述'];
```

**注意**：退款方法执行前，系统会定义 `IN_REFUND` 常量（而非 `IN_PLUGIN`），插件可通过此常量区分当前执行上下文。

---

### 8.1.3 插件可用全局变量

以下全局变量在插件方法执行前由系统自动注入，插件可直接通过 `global` 关键字访问：

| 变量名 | 类型 | 说明 | 可用方法 |
|--------|------|------|---------|
| `$siteurl` | string | 当前站点URL，格式如 `https://pay.example.com/` | submit, mapi |
| `$channel` | array | 当前支付通道配置，包含 `appid`、`appkey`、`appsecret`、`appurl`、`appmchid`、`apptype`、`mode`、`appwxmp`、`appwxa` 等字段 | 所有方法 |
| `$order` | array | 当前订单信息，包含 `trade_no`、`out_trade_no`、`uid`、`type`、`channel`、`name`、`money`、`realmoney`、`getmoney`、`typename`、`status` 等字段 | submit, notify, return, mapi, refund |
| `$ordername` | string | 订单显示名称（经过 `ordername_replace` 处理后的名称） | submit |
| `$conf` | array | 系统配置（`pre_config` 表的键值对），常用字段包括 `sitename`、`localurl`、`transfer_name`、`transfer_desc`、`ordername` 等 | submit, mapi |
| `$clientip` | string | 客户端IP地址 | submit, mapi |
| `$sitename` | string | 商户站点名称（即 `$conf['sitename']`） | submit |
| `$device` | string | 客户端设备类型：`pc` 或 `mobile` | mapi |
| `$mdevice` | string | 移动端内嵌环境：`wechat`、`alipay`、`qq` 或空 | mapi |

**`$channel` 通道配置字段详解**：

| 字段 | 说明 |
|------|------|
| `id` | 通道ID |
| `plugin` | 插件名称 |
| `type` | 支付方式ID（对应 `pre_type.id`） |
| `name` | 通道显示名称 |
| `rate` | 通道费率 |
| `appid` | 应用ID/商户ID（由 `$info['inputs']` 定义） |
| `appkey` | 应用密钥（由 `$info['inputs']` 定义） |
| `appsecret` | 应用密钥2（由 `$info['inputs']` 定义） |
| `appurl` | 接口地址/子商户号（由 `$info['inputs']` 定义） |
| `appmchid` | 商户号/授权token（由 `$info['inputs']` 定义） |
| `apptype` | 支付类型（逗号分隔的字符串，如 `"1,2,3"`，需用 `explode(',',$channel['apptype'])` 解析为数组） |
| `mode` | 手续费扣除模式：`0`=余额扣费，`1`=订单加费 |
| `appwxmp` | 绑定的微信公众号ID（对应 `pre_weixin.id`） |
| `appwxa` | 绑定的微信小程序ID（对应 `pre_weixin.id`） |
| `appswitch` | 自定义开关字段（由 `$info['inputs']` 定义） |

**`$channel` 的 `channelinfo` 覆盖机制**：当商户在 `pre_user.channelinfo` 字段中配置了通道参数覆盖时，系统会自动替换通道配置中对应字段的值。覆盖规则：如果通道字段值以 `[` 开头并以 `]` 结尾（如 `[key1]`），则从 `channelinfo` JSON 中取对应键名的值替换。

---

### 8.1.4 插件可用常量

系统在加载插件时（`Plugin::loadClass()` 方法中）会定义以下常量：

| 常量名 | 类型 | 说明 | 定义位置 |
|--------|------|------|---------|
| `IN_PLUGIN` | bool | 标识当前运行在插件上下文中（值为 `true`） | Plugin::loadClass() |
| `PAY_PLUGIN` | string | 当前插件名称（如 `epay`、`wxpaysl`） | Plugin::loadClass() |
| `PAY_ROOT` | string | 插件根目录的绝对路径（如 `/www/wwwroot/pay/plugins/epay/`） | Plugin::loadClass() |
| `TRADE_NO` | string | 系统订单号（19位数字，格式 `YmdHis`+5位随机数） | Plugin::loadClass() |

**特殊常量**：

| 常量名 | 说明 |
|--------|------|
| `IN_REFUND` | 退款上下文中定义（值为 `true`），此时 `IN_PLUGIN` 不定义 |
| `PLUGIN_ROOT` | 插件总目录（在 `common.php` 中定义，值为 `/www/wwwroot/pay/plugins/`） |
| `PLUGIN_PATH` | USDT插件自定义的插件路径常量 |

**使用示例**：

```php
// 引入插件内部文件
require(PAY_ROOT."inc/config.php");
require(PAY_ROOT."inc/PayApi.php");

// 构建回调URL
$notify_url = $conf['localurl'].'pay/notify/'.TRADE_NO.'/';
$return_url = $siteurl.'pay/return/'.TRADE_NO.'/';
```

---

### 8.1.5 插件可用函数

以下全局函数可在插件中直接调用：

#### `processNotify($order, $api_trade_no=null, $buyer=null)`

处理异步通知，完成订单状态更新、商户余额变动、回调通知等。

| 参数 | 类型 | 说明 |
|------|------|------|
| `$order` | array | 订单信息数组 |
| `$api_trade_no` | string \| null | 支付网关交易号 |
| `$buyer` | string \| null | 买家标识（如支付宝买家ID、微信OpenID） |

**内部逻辑**：调用 `Payment::processOrder(true, $order, $api_trade_no, $buyer)`，该方法会：
1. 将订单状态从 `0`（未支付）更新为 `1`（已支付）
2. 记录支付完成时间和第三方交易号
3. 调用 `processOrder()` 执行后续业务（商户余额变动、异步通知商户等）

#### `processReturn($order, $api_trade_no=null, $buyer=null)`

处理同步回调，完成订单状态更新后跳转到商户的 `return_url`。

| 参数 | 类型 | 说明 |
|------|------|------|
| `$order` | array | 订单信息数组 |
| `$api_trade_no` | string \| null | 支付网关交易号 |
| `$buyer` | string \| null | 买家标识 |

**内部逻辑**：调用 `Payment::processOrder(false, $order, $api_trade_no, $buyer)`，该方法会：
1. 执行与 `processNotify` 相同的订单更新逻辑
2. 额外执行同步跳转：将用户浏览器重定向到商户的 `return_url`
3. 如果支付完成超过5分钟，则跳转到 `/payok.html` 而非商户回调地址

#### `showerror($msg)`

在聚合收款码场景中显示错误页面并终止程序。

| 参数 | 类型 | 说明 |
|------|------|------|
| `$msg` | string | 错误信息 |

**注意**：此函数定义在 `paypage/inc.php` 中，仅在聚合收款码流程中可用。

#### `sysmsg($msg, $title='站点提示信息')`

显示系统级错误提示页面并终止程序。

| 参数 | 类型 | 说明 |
|------|------|------|
| `$msg` | string | 错误信息（支持HTML） |
| `$title` | string | 页面标题 |

#### `showerrorjson($msg)`

输出 JSON 格式的错误信息并终止程序。

| 参数 | 类型 | 说明 |
|------|------|------|
| `$msg` | string | 错误信息 |

**输出格式**：`{"code":-1,"msg":"错误信息"}`

#### 其他常用函数

| 函数名 | 说明 |
|--------|------|
| `daddslashes($string)` | 递归对字符串进行 `addslashes` 转义，防止SQL注入 |
| `checkmobile()` | 检测当前是否为移动端访问，返回 `bool` |
| `is_https()` | 检测当前是否为HTTPS访问，返回 `bool` |
| `get_curl($url, $post, ...)` | 通用HTTP请求函数，支持POST、Cookie、Header等参数 |
| `curl_get($url)` | 简单的GET请求函数，使用系统代理配置 |
| `checkBlockUser($openid, $trade_no)` | 检查用户是否在黑名单中，返回 `false` 或 `['type'=>'error','msg'=>'...']` |
| `ordername_replace($name, $oldname, $uid, $order)` | 替换订单名称中的占位符：`[name]`、`[order]`、`[qq]`、`[time]` |
| `real_ip($type=0)` | 获取客户端真实IP地址 |

---

### 8.1.6 完整插件开发示例

以下是一个完整的支付插件代码模板，以"易支付"接口为例展示所有关键接口的实现：

```php
<?php

class mypay_plugin
{
    static public $info = [
        'name'        => 'mypay',          // 必须与目录名一致
        'showname'    => '我的支付插件',     // 后台显示名称
        'author'      => '开发者名称',       // 作者
        'link'        => 'https://example.com', // 作者链接
        'types'       => ['alipay','wxpay'],    // 支持的支付方式
        'inputs' => [
            'appurl' => [
                'name' => '接口地址',
                'type' => 'input',        // 控件类型：input/textarea/select
                'note' => '必须以http://或https://开头，以/结尾',
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
        ],
        'select' => [                      // 可选：支付方式选择项
            '1' => '扫码支付',
            '2' => '手机支付',
        ],
        'note' => '<p>配置说明HTML</p>',    // 可选：配置页面的说明文字
        'bindwxmp' => false,               // 是否支持绑定微信公众号
        'bindwxa' => false,                // 是否支持绑定微信小程序
    ];

    // ========== 页面支付提交 ==========
    static public function submit(){
        global $siteurl, $channel, $order, $ordername, $sitename, $conf;

        // 构建请求参数
        $params = [
            'pid'          => $channel['appid'],
            'type'         => $order['typename'],
            'out_trade_no' => TRADE_NO,
            'notify_url'   => $conf['localurl'].'pay/notify/'.TRADE_NO.'/',
            'return_url'   => $siteurl.'pay/return/'.TRADE_NO.'/',
            'name'         => $ordername,
            'money'        => (float)$order['realmoney'],
        ];

        // 生成签名
        $params['sign'] = self::generateSign($params, $channel['appkey']);
        $params['sign_type'] = 'MD5';

        // 方式一：跳转到支付网关
        $payUrl = $channel['appurl'].'submit.php?'.http_build_query($params);
        return ['type'=>'jump', 'url'=>$payUrl];

        // 方式二：自动提交表单
        // $html = '<form id="payform" action="'.$channel['appurl'].'submit.php" method="post">';
        // foreach($params as $k=>$v){
        //     $html .= '<input type="hidden" name="'.$k.'" value="'.$v.'">';
        // }
        // $html .= '</form><script>document.getElementById("payform").submit();</script>';
        // return ['type'=>'html', 'data'=>$html];
    }

    // ========== API支付提交 ==========
    static public function mapi(){
        global $siteurl, $channel, $order, $conf, $device, $mdevice;

        $params = [
            'pid'          => $channel['appid'],
            'type'         => $order['typename'],
            'out_trade_no' => TRADE_NO,
            'notify_url'   => $conf['localurl'].'pay/notify/'.TRADE_NO.'/',
            'return_url'   => $siteurl.'pay/return/'.TRADE_NO.'/',
            'name'         => $order['name'],
            'money'        => (float)$order['realmoney'],
        ];
        $params['sign'] = self::generateSign($params, $channel['appkey']);
        $params['sign_type'] = 'MD5';

        $result = get_curl($channel['appurl'].'api.php', http_build_query($params));
        $result = json_decode($result, true);

        if(isset($result['code']) && $result['code']==1){
            if(!empty($result['payurl'])){
                return ['type'=>'jump', 'url'=>$result['payurl']];
            }elseif(!empty($result['qrcode'])){
                return ['type'=>'qrcode', 'url'=>$result['qrcode']];
            }
        }

        return ['type'=>'error', 'msg'=>$result['msg'] ?? '获取支付链接失败'];
    }

    // ========== 异步通知处理 ==========
    static public function notify(){
        global $channel, $order;

        // 验证签名
        $params = $_GET;
        $sign = $params['sign'];
        unset($params['sign'], $params['sign_type']);

        if(self::verifySign($params, $sign, $channel['appkey'])){
            $out_trade_no = daddslashes($_GET['out_trade_no']);
            $trade_no     = daddslashes($_GET['trade_no']);
            $money        = $_GET['money'];

            if($_GET['trade_status'] == 'TRADE_SUCCESS'){
                if($out_trade_no == TRADE_NO && round($money,2)==round($order['realmoney'],2)){
                    processNotify($order, $trade_no);
                }
            }
            return ['type'=>'html', 'data'=>'success'];
        }

        return ['type'=>'html', 'data'=>'fail'];
    }

    // ========== 同步回调处理 ==========
    static public function return(){
        global $channel, $order;

        $params = $_GET;
        $sign = $params['sign'];
        unset($params['sign'], $params['sign_type']);

        if(self::verifySign($params, $sign, $channel['appkey'])){
            $out_trade_no = daddslashes($_GET['out_trade_no']);
            $trade_no     = daddslashes($_GET['trade_no']);
            $money        = $_GET['money'];

            if($out_trade_no == TRADE_NO && round($money,2)==round($order['realmoney'],2)){
                processReturn($order, $trade_no);
            }else{
                return ['type'=>'error', 'msg'=>'订单信息校验失败'];
            }
        }else{
            return ['type'=>'error', 'msg'=>'验证失败'];
        }
    }

    // ========== 退款（可选） ==========
    static public function refund($order){
        global $channel;

        $params = [
            'pid'          => $channel['appid'],
            'out_trade_no' => $order['trade_no'],
            'money'        => $order['refundmoney'],
            'type'         => 'refund',
        ];
        $params['sign'] = self::generateSign($params, $channel['appkey']);

        $result = get_curl($channel['appurl'].'refund.php', http_build_query($params));
        $result = json_decode($result, true);

        if(isset($result['code']) && $result['code']==1){
            return ['code'=>0, 'trade_no'=>$result['trade_no']];
        }else{
            return ['code'=>-1, 'msg'=>$result['msg'] ?? '退款失败'];
        }
    }

    // ========== 签名工具方法 ==========
    static private function generateSign($params, $key){
        ksort($params);
        $str = '';
        foreach($params as $k=>$v){
            if($v !== '' && $v !== null) $str .= "$k=$v&";
        }
        return md5(rtrim($str, '&').$key);
    }

    static private function verifySign($params, $sign, $key){
        return self::generateSign($params, $key) === $sign;
    }
}
```

**`$info` 属性各字段详解**：

| 字段 | 类型 | 必填 | 说明 |
|------|------|------|------|
| `name` | string | 是 | 插件英文名称，必须与目录名一致，全局唯一 |
| `showname` | string | 是 | 插件显示名称，在管理后台展示 |
| `author` | string | 是 | 插件作者 |
| `link` | string | 否 | 作者链接 |
| `types` | array | 是 | 支持的支付方式列表，值对应 `pre_type.name`，如 `alipay`、`wxpay`、`qqpay`、`bank`、`jdpay`、`usdt` |
| `inputs` | array | 是 | 插件配置参数定义，键名对应通道表的字段名 |
| `select` | array \| null | 否 | 支付方式选择项，键为编号，值为显示名称。配置后通道管理页会显示多选框 |
| `note` | string | 否 | 配置页面的说明HTML，支持富文本 |
| `bindwxmp` | bool | 否 | 是否支持绑定微信公众号，默认 `false` |
| `bindwxa` | bool | 否 | 是否支持绑定微信小程序，默认 `false` |

**`$info['inputs']` 配置参数定义**：

`inputs` 的键名对应 `pre_channel` 表中的字段名，系统预定义了5个可用字段：`appid`、`appkey`、`appsecret`、`appurl`、`appmchid`。每个字段的定义格式：

```php
'appid' => [
    'name' => '参数显示名称',
    'type' => 'input',       // 控件类型：input（文本框）、textarea（文本域）、select（下拉框）
    'note' => '填写提示信息',
],
// select 类型需要额外定义 options
'appswitch' => [
    'name' => '是否使用mapi接口',
    'type' => 'select',
    'options' => [0=>'否', 1=>'是'],
],
```

---

## 8.2 首页模板扩展指南

聚合易支付支持多套首页模板，管理员可在后台切换。模板系统通过 `Template` 类实现加载和切换。

### 8.2.1 模板目录结构

所有模板存放在 `template/` 目录下，每套模板为一个独立子目录：

```
template/
├── default/              # 默认模板（必须存在，作为回退模板）
│   ├── index.php         # 首页模板
│   ├── head.php          # 页面头部
│   ├── foot.php          # 页面底部
│   ├── doc.php           # 开发文档页
│   ├── doc.inc.php       # 开发文档内容
│   ├── agreement.php     # 服务条款页
│   ├── wx.php            # 微信相关页
│   ├── payok.php         # 支付成功页
│   └── assets/           # 模板静态资源
│       ├── css/
│       ├── images/
│       └── js/
├── index1/               # 模板1
├── index2/               # 模板2
├── ...
└── index11/              # 模板11
```

**命名规范**：模板目录名只能包含字母和数字（系统通过正则 `/^[a-zA-Z0-9]+$/` 校验）。

### 8.2.2 模板加载机制

模板加载由 `lib\Template` 类处理，核心方法为 `Template::load($name)`：

```php
$template = $conf['template'] ?: 'default';
$filename = TEMPLATE_ROOT.$template.'/'.$name.'.php';
$filename_default = TEMPLATE_ROOT.'default/'.$name.'.php';
```

**加载流程**：

1. 从系统配置 `$conf['template']` 获取当前模板名称，默认为 `default`
2. 检查当前模板目录下是否存在请求的模板文件
3. 如果存在，定义 `INDEX_ROOT` 和 `STATIC_ROOT` 常量指向当前模板目录，返回文件路径
4. 如果不存在，回退到 `default` 模板目录查找同名文件
5. 如果默认模板中也不存在，直接 `exit('Template file not found')`

**常量定义**：

| 常量 | 说明 | 示例值 |
|------|------|--------|
| `INDEX_ROOT` | 当前模板目录的绝对路径 | `/www/wwwroot/pay/template/index1/` |
| `STATIC_ROOT` | 当前模板静态资源URL路径 | `/template/index1/assets/` |
| `TEMPLATE_ROOT` | 模板总目录（在 `common.php` 中定义） | `/www/wwwroot/pay/template/` |

**模板检测**：`Template::exists($template)` 方法通过检查 `template/{name}/index.php` 是否存在来判断模板是否有效。

**模板列表**：`Template::getList()` 方法扫描 `template/` 目录下的所有子目录（排除含 `.` 的名称），返回可用模板列表。

### 8.2.3 可用变量与函数

模板文件中可直接使用以下变量和函数：

**全局变量**：

| 变量 | 类型 | 说明 |
|------|------|------|
| `$conf` | array | 系统配置，常用字段：`sitename`、`title`、`keywords`、`description`、`orgname`、`kfqq`、`email`、`footer`、`test_open` |
| `$cdnpublic` | string | 公共CDN地址前缀，如 `//cdn.staticfile.org/` |

**模板中引用静态资源**：

```php
// CSS
<link rel="stylesheet" href="<?php echo STATIC_ROOT?>css/common.css">
<link rel="stylesheet" href="<?php echo STATIC_ROOT?>css/index.css">

// 图片
<img src="<?php echo STATIC_ROOT?>images/banner.png">

// JS
<script src="<?php echo STATIC_ROOT?>js/custom.js"></script>
```

**模板中引用公共CDN资源**：

```php
<link rel="stylesheet" href="<?php echo $cdnpublic?>font-awesome/4.7.0/css/font-awesome.min.css">
<link rel="stylesheet" href="<?php echo $cdnpublic?>twitter-bootstrap/3.4.1/css/bootstrap.min.css">
<script src="<?php echo $cdnpublic?>jquery/1.12.4/jquery.min.js"></script>
```

**安全检查**：模板文件首行必须包含安全检查：

```php
<?php
if(!defined('IN_CRONLITE'))exit();
?>
```

### 8.2.4 模板开发步骤

**步骤一：创建模板目录**

```
template/mytemplate/
```

**步骤二：创建必要文件**

至少创建 `index.php`（首页模板），建议同时创建 `head.php` 和 `foot.php`：

```
template/mytemplate/
├── index.php
├── head.php
├── foot.php
└── assets/
    ├── css/
    │   └── index.css
    └── images/
```

**步骤三：编写模板内容**

`index.php` 示例：

```php
<?php
if(!defined('IN_CRONLITE'))exit();
require INDEX_ROOT.'head.php';
?>
<section class="hero">
    <div class="container">
        <h1>欢迎使用<?php echo $conf['sitename']?></h1>
        <p>提供免签约支付宝、微信支付、QQ钱包</p>
        <a href="/user/" class="btn btn-primary">登录商户</a>
        <a href="/user/reg.php" class="btn btn-default">注册商户</a>
    </div>
</section>

<section class="features">
    <div class="container">
        <div class="row">
            <div class="col-md-4">
                <h3>多种支付方式</h3>
                <p>支持支付宝、微信、QQ钱包等主流支付方式</p>
            </div>
            <div class="col-md-4">
                <h3>费率超低</h3>
                <p>每笔交易手续费低至2%</p>
            </div>
            <div class="col-md-4">
                <h3>自动结算</h3>
                <p>满一定金额自动提现到您的账户</p>
            </div>
        </div>
    </div>
</section>
<?php require INDEX_ROOT.'foot.php';?>
```

**步骤四：在后台切换模板**

管理后台 → 系统设置 → 基本设置 → 首页模板 → 选择 `mytemplate`

**步骤五：可选的页面覆盖**

如果新模板需要自定义其他页面（如 `doc.php`、`agreement.php`），只需在模板目录下创建同名文件即可。未覆盖的页面会自动回退到 `default` 模板。

---

## 8.3 支付方式扩展指南

支付方式（Payment Type）是系统对支付渠道的抽象分类，如"支付宝"、"微信支付"、"QQ钱包"等。每种支付方式在 `pre_type` 表中有一条记录，通过 `id` 和 `name` 标识。

### 8.3.1 在 pre_type 表新增支付方式

`pre_type` 表结构：

| 字段 | 类型 | 说明 |
|------|------|------|
| `id` | int(11) | 自增主键，支付方式ID |
| `name` | varchar(30) | 支付方式英文名称（唯一标识，如 `alipay`、`wxpay`、`usdt`） |
| `device` | int(1) | 设备限制：`0`=全部、`1`=PC端、`2`=移动端 |
| `showname` | varchar(30) | 支付方式显示名称（如"支付宝"、"微信支付"） |
| `status` | tinyint(1) | 启用状态：`0`=禁用、`1`=启用 |

**系统预置的支付方式**（来自 `install.sql`）：

| id | name | showname | status |
|----|------|----------|--------|
| 1 | alipay | 支付宝 | 1 |
| 2 | wxpay | 微信支付 | 1 |
| 3 | qqpay | QQ钱包 | 1 |
| 4 | bank | 网银支付 | 0 |
| 5 | jdpay | 京东支付 | 0 |
| 6 | paypal | PayPal | 0 |

**新增支付方式的SQL示例**：

```sql
INSERT INTO `pre_type` (`name`, `device`, `showname`, `status`) VALUES ('usdt', 0, 'USDT支付', 1);
```

**注意事项**：
- `name` 字段必须与插件的 `$info['types']` 数组中的值一致
- `name` 字段有联合索引 `KEY name (name, device)`，确保同一设备类型下名称唯一
- 新增后需要在用户组配置中添加对应的通道和费率，否则商户无法使用

### 8.3.2 在插件的 $info['types'] 属性中声明支持

插件的 `$info['types']` 数组声明了该插件支持哪些支付方式，数组中的值必须与 `pre_type.name` 对应：

```php
static public $info = [
    'name'  => 'epay',
    'types' => ['alipay','qqpay','wxpay','bank','jdpay'],  // 支持多种支付方式
    // ...
];
```

```php
static public $info = [
    'name'  => 'wxpaysl',
    'types' => ['wxpay'],  // 仅支持微信支付
    // ...
];
```

```php
static public $info = [
    'name'  => 'usdt',
    'types' => ['usdt'],  // 仅支持USDT支付
    // ...
];
```

**插件注册流程**：当管理员点击"更新插件"时，`Plugin::updateAll()` 方法会：
1. 清空 `pre_plugin` 表
2. 扫描 `plugins/` 目录下的所有插件
3. 读取每个插件的 `$info` 属性
4. 校验 `$info['name']` 是否与目录名一致
5. 将插件信息写入 `pre_plugin` 表，其中 `types` 字段以逗号分隔存储

```sql
-- pre_plugin 表中的 types 字段存储格式
INSERT INTO pre_plugin (name, showname, author, link, types) VALUES ('epay', '彩虹易支付', '彩虹', '', 'alipay,qqpay,wxpay,bank,jdpay');
```

### 8.3.3 在用户组配置中添加新支付方式的通道和费率

新增支付方式后，必须在用户组（`pre_group`）的 `info` JSON 中添加对应配置，否则该支付方式对商户不可用。详见 8.4 节。

**操作步骤**：

1. 在管理后台 → 用户组管理 → 编辑用户组
2. 在配置中添加新支付方式的通道和费率
3. 或者直接修改数据库：

```sql
-- 获取当前用户组配置
SELECT info FROM pre_group WHERE gid=0;

-- 假设新增的 usdt 支付方式 ID 为 7，需要添加如下配置：
-- "7":{"type":"","channel":"-1","rate":""}
-- channel=-1 表示随机可用通道，rate 为空表示使用通道默认费率
```

---

## 8.4 用户组扩展指南

用户组（User Group）是聚合易支付中控制商户可用支付方式、通道分配和费率的核心机制。每个商户属于一个用户组，用户组决定了商户可以使用哪些支付方式、走哪个通道、以及费率是多少。

### 8.4.1 pre_group 表配置说明

`pre_group` 表结构：

| 字段 | 类型 | 说明 |
|------|------|------|
| `gid` | int(11) | 用户组ID，`0` 为默认用户组 |
| `name` | varchar(30) | 用户组名称 |
| `info` | varchar(1024) | 通道与费率配置JSON |
| `isbuy` | tinyint(1) | 是否允许购买：`0`=否、`1`=是 |
| `price` | decimal(10,2) | 购买价格 |
| `sort` | int(10) | 排序值 |
| `expire` | int(10) | 有效期（天），`0`=永久 |
| `settle_open` | int(1) | 结算开关覆盖：`0`=跟随系统、`1`=开启 |
| `settle_type` | int(1) | 结算方式覆盖 |
| `settings` | text | 其他设置JSON |

**默认用户组**（`gid=0`）在系统安装时自动创建：

```sql
INSERT INTO `pre_group` (`gid`, `name`, `info`) VALUES
(0, '默认用户组', '{"1":{"type":"","channel":"-1","rate":""},"2":{"type":"","channel":"-1","rate":""},"3":{"type":"","channel":"-1","rate":""}}');
```

### 8.4.2 通道与费率配置JSON格式

`info` 字段是一个 JSON 字符串，以支付方式ID为键，每个支付方式的配置格式如下：

```json
{
    "1": {
        "type": "",
        "channel": "-1",
        "rate": ""
    },
    "2": {
        "type": "",
        "channel": "5",
        "rate": "98.00"
    },
    "3": {
        "type": "roll",
        "channel": "101",
        "rate": "97.50"
    }
}
```

**字段说明**：

| 字段 | 说明 |
|------|------|
| `type` | 通道类型：空字符串=普通通道、`roll`=轮询组 |
| `channel` | 通道分配：`-1`=随机可用通道、`0`=关闭该支付方式、正整数=指定通道ID或轮询组ID |
| `rate` | 费率（百分比）：空字符串=使用通道默认费率、具体数值如 `98.00` 表示商户拿到98% |

**通道选择逻辑**（由 `Channel::getSubmitInfo()` 实现）：

1. **`channel = 0`**：该支付方式对当前用户组关闭，返回 `false`
2. **`channel = -1`**：随机选择一个该支付方式下状态为开启的通道，支持金额限额过滤（`paymin`/`paymax`）
3. **`channel = 正整数`**：
   - 如果 `type = "roll"`：从轮询组中按规则选择通道
   - 否则：使用指定ID的通道
4. **`rate` 为空**：使用通道自身的默认费率（`pre_channel.rate`）
5. **`rate` 有值**：使用用户组配置的费率覆盖通道默认费率

**轮询组机制**：

轮询组（`pre_roll` 表）允许将多个通道组合在一起，按权重或轮询方式分配：

```sql
CREATE TABLE pre_roll (
    id int(11) auto_increment,
    type int(11),          -- 支付方式ID
    name varchar(30),      -- 轮询组名称
    kind int(1) DEFAULT 0, -- 分配方式：0=顺序轮询、1=加权随机
    info text,             -- 通道配置，格式："通道ID:权重,通道ID:权重,..."
    status tinyint(1),     -- 状态
    `index` int(11),       -- 当前轮询索引（顺序轮询时使用）
    PRIMARY KEY (id)
);
```

**轮询组 `info` 格式示例**：

```
5:3,8:2,12:5
```

表示包含3个通道：通道5权重3、通道8权重2、通道12权重5。

### 8.4.3 用户组购买功能配置

用户组支持购买功能，商户可以通过支付购买升级到更高级的用户组：

| 字段 | 说明 |
|------|------|
| `isbuy` | 设置为 `1` 允许购买 |
| `price` | 购买价格（元） |
| `expire` | 有效期天数，`0` 表示永久 |

**购买流程**：
1. 商户在前端选择要购买的用户组
2. 系统创建一笔 `tid=4`（购买用户组）的订单
3. 商户完成支付后，`processOrder()` 函数检测到 `tid=4`，执行用户组变更
4. 调用 `changeUserGroup($uid, $gid, $endtime)` 更新商户的用户组

**用户组购买页面**：`user/groupbuy.php`，展示各用户组的通道和费率信息。

---

## 8.5 实名认证扩展指南

聚合易支付支持多种实名认证方式，商户在注册后可能需要完成实名认证才能使用全部功能。认证方式通过系统配置 `$conf['cert_open']` 控制。

### 8.5.1 认证方式

系统支持以下四种认证方式，由 `$conf['cert_open']` 配置值决定：

#### 支付宝快捷认证（certmethod=0）

**配置值**：`$conf['cert_open'] = 1` 或 `3`

**认证流程**：
1. 商户填写真实姓名和身份证号
2. 系统调用支付宝身份认证初始化接口（`AlipayCertdocService::preconsult()`），获取 `verify_id`
3. 将 `verify_id` 存入 `pre_user.certtoken` 字段
4. 生成支付宝认证二维码，商户使用支付宝扫码完成认证
5. 支付宝回调通知认证结果
6. 系统验证回调签名，更新用户认证状态

**相关文件**：
- `plugins/alipay/inc/AlipayCertdocService.php` — 支付宝认证服务
- `plugins/alipay/inc/AlipayCertifyService.php` — 支付宝身份认证服务
- `user/ajax2.php` — 认证AJAX处理

**通道配置**：需要配置 `$conf['cert_channel']` 指向一个支付宝支付通道。

#### 微信快捷认证（certmethod=1）

**配置值**：`$conf['cert_open'] = 4`

**认证流程**：
1. 商户填写真实姓名和身份证号
2. 系统调用腾讯云人脸核身接口（`QcloudFaceid::GetRealNameAuthToken()`），获取认证token
3. 生成微信小程序认证二维码
4. 商户使用微信扫码完成认证
5. 腾讯云回调通知认证结果

**相关文件**：
- `includes/lib/QcloudFaceid.php` — 腾讯云人脸核身服务
- 配置项：`$conf['cert_qcloud_secretid']`、`$conf['cert_qcloud_secretkey']`

#### 手机号三要素认证（certmethod=2）

**配置值**：`$conf['cert_open'] = 2`

**认证流程**：
1. 商户填写真实姓名、身份证号
2. 系统读取商户绑定的手机号码
3. 调用阿里云手机号三要素验证接口（`check_cert($idcard, $name, $phone)`）
4. 验证姓名、身份证号、手机号三者一致性
5. 验证通过则更新认证状态

**相关函数**：`check_cert($idcard, $name, $phone)`（定义在 `includes/functions.php`）

**配置项**：
- `$conf['cert_appcode']` — 阿里云API AppCode

**接口地址**：`http://phone3.market.alicloudapi.com/phonethree`

**返回值**：

```php
// 验证通过
['code'=>0, 'msg'=>'认证通过']

// 验证失败
['code'=>-1, 'msg'=>'信息不一致']

// 接口异常
['code'=>-2, 'msg'=>'返回结果解析失败']
```

#### 人工审核认证（certmethod=3）

**配置值**：`$conf['cert_open'] = 5`

**认证流程**：
1. 商户提交认证信息
2. 管理员在后台手动审核
3. 审核通过后管理员手动更新认证状态

### 8.5.2 认证回调处理

认证完成后，系统会更新 `pre_user` 表的以下字段：

| 字段 | 类型 | 说明 |
|------|------|------|
| `cert` | tinyint(4) | 认证状态：`0`=未认证、`1`=已认证 |
| `certtype` | tinyint(4) | 认证类型：`0`=个人、`1`=企业 |
| `certmethod` | tinyint(4) | 认证方式：`0`=支付宝快捷、`1`=微信快捷、`2`=手机三要素、`3`=人工审核 |
| `certno` | varchar(18) | 身份证号码 |
| `certname` | varchar(32) | 真实姓名 |
| `certtime` | datetime | 认证时间 |
| `certtoken` | varchar(64) | 认证令牌（支付宝认证时的 verify_id） |
| `certcorpno` | varchar(30) | 营业执照号码（企业认证） |
| `certcorpname` | varchar(80) | 公司名称（企业认证） |

**认证费用**：如果配置了 `$conf['cert_money']`，认证成功后会从商户余额中扣除相应费用：

```php
if($conf['cert_money']>0){
    changeUserMoney($uid, $conf['cert_money'], false, '实名认证');
}
```

### 8.5.3 企业认证（certtype=1）

企业认证在个人认证的基础上，额外需要提供企业信息：

**所需字段**：
- `certcorpname` — 公司名称
- `certcorpno` — 统一社会信用代码
- `certname` — 法人姓名
- `certno` — 法人身份证号

**企业信息校验**：系统调用 `check_corp_cert($companyName, $creditNo, $legalPerson)` 函数验证企业信息：

```php
function check_corp_cert($companyName, $creditNo, $legalPerson){
    global $conf;
    $appcode = $conf['cert_appcode2'];
    $url = 'http://companythree.shumaidata.com/companythree/check';
    $post = ['companyName'=>$companyName, 'creditNo'=>$creditNo, 'legalPerson'=>$legalPerson];
    $data = get_curl($url.'?'.http_build_query($post), 0, 0, 0, 0, 0, 0,
        ['Authorization: APPCODE '.$appcode, 'Content-Type: application/x-www-form-urlencoded; charset=UTF-8']);
    // ...
}
```

**配置项**：
- `$conf['cert_corpopen']` — 是否开启企业认证
- `$conf['cert_appcode2']` — 企业信息校验API AppCode

**个人认证升级企业认证**：已通过个人认证的商户可以通过 `certificate.php?certtype=1&upgrade=1` 升级为企业认证。

---

## 8.6 转账通道扩展指南

转账功能用于将商户的结算款项自动转入其支付宝、微信或银行卡账户。系统通过 `transfer_do()` 函数统一调度，根据转账类型和通道插件选择具体的转账实现。

### 8.6.1 支持的转账类型

系统当前支持以下转账类型：

#### 支付宝转账（transferToAlipay）

**触发条件**：`transfer_do('alipay', ...)` 且通道插件不是 `jeepay`

**实现**：调用支付宝单笔转账接口，支持转账到支付宝账号或支付宝用户ID（以 `2088` 开头的16位数字）

**相关文件**：`plugins/alipay/inc/AlipayTransferService.php`

**特点**：
- 自动识别收款账户类型（用户ID或登录账号）
- 支持实名校验和不校验两种模式
- 使用 `$conf['transfer_name']` 作为付款方名称

#### 微信企业付款（transferToWeixin）

**触发条件**：`transfer_do('wxpay', ...)` 且通道插件不是 `jeepay` 或 `wxpayn`

**实现**：调用微信企业付款接口，转账到用户 OpenID

**相关文件**：`plugins/wxpay/inc/WxPay.Api.php`

**特点**：
- 支持实名校验（`FORCE_CHECK`）和不校验（`NO_CHECK`）
- 金额单位为分（乘以100转换）
- 需要配置API证书

#### 微信商家转账（transferToWeixinNew）

**触发条件**：`transfer_do('wxpay', ...)` 且通道插件为 `wxpayn`

**实现**：调用微信商家转账到零钱V3接口

**相关文件**：`plugins/wxpayn/inc/WxPayApi.class.php`

**特点**：
- 使用批量转账接口，单次转账数量为1
- 支持用户姓名加密传输
- 异步转账模式：提交后需查询转账结果
- 转账状态：`PROCESSING`（处理中）、`SUCCESS`（成功）、`FAIL`（失败）

#### QQ钱包付款（transferToQQ）

**触发条件**：`transfer_do('qqpay', ...)`

**实现**：调用QQ钱包企业付款接口

**相关文件**：`plugins/qqpay/inc/qpayMchAPI.class.php`

**特点**：
- 支持实名校验
- 使用QQ号作为收款账户

#### 银行卡转账（transferToBank）

**触发条件**：`transfer_do('bank', ...)`

**实现**：调用支付宝转账到银行卡接口

**相关文件**：`plugins/alipay/inc/AlipayTransferService.php`

**特点**：
- 通过支付宝的转账到银行卡功能实现
- 需要收款人姓名和银行卡号

#### Jeepay聚合转账（transferJeepay）

**触发条件**：`transfer_do('alipay'/'wxpay', ...)` 且通道插件为 `jeepay`

**实现**：调用Jeepay聚合支付平台的转账接口

**相关文件**：`plugins/jeepay/jeepay_plugin.php`

**特点**：
- 支持支付宝和微信两种转账类型
- 通过Jeepay平台统一处理

### 8.6.2 转账函数接口

```php
function transfer_do($type, $channel, $out_trade_no, $payee_account, $payee_real_name, $money)
```

**参数说明**：

| 参数 | 类型 | 说明 |
|------|------|------|
| `$type` | string | 转账类型：`alipay`、`wxpay`、`qqpay`、`bank` |
| `$channel` | array | 支付通道配置数组（来自 `Channel::get()`） |
| `$out_trade_no` | string | 商户转账订单号 |
| `$payee_account` | string | 收款人账号（支付宝账号/用户ID、微信OpenID、QQ号、银行卡号） |
| `$payee_real_name` | string | 收款人真实姓名（可为空，为空时不校验姓名） |
| `$money` | float | 转账金额（元） |

**返回值格式**：

所有转账函数返回统一的数组格式：

```php
// 转账成功
[
    'code'    => 0,       // 0=接口调用成功
    'ret'     => 1,       // 1=转账成功、0=转账失败（业务级）
    'msg'     => 'success',
    'orderid' => 'xxx',   // 网关转账订单号
    'paydate' => '2024-01-01 12:00:00', // 转账完成时间
]

// 转账失败（业务级，如姓名不匹配、余额不足）
[
    'code'     => 0,
    'ret'      => 0,
    'msg'      => '[NAME_MISMATCH]用户姓名校验失败',
    'sub_code' => 'NAME_MISMATCH',
    'sub_msg'  => '用户姓名校验失败',
]

// 转账失败（系统级，如接口异常）
[
    'code' => -1,
    'msg'  => '未知错误',
]
```

**`code` 字段含义**：

| 值 | 含义 |
|----|------|
| `0` | 接口调用成功，需进一步检查 `ret` 判断转账是否成功 |
| `-1` | 接口调用失败或系统异常，可重试 |

### 8.6.3 新增转账通道的步骤

要新增一种转账通道（如新增银联转账），需要以下步骤：

**步骤一：在 `transfer_do()` 函数中添加新的类型分支**

编辑 `includes/functions.php`，在 `transfer_do()` 函数中添加新的类型判断：

```php
function transfer_do($type, $channel, $out_trade_no, $payee_account, $payee_real_name, $money){
    global $conf;
    if($type == 'alipay'){
        // ... 现有逻辑
    }elseif($type == 'wxpay'){
        // ... 现有逻辑
    }elseif($type == 'qqpay'){
        // ... 现有逻辑
    }elseif($type == 'bank'){
        // ... 现有逻辑
    }elseif($type == 'unionpay'){
        // 新增银联转账
        return transferToUnionPay($channel, $out_trade_no, $payee_account, $payee_real_name, $money);
    }
    return false;
}
```

**步骤二：实现转账函数**

在 `includes/functions.php` 中添加新的转账函数，遵循统一的返回值格式：

```php
function transferToUnionPay($channel, $out_trade_no, $payee_account, $payee_real_name, $money){
    global $conf;
    define("PAY_ROOT", PLUGIN_ROOT.'unionpay/');

    // 调用银联转账接口
    require_once PAY_ROOT."inc/UnionPayService.php";
    $service = new UnionPayService($config);
    $result = $service->transfer($out_trade_no, $payee_account, $payee_real_name, $money);

    $data = array();
    if($result['code'] == 'SUCCESS'){
        $data['code'] = 0;
        $data['ret'] = 1;
        $data['msg'] = 'success';
        $data['orderid'] = $result['transaction_id'];
        $data['paydate'] = $result['pay_time'];
    }elseif($result['code'] == 'FAIL'){
        $data['code'] = 0;
        $data['ret'] = 0;
        $data['msg'] = '['.$result['error_code'].']'.$result['error_msg'];
        $data['sub_code'] = $result['error_code'];
        $data['sub_msg'] = $result['error_msg'];
    }else{
        $data['code'] = -1;
        $data['msg'] = '未知错误';
    }
    return $data;
}
```

**步骤三：创建对应的支付插件目录和SDK文件**

```
plugins/unionpay/
├── unionpay_plugin.php    # 支付插件主文件
└── inc/
    └── UnionPayService.php # 转账服务类
```

**步骤四：在管理后台配置结算通道**

确保结算通道使用了支持新转账类型的插件，并在系统设置中开启对应的转账方式（`$conf['transfer_alipay']`、`$conf['transfer_wxpay']` 等）。

**步骤五：更新结算流程**

如果新转账类型需要在结算页面展示（如新增"银联"结算选项），还需要：
1. 在 `pre_config` 中添加对应的配置项（如 `transfer_unionpay`）
2. 在结算页面（`user/settle.php`）中添加对应的选项
3. 在结算处理逻辑中添加对新类型的支持

**注意事项**：
- 转账函数内部通过 `define("PAY_ROOT", ...)` 设置插件目录，确保SDK文件路径正确
- 转账金额在微信/QQ场景下需要乘以100转换为分
- 所有转账函数必须返回统一的数组格式，确保结算流程能正确处理结果
- 转账结果会写入 `pre_settle.transfer_status` 和 `pre_settle.transfer_result` 字段
