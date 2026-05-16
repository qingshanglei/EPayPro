# 三、核心功能模块解析

## 3.1 支付流程模块

聚合易支付的支付流程是整个系统的核心链路，涉及从商户发起支付请求到最终资金到账的完整闭环。整体流程可用以下文字流程图描述：

```
商户系统 → submit.php(签名验证+风控+通道分配) → 插件submit() → 支付页面渲染
                                                              ↓
用户完成支付 ← 第三方支付平台 ← ─────────────────────────────────┘
      ↓
支付平台回调 → pay.php(回调路由) → 插件notify()/return() → Payment::processOrder()
                                                              ↓
                                                    processOrder()资金处理
                                                              ↓
                                                    do_notify()异步通知商户
```

### 3.1.1 API支付提交（submit.php）

[submit.php](file:///www/wwwroot/pay/submit.php) 是商户通过API接口发起支付请求的入口文件，处理完整的支付提交流程。该文件同时支持GET和POST两种请求方式，核心处理步骤如下：

**第一步：接收参数与初始化**

```php
// submit.php L2-L9
if(isset($_GET['pid'])){
    $queryArr=$_GET;
}elseif(isset($_POST['pid'])){
    $queryArr=$_POST;
}else{
    exit('你还未配置支付接口商户！');
}
```

系统优先从GET参数中获取商户ID（pid），若不存在则尝试POST参数。设置 `$is_defend = true` 开启防御模式，`$nosession = true` 禁用Session以提升并发性能。随后引入公共文件 `includes/common.php` 完成数据库连接、配置加载等初始化工作。

**第二步：MD5签名验证**

```php
// submit.php L31-L37
use \lib\PayUtils;
$prestr=PayUtils::createLinkstring(PayUtils::argSort(PayUtils::paraFilter($queryArr)));
$pid=intval($queryArr['pid']);
$userrow=$DB->getRow("SELECT `uid`,`gid`,`key`,`money`,`mode`,`pay`,`cert`,`status`,`channelinfo`,`qq`,`ordername` FROM `pre_user` WHERE `uid`='{$pid}' LIMIT 1");
if(!PayUtils::md5Verify($prestr, $queryArr['sign'], $userrow['key']))sysmsg('签名校验失败，请返回重试！');
```

签名验证流程：
1. `PayUtils::paraFilter()` 过滤掉空值和sign/sign_type参数
2. `PayUtils::argSort()` 按键名ASCII升序排序
3. `PayUtils::createLinkstring()` 拼接为 `key=value&key=value` 格式字符串
4. `PayUtils::md5Verify()` 将拼接字符串追加商户密钥后做MD5，与传入的sign参数比对

签名算法为：`md5(参数排序拼接字符串 + 商户密钥)`，这是一种典型的MD5签名方案，确保请求参数未被篡改。

**第三步：商户状态检查**

```php
// submit.php L39-L75
if($userrow['status']==0 || $userrow['pay']==0)sysmsg('商户已封禁，无法支付！');
if($userrow['pay']==2 && $conf['user_review']==1)sysmsg('商户没通过审核，请联系官方客服进行审核');
```

系统执行多层商户状态校验：
- **封禁检查**：`status==0` 或 `pay==0` 表示商户被禁用
- **审核检查**：`pay==2` 且系统开启审核模式时，未审核商户无法支付
- **实名认证检查**：`$conf['cert_force']==1` 时，未实名商户（`cert==0`）无法收款
- **QQ绑定检查**：`$conf['forceqq']==1` 时，未填写QQ的商户无法收款
- **域名白名单检查**：`$conf['pay_domain_forbid']==1` 时，通知URL域名必须在 `pre_domain` 表中授权

**第四步：商品风控检查**

```php
// submit.php L77-L90
if(!empty($conf['blockname'])){
    $block_name = explode('|',$conf['blockname']);
    foreach($block_name as $rows){
        if(!empty($rows) && strpos($name,$rows)!==false){
            $DB->exec("INSERT INTO `pre_risk` ...");
            sysmsg($conf['blockalert']?$conf['blockalert']:'该商品禁止出售');
        }
    }
}
if($conf['blockips']){
    $blockips = explode('|',$conf['blockips']);
    if(in_array($clientip, $blockips))sysmsg('系统异常无法完成付款');
}
```

风控检查包含两个维度：
- **商品名称黑名单**（`blockname`）：用 `|` 分隔的关键词列表，匹配到时记录风控日志到 `pre_risk` 表并拦截
- **IP黑名单**（`blockips`）：用 `|` 分隔的IP列表，命中则直接拦截

**第五步：订单创建或复用**

```php
// submit.php L94-L111
$firstGetChannel = true;
$oldorder = $DB->getRow("SELECT * FROM `pre_order` WHERE `uid`=:uid AND `out_trade_no`=:out_trade_no", ...);
if($oldorder && time() - strtotime($oldorder['addtime']) < 864000){
    if($oldorder['status']>0){
        sysmsg('该订单('.$out_trade_no.')已完成支付，请勿重复发起支付');
    }
    if(round($oldorder['money'],2) != round($money,2) || ...){
        sysmsg('该订单('.$out_trade_no.')支付参数有变化，请更换订单号重新发起支付');
    }
    $trade_no=$oldorder['trade_no'];
    // 若订单已获取过通道信息且支付方式未变，则复用
    if($oldorder['type'] > 0 && $oldorder['channel'] > 0 && $oldorder['realmoney'] > 0 && $oldorder['getmoney'] > 0 && $typeid == $oldorder['type']){
        $firstGetChannel = false;
    }
}else{
    $trade_no=date("YmdHis").rand(11111,99999);
    // 插入新订单
}
```

订单号生成规则为 `YmdHis` + 5位随机数（如 `2026051614302512345`）。系统支持24小时内（实际代码为864000秒即10天）同订单号复用：若订单已支付则拒绝，若参数变化则拒绝，若已分配通道且支付方式未变则复用通道信息避免重复分配。

**第六步：通道分配**

```php
// submit.php L120-L140
if($firstGetChannel){
    $submitData = \lib\Channel::submit($type, $userrow['gid'], $money);
    if(!$submitData){
        echo "<script>window.location.replace('./cashier.php?trade_no={$trade_no}&sitename={$sitename}&other=1');</script>";
        exit;
    }
    // 费率计算...
}else{
    $submitData = \lib\Channel::get($oldorder['channel']);
    // 复用已有通道信息...
}
```

调用 `Channel::submit()` 根据支付方式名称、用户组ID和金额分配通道。若分配失败，跳转收银台并标记 `other=1` 显示"当前支付方式暂时关闭维护"提示。

**第七步：费率计算**

```php
// submit.php L126-L132
if($userrow['mode']==1){ //订单加费模式
    $realmoney = round($money*(100+100-$submitData['rate'])/100,2);
    $getmoney = $money;
}else{
    $realmoney = $money;
    $getmoney = round($money*$submitData['rate']/100,2);
}
```

系统支持两种费率模式：
- **普通模式**（`mode==0`）：用户支付金额=订单金额（realmoney=money），商户到账金额=订单金额×费率（getmoney=money×rate/100）
- **订单加费模式**（`mode==1`）：商户到账金额=订单金额（getmoney=money），用户实际支付金额=订单金额×(200-rate)/100（realmoney=money×(200-rate)/100），即手续费由付款方承担

例如：订单金额100元，费率98%，普通模式下用户支付100元商户到账98元；加费模式下商户到账100元，用户支付102元。

**第八步：金额随机增减（防风控）**

```php
// submit.php L155-L156
if(!empty($conf['pay_payaddstart'])&&$conf['pay_payaddstart']!=0&&...&&$realmoney>=$conf['pay_payaddstart'])
    $realmoney = $realmoney + randomFloat(round($conf['pay_payaddmin'],2),round($conf['pay_payaddmax'],2));
```

当订单实际支付金额达到 `pay_payaddstart` 阈值时，在 `pay_payaddmin` 到 `pay_payaddmax` 范围内随机增减金额，目的是防止支付平台因相同金额频繁交易触发风控。`randomFloat()` 函数返回保留两位小数的随机浮点数。

**第九步：插件调用**

```php
// submit.php L172-L177
try{
    $result = \lib\Plugin::loadForSubmit($submitData['plugin'], $trade_no);
    \lib\Payment::echoDefault($result);
}catch(Exception $e){
    sysmsg($e->getMessage());
}
```

调用 `Plugin::loadForSubmit()` 加载对应支付插件并执行 `submit()` 方法，获取支付结果后通过 `Payment::echoDefault()` 渲染支付页面。

**第十步：支付页面渲染**

`Payment::echoDefault()` 根据 `$result['type']` 值选择不同的渲染方式（详见3.4.1节），包括跳转、HTML输出、JSON输出、扫码页面、URL Scheme、同步回调、错误提示等。

### 3.1.2 收银台支付（cashier.php → submit2.php）

当商户未指定支付方式（`type` 参数为空）或通道分配失败时，系统会跳转到收银台让用户选择支付方式。

**cashier.php 收银台页面**

[cashier.php](file:///www/wwwroot/pay/cashier.php) 展示可用支付方式列表供用户选择：

```php
// cashier.php L14-L15
$gid = $DB->getColumn("SELECT gid FROM pre_user WHERE uid='{$row['uid']}' limit 1");
$paytype = \lib\Channel::getTypes($gid);
```

核心逻辑：
1. 根据订单的商户UID查询其用户组ID（gid）
2. 调用 `Channel::getTypes()` 获取该用户组可用的支付方式列表
3. 微信环境自动将微信支付排到第一位（通过检测 `MicroMessenger` UA）
4. 若 `other=1` 参数存在，显示"当前支付方式暂时关闭维护"提示
5. 用户点击支付方式后，前端JS跳转到 `submit2.php`：

```javascript
// cashier.php L123-L127
$(document).on("click", ".immediate_pay", function () {
    var value = $(".types").find('.active').attr('value');
    var trade_no = $("input[name='trade_no']").val();
    window.location.href='./submit2.php?typeid='+value+'&trade_no='+trade_no;
});
```

**submit2.php 收银台支付提交**

[submit2.php](file:///www/wwwroot/pay/submit2.php) 与 submit.php 的核心区别在于：
- 通过支付方式ID（`typeid`）而非名称分配通道，调用 `Channel::submit2()` 而非 `Channel::submit()`
- 订单已存在，从 `pre_order` 表读取订单信息
- 特殊处理直清模式下的充值余额和购买用户组订单（使用平台商户的通道配置）

```php
// submit2.php L39-L48
if($submitData['mode']==1 && ($order['tid']==2 || $order['tid']==4)){
    $userrow = $DB->getRow("SELECT `gid`,`money`,`mode`,`channelinfo` FROM `pre_user` WHERE `uid`='{$conf['reg_pay_uid']}' LIMIT 1");
    if($order['tid']==2) $rate = $submitData['rate'];
    $submitData = \lib\Channel::submit2($typeid, $userrow['gid'], $order['money']);
    if($order['tid']==2) $submitData['rate'] = $rate;
    $submitData['mode'] = 0;
}
```

这段逻辑确保：充值余额（tid=2）和购买用户组（tid=4）的订单在直清模式下使用平台商户的通道，避免手续费被扣除两次。

### 3.1.3 支付回调路由（pay.php）

[pay.php](file:///www/wwwroot/pay/pay.php) 是所有支付回调的统一路由入口，采用URL路径解析模式：

**URL格式**：`/pay/{func}/{trade_no}/`

```php
// pay.php L14-L24
$s = isset($_GET['s'])?$_GET['s']:exit('404 Not Found');
$sitename=isset($_GET['sitename'])?base64_decode($_GET['sitename']):'';
$submit2=true;

try{
    $result = \lib\Plugin::loadForPay($s);
    \lib\Payment::echoDefault($result);
}catch(Exception $e){
    sysmsg($e->getMessage());
}
```

`Plugin::loadForPay()` 内部通过正则解析URL：

```php
// Plugin.php L41-L61
if(preg_match('/^(.[a-zA-Z0-9]+)\/([0-9]+)\/$/',$s, $matchs)){
    $func = $matchs[1];       // 功能名
    $trade_no = $matchs[2];   // 平台订单号
    
    $order = $DB->getRow("SELECT A.*,B.name typename FROM pre_order A left join pre_type B on A.type=B.id WHERE trade_no=:trade_no limit 1", ...);
    $channel = \lib\Channel::get($order['channel'], $channelinfo);
    
    return self::loadClass($channel['plugin'], $func, $trade_no);
}
```

**func 参数含义**：

| func值 | 说明 | 调用的插件方法 |
|--------|------|---------------|
| `notify` | 异步通知回调 | `插件::notify()` |
| `return` | 同步跳转回调 | `插件::return()` |
| `alipay` | 支付宝支付（mapi） | `插件::alipay()` |
| `wxpay` | 微信支付（mapi） | `插件::wxpay()` |
| `qqpay` | QQ钱包支付（mapi） | `插件::qqpay()` |
| `bank` | 云闪付支付（mapi） | `插件::bank()` |
| `jdpay` | 京东支付（mapi） | `插件::jdpay()` |
| `submit` | 页面支付提交 | `插件::submit()` |

回调流程中，系统会根据订单号查询订单信息和通道信息，然后加载对应的支付插件类并调用相应方法。插件方法返回的结果通过 `Payment::echoDefault()` 渲染输出。

### 3.1.4 订单状态查询（getshop.php）

[getshop.php](file:///www/wwwroot/pay/getshop.php) 提供订单支付状态查询接口，供前端轮询使用：

```php
// getshop.php L5-L24
$trade_no=isset($_GET['trade_no'])?daddslashes($_GET['trade_no']):exit('No trade_no!');

$row=$DB->getRow("SELECT * FROM pre_order WHERE trade_no='{$trade_no}' limit 1");
if($row['status']>=1){
    if(!empty($row['endtime']) && time() - strtotime($row['endtime']) > 300){
        $jumpurl = '/payok.html';
    }else{
        $url=creat_callback($row);
        $jumpurl = $url['return'];
    }
    if($row['status'] == 2){
        $jumpurl = '/payerr.html';
    }
    echo json_encode(['code'=>1, 'msg'=>'付款成功', 'backurl'=>$jumpurl]);
}else{
    echo json_encode(['code'=>-1, 'msg'=>'未付款']);
}
```

返回JSON格式：
- **已支付**（`status>=1`）：返回 `code=1`，附带回调跳转URL
  - 支付完成超过5分钟：跳转到 `/payok.html` 静态页面
  - 5分钟内：跳转到商户的 `return_url`
  - `status==2`（异常订单）：跳转到 `/payerr.html`
- **未支付**（`status==0`）：返回 `code=-1`

### 3.1.5 微信点金计划（gold.php）

[gold.php](file:///www/wwwroot/pay/gold.php) 实现微信支付点金计划的iframe页面。微信点金计划是微信支付为服务商提供的支付后商户信息展示能力：

```php
// gold.php L10-L21
$sub_mch_id = $_GET['sub_mch_id'];
$out_trade_no = $_GET['out_trade_no'];
$check_code = $_GET['check_code'];

$order = $DB->getRow("SELECT * FROM pre_order WHERE trade_no=:trade_no limit 1", ...);
if(!$order)$order = $DB->getRow("SELECT * FROM pre_order WHERE api_trade_no=:trade_no limit 1", ...);
$trade_no = $order['trade_no'];
$jump_url = $siteurl.'pay/return/'.$trade_no.'/';
```

页面加载微信点金计划JS SDK（`jgoldplan-1.0.0.js`），通过 `postMessage` 与微信支付页面通信：
1. 发送 `jumpOut` 动作，指定跳转URL为支付同步回调地址
2. 发送 `onIframeReady` 动作，设置 `displayStyle` 为 `SHOW_OFFICIAL_PAGE`

## 3.2 通道管理模块

通道管理模块是支付系统的调度核心，负责根据商户配置和支付方式分配合适的支付通道。[Channel.php](file:///www/wwwroot/pay/includes/lib/Channel.php) 实现了完整的通道分配逻辑。

### 3.2.1 Channel类核心方法

| 方法 | 说明 | 调用场景 |
|------|------|---------|
| `submit($type, $gid, $money, $device)` | 按支付方式名称分配通道 | submit.php API支付提交 |
| `submit2($typeid, $gid, $money)` | 按支付方式ID分配通道 | submit2.php 收银台支付 |
| `getSubmitInfo($typeid, $typename, $gid, $money)` | 核心通道分配逻辑 | 被submit/submit2调用 |
| `getTypes($gid)` | 获取商户可用支付方式 | cashier.php 收银台 |
| `getChannelFromRoll($channel, $money)` | 轮询组通道分配 | getSubmitInfo内部调用 |
| `get($id, $channelinfo)` | 获取通道详情 | 多处使用 |
| `info($id)` | 获取通道简要信息 | paypage/index.php |
| `getWeixin($id)` | 获取微信公众号配置 | weixinOpenId() |

### 3.2.2 通道分配逻辑

`getSubmitInfo()` 是通道分配的核心方法，完整逻辑如下：

**1. 查询用户组配置**

```php
// Channel.php L76-L77
if($gid>0)$groupinfo=$DB->getColumn("SELECT info FROM pre_group WHERE gid='$gid' LIMIT 1");
if(!$groupinfo)$groupinfo=$DB->getColumn("SELECT info FROM pre_group WHERE gid=0 LIMIT 1");
```

优先查询商户所属用户组的配置，若不存在则回退到默认用户组（gid=0）。`pre_group.info` 字段存储JSON格式的通道配置。

**2. 根据配置决定通道选择方式**

用户组配置JSON格式为：
```json
{
    "typeid1": {"type":"", "channel":"-1", "rate":""},
    "typeid2": {"type":"roll", "channel":"3", "rate":"98"},
    ...
}
```

其中 `typeid` 是支付方式的数字ID，每个支付方式对应的配置项含义：

| channel值 | 含义 | 处理方式 |
|-----------|------|---------|
| `0` | 关闭该支付方式 | 直接返回 `false` |
| `-1` | 随机选择可用通道 | 查询 `pre_channel` 表中该支付方式的所有可用通道，随机选取 |
| 正数 | 指定通道ID或轮询组ID | 若type为"roll"则走轮询组逻辑，否则直接使用指定通道 |

**3. channel=-1 随机选择逻辑**

```php
// Channel.php L92-L113
elseif($channel==-1){
    $rows=$DB->getAll("SELECT id,plugin,status,rate,apptype,mode,paymin,paymax FROM pre_channel WHERE type='$typeid' AND status=1 AND daystatus=0");
    if(count($rows)>0){
        $newrows = [];
        foreach($rows as $row){
            if($money>0 && !empty($row['paymin']) && $row['paymin']>0 && $money<$row['paymin'])continue;
            if($money>0 && !empty($row['paymax']) && $row['paymax']>0 && $money>$row['paymax'])continue;
            $newrows[] = $row;
        }
        if(count($newrows)>0){
            $row = $newrows[array_rand($newrows)];
        }else{
            $row = $rows[array_rand($rows)];
        }
        // ...
    }
}
```

随机选择流程：
1. 查询该支付方式下所有状态正常（`status=1`）且未日限额封禁（`daystatus=0`）的通道
2. 根据支付金额过滤不符合限额的通道（`paymin`/`paymax`）
3. 若过滤后仍有通道，从中随机选取一个（`array_rand`）
4. 若过滤后无通道，从全部可用通道中随机选取（忽略限额）
5. 若用户组未配置费率（`rate` 为空），使用通道默认费率

**4. 通道不可用过滤条件**

- `status=0`：通道被禁用
- `daystatus=1`：通道当日交易额已达上限（日限额封禁）
- 金额低于 `paymin` 或高于 `paymax`

### 3.2.3 轮询组机制

轮询组（`pre_roll` 表）允许将多个通道组合，按策略分配流量，实现负载均衡和容灾切换。

**轮询组数据结构**

`pre_roll` 表关键字段：
- `id`：轮询组ID
- `info`：通道配置，格式为 `channelId:weight,channelId:weight`（如 `1:50,2:30,3:20`）
- `kind`：轮询策略（0=顺序轮询，1=加权随机）
- `index`：当前轮询位置（顺序轮询时使用）
- `status`：状态（1=启用）

**顺序轮询（kind=0）**

```php
// Channel.php L233-L236
$channel = $newinfo[$row['index']]['name'];
$index = ($row['index'] + 1) % count($newinfo);
$DB->exec("UPDATE pre_roll SET `index`='$index' WHERE id='{$row['id']}'");
```

按 `index` 顺序依次选择通道，选择后 `index` 递增并取模循环。每次分配后会更新数据库中的 `index` 值。

**加权随机（kind=1）**

```php
// Channel.php L255-L268
static private function random_weight($arr){
    $weightSum = 0;
    foreach ($arr as $value) {
        $weightSum += $value['weight'];
    }
    if($weightSum<=0)return false;
    $randNum = rand(1, $weightSum);
    foreach ($arr as $k => $v) {
        if ($randNum <= $v['weight']) {
            return $v['name'];
        }
        $randNum -=$v['weight'];
    }
}
```

加权随机算法：计算总权重，生成1到总权重之间的随机数，按顺序累减权重直到随机数落入某个通道的权重区间。

**轮询组过滤逻辑**

```php
// Channel.php L211-L229
$channelids = [];
foreach($info as $inforow){
    $channelids[] = $inforow['name'];
}
$channelids = implode(',',$channelids);
$rows=$DB->getAll("SELECT id,paymin,paymax FROM pre_channel WHERE id IN ($channelids) AND status=1 AND daystatus=0");
$newids = [];
foreach($rows as $channelrow){
    if($money>0 && !empty($channelrow['paymin']) && $channelrow['paymin']>0 && $money<$channelrow['paymin'])continue;
    if($money>0 && !empty($channelrow['paymax']) && $channelrow['paymax']>0 && $money>$channelrow['paymax'])continue;
    $newids[] = $channelrow['id'];
}
if(count($newids)==0)return false;
```

在轮询组分配前，先根据金额限额和通道状态过滤不可用通道，过滤后若无可用通道则返回 `false`。

### 3.2.4 用户组通道配置

用户组配置存储在 `pre_group.info` 字段中，JSON格式如下：

```json
{
    "1": {"type":"", "channel":"-1", "rate":""},
    "2": {"type":"roll", "channel":"5", "rate":"97.5"},
    "3": {"type":"", "channel":"10", "rate":"98"},
    "4": {"type":"", "channel":"0", "rate":""}
}
```

字段说明：
- **type**：`"roll"` 表示channel值指向轮询组ID，空字符串表示指向普通通道ID
- **channel**：`0`=关闭该支付方式，`-1`=随机选择可用通道，正数=指定通道ID或轮询组ID
- **rate**：该用户组在此支付方式下的费率（百分比，如98表示98%），为空则使用通道默认费率

`getTypes()` 方法在收银台场景下使用，遍历所有支付方式并根据用户组配置过滤不可用的支付方式，同时设置各支付方式的费率显示。

## 3.3 插件系统模块

插件系统是聚合易支付的核心扩展机制，通过统一的插件接口对接不同的第三方支付平台。[Plugin.php](file:///www/wwwroot/pay/includes/lib/Plugin.php) 管理插件的加载、调用和注册。

### 3.3.1 Plugin类核心方法

| 方法 | 说明 |
|------|------|
| `getList()` | 扫描plugins目录获取所有插件名称列表 |
| `getConfig($name)` | 获取插件的 `$info` 静态属性（元信息） |
| `loadForSubmit($plugin, $trade_no, $ismapi)` | 加载插件处理支付提交（调用submit或mapi方法） |
| `loadForPay($s)` | 加载插件处理支付回调（解析URL后调用对应方法） |
| `loadForJsapi($trade_no, $type, $money, $name, $openid)` | 加载插件处理JSAPI支付（调用jsapi方法） |
| `refund($trade_no, $money, &$message)` | 调用插件的退款方法 |
| `exists($name)` | 检查插件文件是否存在 |
| `isrefund($name)` | 检查插件是否支持退款 |
| `updateAll()` | 更新插件数据库表（清空后重新注册所有插件） |
| `get($name)` | 从数据库获取插件信息 |
| `getAll()` | 从数据库获取所有插件信息 |

### 3.3.2 插件加载流程

`loadClass()` 是插件加载的核心私有方法：

```php
// Plugin.php L86-L108
static private function loadClass($plugin, $func, $trade_no){
    $filename = PLUGIN_ROOT.$plugin.'/'.$plugin.'_plugin.php';
    $classname = '\\'.$plugin.'_plugin';
    if (file_exists($filename)) {
        define("IN_PLUGIN", true);
        define("PAY_PLUGIN", $plugin);
        define("PAY_ROOT", PLUGIN_ROOT.PAY_PLUGIN.'/');
        define("TRADE_NO", $trade_no);
        include $filename;
        if (class_exists($classname, false) && method_exists($classname, $func)) {
            return $classname::$func();
        } else {
            if($func == 'mapi' && class_exists($classname, false) && method_exists($classname, 'submit')){
                global $siteurl;
                return ['type'=>'jump','url'=>$siteurl.'pay/submit/'.TRADE_NO.'/'];
            }else{
                throw new Exception('插件方法不存在:'.$func);
            }
        }
    }else{
        throw new Exception('Pay file not found');
    }
}
```

加载步骤：
1. 构造插件文件路径：`plugins/{name}/{name}_plugin.php`
2. 定义4个全局常量：
   - `IN_PLUGIN`：标记当前在插件环境中
   - `PAY_PLUGIN`：当前插件名称
   - `PAY_ROOT`：插件根目录路径
   - `TRADE_NO`：当前交易号
3. 包含插件文件
4. 调用插件类的静态方法（如 `epay_plugin::submit()`）
5. 若请求 `mapi` 方法但插件未实现，降级为跳转到页面支付方式

**loadForSubmit 加载流程**

```php
// Plugin.php L67-L84
static public function loadForSubmit($plugin, $trade_no, $ismapi=false){
    global $DB,$conf,$order,$channel,$ordername,$userrow;
    // ...
    $channel = \lib\Channel::get($order['channel'], $channelinfo);
    $channel['apptype'] = explode(',',$channel['apptype']);
    $ordername = !empty($conf['ordername'])?ordername_replace($conf['ordername'],$order['name'],$order['uid'],$trade_no):$order['name'];
    return self::loadClass($plugin, $func, $trade_no);
}
```

该方法在支付提交时调用，会预先加载通道信息和订单名称替换。`ordername_replace()` 支持以下占位符替换：
- `[name]` → 原始商品名称
- `[order]` → 平台订单号
- `[qq]` → 商户QQ号
- `[time]` → 当前时间戳

**loadForPay 加载流程**

```php
// Plugin.php L39-L65
static public function loadForPay($s){
    global $DB,$conf,$order,$channel,$ordername;
    if(preg_match('/^(.[a-zA-Z0-9]+)\/([0-9]+)\/$/',$s, $matchs)){
        $func = $matchs[1];
        $trade_no = $matchs[2];
        $order = $DB->getRow("SELECT A.*,B.name typename FROM pre_order A left join pre_type B on A.type=B.id WHERE trade_no=:trade_no limit 1", ...);
        $channel = \lib\Channel::get($order['channel'], $channelinfo);
        return self::loadClass($channel['plugin'], $func, $trade_no);
    }
}
```

该方法在支付回调时调用，从URL解析出功能名和订单号，查询订单和通道信息后加载插件。

### 3.3.3 插件接口定义

每个插件必须实现为一个PHP类，类名格式为 `{name}_plugin`，放置在 `plugins/{name}/{name}_plugin.php` 文件中。以 [epay_plugin](file:///www/wwwroot/pay/plugins/epay/epay_plugin.php) 为示例：

**$info 属性（必须）**

```php
static public $info = [
    'name'        => 'epay',           // 插件英文名称，需和目录名一致
    'showname'    => '彩虹易支付',      // 插件显示名称
    'author'      => '彩虹',            // 插件作者
    'link'        => '',               // 作者链接
    'types'       => ['alipay','qqpay','wxpay','bank','jdpay'], // 支持的支付方式
    'inputs' => [                      // 后台配置项
        'appurl' => ['name'=>'接口地址', 'type'=>'input', 'note'=>'...'],
        'appid'  => ['name'=>'商户ID', 'type'=>'input', 'note'=>''],
        'appkey' => ['name'=>'商户密钥', 'type'=>'input', 'note'=>''],
        'appswitch' => ['name'=>'是否使用mapi接口', 'type'=>'select', 'options'=>[0=>'否',1=>'是']],
    ],
    'select' => null,                  // 下拉选项配置
    'note' => '',                      // 填写说明
    'bindwxmp' => false,              // 是否支持绑定微信公众号
    'bindwxa' => false,               // 是否支持绑定微信小程序
];
```

`inputs` 定义了后台通道配置页面需要填写的参数，支持的type有 `input`（文本输入）和 `select`（下拉选择）。可选的配置项键名有：`appid`、`appkey`、`appsecret`、`appurl`、`appmchid`，也可自定义键名。

**插件方法**

| 方法 | 必须 | 说明 | 返回值格式 |
|------|------|------|-----------|
| `submit()` | 是 | 页面支付提交 | `['type'=>'jump/html', 'url'=>'...'/'data'=>'...']` |
| `mapi()` | 否 | API支付提交 | 同submit，或按支付方式名分方法 |
| `notify()` | 是 | 异步通知处理 | `['type'=>'html', 'data'=>'success/fail']` |
| `return()` | 是 | 同步回调处理 | `['type'=>'error/return', 'msg'=>'...'/'url'=>'...']` |
| `jsapi()` | 否 | JSAPI支付 | 返回支付参数供前端调用 |
| `refund()` | 否 | 退款 | `['code'=>0, 'ret'=>1, 'msg'=>'success']` |

**返回值type类型说明**

| type值 | 说明 | 附加字段 |
|--------|------|---------|
| `jump` | 跳转到指定URL | `url` |
| `html` | 输出HTML内容 | `data` |
| `json` | 输出JSON数据 | `data` |
| `page` | 包含指定页面模板 | `page`（模板名）, `data`（模板变量） |
| `qrcode` | 扫码支付页面 | `url`（二维码内容）, `page`（模板名） |
| `scheme` | URL Scheme跳转 | `url`（scheme内容）, `page`（模板名） |
| `return` | 同步回调跳转 | `url` |
| `error` | 错误提示 | `msg` |

### 3.3.4 已有插件清单

系统共包含33个支付插件，覆盖主流支付渠道：

| 插件名 | 目录名 | 说明 |
|--------|--------|------|
| 支付宝官方 | `alipay` | 支付宝官方接口，支持扫码、H5、JSAPI等 |
| 支付宝旧版 | `aliold` | 支付宝旧版接口 |
| 支付宝服务商 | `alipaysl` | 支付宝服务商模式接口 |
| 微信支付官方 | `wxpay` | 微信支付V2接口，支持扫码、H5、JSAPI |
| 微信支付V3 | `wxpayn` | 微信支付V3接口（商家转账） |
| 微信支付V3+ | `wxpaynp` | 微信支付V3增强版 |
| 微信支付服务商 | `wxpaysl` | 微信支付服务商模式接口 |
| QQ钱包 | `qqpay` | QQ钱包支付接口 |
| 彩虹易支付 | `epay` | 彩虹易支付对接接口 |
| Jeepay | `jeepay` | Jeepay聚合支付平台接口 |
| PayJS | `payjs` | PayJS微信支付接口 |
| PayPal | `paypal` | PayPal国际支付接口 |
| 威富通 | `swiftpass` | 威富通支付接口 |
| 威富通V2 | `swiftpass2` | 威富通V2接口 |
| 汇付天下 | `adapay` | 汇付天下Adapay接口 |
| 通联支付 | `allinpay` | 通联支付接口 |
| 银联商务 | `chinaums` | 银联商务接口 |
| 银联在线 | `unionpay` | 银联在线支付接口 |
| 京东支付 | `jdpay` | 京东支付接口 |
| 多拉宝 | `duolabao` | 多拉宝支付接口 |
| 易生支付 | `mirfupay` | 易生支付接口 |
| 开鑫支付 | `kayixin` | 开鑫支付接口 |
| 迅虎支付 | `xunhupay` | 迅虎支付接口 |
| 迅虎支付V2 | `xunhupay2` | 迅虎支付V2接口 |
| XorPay | `xorpay` | XorPay支付接口 |
| 码支付 | `vmq` | 码支付（V免签）接口 |
| USDT支付 | `usdt` | USDT数字货币支付接口 |
| 易码支付 | `ympay` | 易码支付接口 |
| 银盛支付 | `ysepay` | 银盛支付接口 |
| 随通支付 | `sytpay` | 随通支付接口 |
| 我爱支付 | `woaizf` | 我爱支付接口 |
| 掌易付 | `zyu` | 掌易付接口 |
| 张一搜 | `zhangyishou` | 张一搜支付接口 |
| QXApp | `qxapp` | QXApp支付接口 |

## 3.4 订单处理模块

订单处理模块负责支付完成后的资金变动、商户通知等核心业务逻辑，由 [Payment.php](file:///www/wwwroot/pay/includes/lib/Payment.php) 和 [functions.php](file:///www/wwwroot/pay/includes/functions.php) 中的相关函数实现。

### 3.4.1 Payment类

`Payment` 类提供4个静态方法：

**echoDefault() — 页面支付返回处理**

```php
// Payment.php L9-L44
static public function echoDefault($result){
    $type = $result['type'];
    switch($type){
        case 'jump':   // 跳转到指定URL
            echo '<script>window.location.replace(\''.$result['url'].'\');</script>';
            break;
        case 'html':   // 直接输出HTML
            echo $result['data'];
            break;
        case 'json':   // 输出JSON
            echo json_encode($result['data']);
            break;
        case 'page':   // 包含页面模板
            include PAYPAGE_ROOT.$result['page'].'.php';
            break;
        case 'qrcode': // 扫码支付页面
        case 'scheme': // URL Scheme跳转页面
            $code_url = $result['url'];
            include PAYPAGE_ROOT.$result['page'].'.php';
            break;
        case 'return': // 同步回调跳转
            returnTemplate($result['url']);
            break;
        case 'error':  // 错误提示
            sysmsg($result['msg']);
            break;
    }
}
```

各type的处理方式：
- **jump**：通过JS `window.location.replace` 跳转
- **html**：直接输出HTML内容（如表单自动提交）
- **json**：输出JSON数据
- **page/qrcode/scheme**：包含 `paypage/` 目录下的PHP模板文件，模板中可使用 `$order`、`$code_url` 等变量
- **return**：调用 `returnTemplate()` 渲染跳转页面（通过JS的 `window.atob` 解码Base64 URL后跳转）
- **error**：调用 `sysmsg()` 显示错误信息

**echoJson() — API支付返回处理**

```php
// Payment.php L47-L73
static public function echoJson($result){
    $json['code'] = 1;
    $json['trade_no'] = TRADE_NO;
    switch($type){
        case 'jump':    $json['payurl'] = $result['url']; break;
        case 'qrcode':  $json['qrcode'] = $result['url']; break;
        case 'scheme':  $json['urlscheme'] = $result['url']; break;
        case 'error':   $json['code'] = -2; $json['msg'] = $result['msg']; break;
        default:        $json['payurl'] = $siteurl.'pay/submit/'.TRADE_NO.'/'; break;
    }
    exit(json_encode($json));
}
```

API模式返回JSON格式，包含 `code`（状态码）、`trade_no`（平台订单号）和支付信息字段。

**processOrder() — 订单回调处理**

```php
// Payment.php L76-L104
static public function processOrder($isnotify, $order, $api_trade_no, $buyer){
    if($order['status']==0){
        if($DB->exec("UPDATE `pre_order` SET `status`=1 WHERE `trade_no`='".$order['trade_no']."'")){
            $data = ['endtime'=>'NOW()', 'date'=>'CURDATE()'];
            if(!empty($api_trade_no)) $data['api_trade_no'] = $api_trade_no;
            if(!empty($buyer)) $data['buyer'] = $buyer;
            $DB->update('order', $data, ['trade_no'=>$order['trade_no']]);
            processOrder($order, $isnotify); // 调用全局函数
        }
    }elseif(empty($order['api_trade_no']) && !empty($api_trade_no)){
        $data = ['api_trade_no'=>$api_trade_no];
        if(!empty($buyer)) $data['buyer'] = $buyer;
        $DB->update('order', $data, ['trade_no'=>$order['trade_no']]);
    }
    if(!$isnotify){
        // 同步回调跳转处理
        if(!empty($order['endtime']) && time() - strtotime($order['endtime']) > 300){
            $jumpurl = '/payok.html';
        }else{
            $url=creat_callback($order);
            $jumpurl = $url['return'];
        }
        returnTemplate($jumpurl);
    }
}
```

关键逻辑：
1. 使用 `UPDATE SET status=1` 的行锁特性防止重复处理（只有status=0时才能更新成功）
2. 更新订单完成时间、第三方交易号、付款人信息
3. 调用全局 `processOrder()` 函数处理资金变动
4. 若是同步回调（非异步通知），渲染跳转页面
5. 支付完成超过5分钟后，同步回调不再跳转回商户网站，而是跳转到 `/payok.html`

**updateOrder() — 更新订单信息**

```php
// Payment.php L107-L112
static public function updateOrder($trade_no, $api_trade_no, $buyer = null){
    $data = ['api_trade_no'=>$api_trade_no];
    if(!empty($buyer)) $data['buyer'] = $buyer;
    $DB->update('order', $data, ['trade_no'=>$trade_no]);
}
```

辅助函数，用于更新订单的第三方交易号和付款人信息，不改变订单状态。

### 3.4.2 processOrder函数

[functions.php](file:///www/wwwroot/pay/includes/functions.php) 中的 `processOrder()` 全局函数（L513-L577）是订单完成后资金变动的核心逻辑：

```php
function processOrder($srow,$notify=true){
    $addmoney = $srow['getmoney'];
    $reducemoney = round($srow['realmoney']-$srow['getmoney'], 2);
    if($reducemoney<0)$reducemoney=0;
```

根据订单类型（`tid` 字段）执行不同的资金处理逻辑：

**tid=1：商户注册**

```php
if($srow['tid']==1){
    changeUserMoney($srow['uid'], $addmoney, true, '订单收入', $srow['trade_no']);
    $info = unserialize($CACHE->read('reg_'.$srow['trade_no']));
    if($info){
        $key = random(32);
        $paystatus = $conf['user_review']==1?2:1;
        $sds=$DB->exec("INSERT INTO `pre_user` (`upid`, `key`, `money`, `email`, `phone`, `addtime`, `pay`, `settle`, `keylogin`, `apply`, `status`) VALUES (...)");
        $uid=$DB->lastInsertId();
        $pwd = getMd5Pwd($info['pwd'], $uid);
        $DB->exec("UPDATE `pre_user` SET `pwd` ='{$pwd}' WHERE `uid`='$uid'");
        // 发送注册成功邮件
    }
}
```

付费注册流程：
1. 给收款商户增加收入
2. 从缓存中读取注册信息（`reg_{trade_no}` 缓存键）
3. 创建新商户账号，生成32位随机密钥
4. 密码使用 `getMd5Pwd()` 加密（双重MD5 + salt）
5. 发送注册成功通知邮件（包含商户ID和密钥）

**tid=2：余额充值**

```php
}else if($srow['tid']==2){
    changeUserMoney($srow['uid'], $addmoney, true, '余额充值', $srow['trade_no']);
}
```

直接给商户余额增加充值金额。

**tid=3：聚合收款码**

```php
}else if($srow['tid']==3){
    if($channel['mode']==1){
        if($reducemoney>0)
            changeUserMoney($srow['uid'], $reducemoney, false, '在线收款服务费', $srow['trade_no']);
    }else{
        changeUserMoney($srow['uid'], $addmoney, true, '在线收款', $srow['trade_no']);
    }
}
```

两种模式：
- **直清模式**（`channel['mode']==1`）：用户全额付款给商户，平台从商户余额中扣除服务费（`realmoney - getmoney`）
- **普通模式**：平台收取全部款项，将扣除手续费后的金额（`getmoney`）加到商户余额

**tid=4：购买用户组**

```php
}else if($srow['tid']==4){
    $param = json_decode($srow['param'], true);
    changeUserGroup($srow['uid'], $param['gid'], $param['endtime']);
}
```

从订单参数中解析目标用户组ID和到期时间，调用 `changeUserGroup()` 修改商户的用户组。

**其他：普通订单**

```php
}else{
    if($channel['mode']==1){
        if($reducemoney>0)
            changeUserMoney($srow['uid'], $reducemoney, false, '订单服务费', $srow['trade_no']);
    }else{
        changeUserMoney($srow['uid'], $addmoney, true, '订单收入', $srow['trade_no']);
    }
    $url=creat_callback($srow);
    if(do_notify($url['notify'])){
        $DB->exec("UPDATE pre_order SET notify=0 WHERE trade_no='{$srow['trade_no']}'");
    }elseif($notify==true){
        $DB->exec("UPDATE pre_order SET notify=1,notifytime=date_add(now(), interval 1 minute) WHERE trade_no='{$srow['trade_no']}'");
    }
}
```

普通订单处理：
1. 根据通道模式处理资金变动（同收款码逻辑）
2. 调用 `creat_callback()` 生成带签名的回调URL
3. 调用 `do_notify()` 向商户发送异步通知
4. 若通知成功，设置 `notify=0`；若失败，设置 `notify=1` 并安排1分钟后重试

**通道日限额检查**

```php
if($channel['daytop']>0){
    $cachekey = 'daytop'.$channel['id'].date("Ymd");
    $nowmoney = $CACHE->read($cachekey);
    if(!$nowmoney)$nowmoney=0;
    $nowmoney=$nowmoney+$srow['money'];
    $CACHE->save($cachekey, $nowmoney);
    if($nowmoney>=$channel['daytop']){
        $DB->exec("UPDATE pre_channel SET daystatus=1 WHERE id='{$channel['id']}'");
    }
}
```

每次订单完成后累加通道当日交易额到缓存，若达到 `daytop` 阈值则将通道标记为 `daystatus=1`（日限额封禁），该通道当日不再被分配。

### 3.4.3 通知重试机制

[cron.php](file:///www/wwwroot/pay/cron.php) 中的 `do=notify` 任务实现了异步通知的重试机制：

```php
// cron.php L115-L146
elseif($_GET['do']=='notify'){
    $limit = 20;
    for($i=0;$i<$limit;$i++){
        $srow=$DB->getRow("SELECT * FROM pre_order WHERE (TO_DAYS(NOW()) - TO_DAYS(endtime) <= 1) AND notify>0 AND notifytime<NOW() LIMIT 1");
        if(!$srow)break;

        $notify = $srow['notify'] + 1;
        if($notify == 2){
            $interval = '2 minute';
        }elseif($notify == 3){
            $interval = '16 minute';
        }elseif($notify == 4){
            $interval = '36 minute';
        }elseif($notify == 5){
            $interval = '1 hour';
        }else{
            $DB->exec("UPDATE pre_order SET notify=-1,notifytime=NULL WHERE trade_no='{$srow['trade_no']}'");
            continue;
        }
        $DB->exec("UPDATE pre_order SET notify={$notify},notifytime=date_add(now(), interval {$interval}) WHERE trade_no='{$srow['trade_no']}'");

        $url=creat_callback($srow);
        if(do_notify($url['notify'])){
            $DB->exec("UPDATE pre_order SET notify=0,notifytime=NULL WHERE trade_no='{$srow['trade_no']}'");
        }
    }
}
```

重试时间表（从首次通知失败开始计算）：

| 重试次数 | 距上次通知的间隔 | 距首次通知的累计时间 |
|---------|----------------|-------------------|
| 第1次（首次失败后） | 1分钟 | 1分钟 |
| 第2次 | 2分钟 | 3分钟 |
| 第3次 | 16分钟 | 19分钟 |
| 第4次 | 36分钟 | 55分钟 |
| 第5次 | 1小时 | 1小时55分钟 |
| 超过5次 | 标记 `notify=-1` | 不再重试 |

`notify` 字段含义：
- `0`：通知成功或无需通知
- `1-5`：已重试次数（等待下次重试）
- `-1`：重试超过5次，标记为通知失败

此外还有 `do=notify2` 任务，用于对 `notify=-1` 的订单进行最终补救重试（不设间隔，直接重试）。

`do_notify()` 函数通过CURL请求商户的 `notify_url`，判断响应中是否包含 `success`/`SUCCESS`/`Success` 字符串来确定通知是否成功。

## 3.5 用户系统模块

### 3.5.1 商户注册与登录

**注册方式**

系统支持两种注册方式，由 `$conf['reg_pay']` 配置决定：

1. **付费注册**（`reg_pay=1`）：
   - 用户填写邮箱/手机号 + 验证码 + 密码
   - 创建 `tid=1` 的注册订单，金额为 `reg_pay_price`
   - 注册信息暂存到缓存（`reg_{trade_no}`），支付成功后才创建商户
   - 收款商户ID为 `reg_pay_uid` 配置项

2. **免费注册**（`reg_pay=0`）：
   - 用户填写邮箱/手机号 + 验证码 + 密码
   - 直接创建商户账号
   - 根据审核配置决定初始状态（`user_review=1` 时 `pay=2` 需审核）

验证码发送支持邮箱和手机号两种方式（`verifytype` 配置），手机验证码通过短信API发送，邮箱验证码通过邮件发送。

**登录方式**

[user/ajax.php](file:///www/wwwroot/pay/user/ajax.php) 的 `act=login` 处理登录逻辑，支持以下方式：

| 登录方式 | type值 | 说明 |
|---------|--------|------|
| 账号密码 | 1 | 使用邮箱或手机号 + 密码登录 |
| 商户密钥 | 0 | 使用商户ID + 密钥登录 |
| QQ快捷登录 | — | 通过OAuth绑定QQ的uid |
| 支付宝快捷登录 | — | 通过OAuth绑定支付宝的uid |
| 微信扫码登录 | — | 通过微信OpenID绑定 |

登录验证流程：
```php
// user/ajax.php L68-L84
if($userrow && ($type==0 && $pass==$userrow['key'] || $type==1 && $pass==$userrow['pwd'])) {
    $uid = $userrow['uid'];
    $session=md5($uid.$userrow['key'].$password_hash);
    $expiretime=time()+604800; // 7天有效期
    $token=authcode("{$uid}\t{$session}\t{$expiretime}", 'ENCODE', SYS_KEY);
    setcookie("user_token", $token, time() + 604800);
}
```

**认证机制**

[member.php](file:///www/wwwroot/pay/includes/member.php) 实现了基于Cookie Token的认证机制：

```php
// member.php L13-L23
if(isset($_COOKIE["user_token"])){
    $token=authcode(daddslashes($_COOKIE['user_token']), 'DECODE', SYS_KEY);
    list($uid, $sid, $expiretime) = explode("\t", $token);
    $uid = intval($uid);
    $userrow=$DB->getRow("SELECT * FROM pre_user WHERE uid=:uid limit 1", [':uid'=>$uid]);
    $session=md5($userrow['uid'].$userrow['key'].$password_hash);
    if($session==$sid && $expiretime>time()) {
        $islogin2=1;
    }
}
```

Token结构：`authcode(uid\tsession_hash\texpiretime)`，其中：
- `uid`：商户ID
- `session_hash`：`md5(uid + 商户密钥 + password_hash)` 生成的会话标识
- `expiretime`：过期时间戳（7天有效期）

`authcode()` 函数使用基于RC4的对称加密算法，密钥为 `SYS_KEY`（系统密钥），提供Token的加密和解密功能。

### 3.5.2 用户组与权限

用户组通过 `pre_group` 表管理，每个用户组包含以下配置：

- **通道与费率配置**（`info` JSON字段）：控制各支付方式的通道分配和费率
- **结算配置**：结算方式、结算费率等
- **购买配置**：用户组可购买，设置价格和有效期

商户通过 `pre_user.gid` 关联用户组，`endtime` 字段记录用户组到期时间。购买用户组时创建 `tid=4` 的订单，支付成功后调用 `changeUserGroup()` 更新商户的用户组。

### 3.5.3 余额管理与资金明细

**changeUserMoney()函数**

```php
// functions.php L579-L599
function changeUserMoney($uid, $money, $add=true, $type=null, $orderid=null){
    global $DB;
    if($money<=0)return;
    if($type=='订单退款'){
        $isrefund = $DB->getColumn("SELECT id FROM pre_record WHERE type='订单退款' AND trade_no='{$orderid}' LIMIT 1");
        if($isrefund)return; // 防止重复退款
    }
    $DB->beginTransaction();
    $oldmoney = $DB->getColumn("SELECT money FROM pre_user WHERE uid='{$uid}' LIMIT 1 FOR UPDATE");
    if($add == true){
        $action = 1;
        $newmoney = round($oldmoney+$money, 2);
    }else{
        $action = 2;
        $newmoney = round($oldmoney-$money, 2);
    }
    $res = $DB->exec("UPDATE pre_user SET money='{$newmoney}' WHERE uid='{$uid}'");
    $DB->exec("INSERT INTO `pre_record` (`uid`, `action`, `money`, `oldmoney`, `newmoney`, `type`, `trade_no`, `date`) VALUES (...)");
    $DB->commit();
    return $res;
}
```

关键设计：
1. **事务保护**：使用 `beginTransaction()` + `commit()` 确保原子性
2. **行锁**：`SELECT ... FOR UPDATE` 获取行级排他锁，防止并发修改导致余额不一致
3. **防重复退款**：退款操作先检查 `pre_record` 表是否已有记录
4. **资金明细**：每次变动都记录到 `pre_record` 表，包含操作前后余额
5. **action字段**：1=收入，2=支出

`pre_record` 资金明细表字段：
- `uid`：商户ID
- `action`：操作类型（1=收入，2=支出）
- `money`：变动金额
- `oldmoney`：变动前余额
- `newmoney`：变动后余额
- `type`：变动类型（订单收入/余额充值/在线收款/在线收款服务费/订单服务费/订单退款/自动结算等）
- `trade_no`：关联订单号
- `date`：变动时间

### 3.5.4 结算与提现

**自动结算**

[cron.php](file:///www/wwwroot/pay/cron.php) 的 `do=settle` 任务实现自动结算：

```php
// cron.php L20-L51
if($conf['settle_open']==1 || $conf['settle_open']==3){
    $rs=$DB->query("SELECT * from pre_user where money>={$conf['settle_money']} and account is not null and username is not null and settle=1 and status=1");
    while($row = $rs->fetch()){
        if($conf['cert_force']==1 && $row['cert']==0) continue;
        if($conf['settle_rate']>0){
            $fee=round($row['money']*$conf['settle_rate']/100,2);
            if($fee<$conf['settle_fee_min'])$fee=$conf['settle_fee_min'];
            if($fee>$conf['settle_fee_max'])$fee=$conf['settle_fee_max'];
            $realmoney=$row['money']-$fee;
        }else{
            $realmoney=$row['money'];
        }
        if($DB->exec("INSERT INTO `pre_settle` (...) VALUES (...)")){
            changeUserMoney($row['uid'], $row['money'], false, '自动结算');
        }
    }
}
```

自动结算条件：
- 余额达到结算门槛（`settle_money`）
- 已设置收款账号（`account` 和 `username` 不为空）
- 开启自动结算（`settle=1`）
- 商户状态正常（`status=1`）
- 若强制实名认证，需已完成认证

结算手续费计算：
- `settle_rate`：结算手续费率（百分比）
- `settle_fee_min`：最低手续费
- `settle_fee_max`：最高手续费
- 实际手续费 = `max(settle_fee_min, min(余额×settle_rate/100, settle_fee_max))`

**企业付款**

系统通过 `transfer_do()` 函数实现企业付款到商户收款账号，支持以下渠道：

| 类型 | 函数 | 说明 |
|------|------|------|
| 支付宝 | `transferToAlipay()` | 支付宝单笔转账到账户/银行卡 |
| 微信V2 | `transferToWeixin()` | 微信企业付款到零钱 |
| 微信V3 | `transferToWeixinNew()` | 微信商家转账到零钱（V3接口） |
| QQ钱包 | `transferToQQ()` | QQ钱包企业付款 |
| 银行卡 | `transferToBank()` | 支付宝转账到银行卡 |
| Jeepay | `transferJeepay()` | 通过Jeepay平台转账 |

`transfer_do()` 是统一入口函数，根据付款类型和通道插件选择对应的转账实现。返回值统一格式：
```php
['code'=>0, 'ret'=>1, 'msg'=>'success', 'orderid'=>'...', 'paydate'=>'...']  // 成功
['code'=>0, 'ret'=>0, 'msg'=>'[错误码]错误信息', 'sub_code'=>'...', 'sub_msg'=>'...']  // 业务失败
['code'=>-1, 'msg'=>'错误信息']  // 系统错误
```

## 3.6 管理后台模块

管理后台位于 [admin688/](file:///www/wwwroot/pay/admin688/) 目录，提供平台运营管理功能。

### 3.6.1 管理员认证

```php
// member.php L4-L11
if(isset($_COOKIE["admin_token"])){
    $token=authcode(daddslashes($_COOKIE['admin_token']), 'DECODE', SYS_KEY);
    list($user, $sid, $expiretime) = explode("\t", $token);
    $session=md5($conf['admin_user'].$conf['admin_pwd'].$password_hash);
    if($session==$sid && $expiretime>time()) {
        $islogin=1;
    }
}
```

管理员认证机制与商户类似，使用 `admin_token` Cookie，Token结构为 `authcode(admin_user\tsession_hash\texpiretime)`，其中 `session_hash = md5(管理员用户名 + 管理员密码 + password_hash)`。

### 3.6.2 管理功能列表

根据 [head.php](file:///www/wwwroot/pay/admin688/head.php) 导航和目录文件，管理后台包含以下功能模块：

| 文件 | 功能 | 说明 |
|------|------|------|
| **首页与概览** | | |
| `index.php` | 后台首页 | 显示订单总数、商户数量、总余额、结算总额、支付方式/通道收入统计 |
| **订单管理** | | |
| `order.php` | 订单列表 | 查看、搜索、管理所有支付订单 |
| `ajax_order.php` | 订单AJAX | 订单数据查询接口 |
| `export.php` | 导出订单 | 导出订单数据为文件 |
| `download.php` | 下载文件 | 下载导出的文件 |
| **结算管理** | | |
| `slist.php` | 结算列表 | 查看结算记录 |
| `settle.php` | 结算处理 | 审核和处理结算申请 |
| `ajax_settle.php` | 结算AJAX | 结算操作接口 |
| **商户管理** | | |
| `ulist.php` | 用户列表 | 查看、编辑、封禁商户 |
| `glist.php` | 用户组设置 | 配置用户组的通道和费率 |
| `group.php` | 用户组购买 | 设置可购买的用户组 |
| `record.php` | 资金明细 | 查看商户资金变动记录 |
| `uset.php` | 商户设置 | 修改商户信息 |
| `ustat.php` | 支付统计 | 商户支付数据统计 |
| `domain.php` | 授权域名 | 管理商户支付域名白名单 |
| `ajax_user.php` | 商户AJAX | 商户操作接口 |
| **支付接口** | | |
| `pay_channel.php` | 支付通道 | 配置支付通道参数（appid/appkey/appsecret等） |
| `pay_type.php` | 支付方式 | 管理支付方式（支付宝/微信/QQ等） |
| `pay_plugin.php` | 支付插件 | 查看和管理已安装的支付插件 |
| `pay_roll.php` | 通道轮询 | 配置通道轮询组 |
| `pay_weixin.php` | 公众号小程序 | 管理微信公众号和小程序配置 |
| `ajax_pay.php` | 支付AJAX | 支付配置操作接口 |
| **系统设置** | | |
| `set.php` | 系统设置 | 多模块配置（网站信息/支付结算/企业付款/快捷登录/实名认证/邮箱短信/模板/计划任务等） |
| `gonggao.php` | 公告配置 | 管理网站公告 |
| **其他功能** | | |
| `transfer.php` | 企业付款 | 手动发起企业付款 |
| `transfer_batch.php` | 批量付款 | 批量企业付款 |
| `risk.php` | 风控记录 | 查看风控拦截记录 |
| `log.php` | 登录日志 | 查看商户登录日志 |
| `clean.php` | 数据清理 | 清理过期数据 |
| `testsubmit.php` | 测试支付 | 测试支付流程 |
| **通用** | | |
| `login.php` | 管理员登录 | 管理员登录页面 |
| `head.php` | 导航头部 | 后台导航菜单 |
| `ajax.php` | 通用AJAX | 通用数据接口 |
| `code.php` | 验证码 | 图形验证码生成 |
| `sso.php` | SSO登录 | 单点登录 |

## 3.7 聚合收款码模块

聚合收款码模块位于 [paypage/](file:///www/wwwroot/pay/paypage/) 目录，提供固定金额收款码功能，支持微信/支付宝/QQ客户端内直接支付。

### 3.7.1 收款码页面（paypage/）

**inc.php — 辅助函数**

[paypage/inc.php](file:///www/wwwroot/pay/paypage/inc.php) 定义了收款码模块的辅助函数：

- `showerror($msg)`：显示错误页面并退出
- `showerrorjson($msg)`：返回JSON格式错误并退出
- `check_paytype()`：通过User-Agent检测当前客户端类型（微信/支付宝/QQ）

**index.php — 收款码主页面**

[paypage/index.php](file:///www/wwwroot/pay/paypage/index.php) 是收款码的入口页面：

```php
// paypage/index.php L4-L49
if(isset($_GET['merchant'])){
    $merchant=trim($_GET['merchant']);
    $uid = authcode($merchant, 'DECODE', SYS_KEY);
}elseif(isset($_SESSION['paypage_uid'])){
    $uid = intval($_SESSION['paypage_uid']);
}
```

商户识别方式：
1. URL参数 `merchant`：通过 `authcode` 解密获取商户UID（加密的商户标识）
2. Session缓存：已访问过的商户UID

页面流程：
1. 验证商户状态（封禁/实名/QQ绑定检查）
2. 检测客户端环境，自动选择支付方式
3. 调用 `Channel::submit()` 分配通道
4. 若支持JSAPI直接支付，获取OpenId并设置 `direct=1`
5. 生成CSRF Token防止跨站请求
6. 渲染收款码页面（包含金额输入和虚拟键盘）

**ajax.php — 创建订单AJAX**

[paypage/ajax.php](file:///www/wwwroot/pay/paypage/ajax.php) 处理收款码的支付请求：

```php
// paypage/ajax.php L29-L83
if(!empty($paytype) && isset($_SESSION['paypage_typeid']) && ...){
    $typeid = intval($_SESSION['paypage_typeid']);
    $channel = intval($_SESSION['paypage_channel']);
    if($direct==1){
        // JSAPI直接支付模式
        $paydata = \lib\Plugin::loadForJsapi($trade_no,$paytype,$realmoney,$ordername,$payer);
        $result['paydata'] = $paydata;
    }else{
        // 跳转收银台模式
        $result['url'] = '/submit2.php?typeid='.$typeid.'&trade_no='.$trade_no;
    }
}else{
    // 未确定支付方式，跳转收银台
    $result['url'] = '/cashier.php?trade_no='.$trade_no;
}
```

创建订单流程：
1. CSRF Token验证
2. 商户和金额验证
3. IP黑名单和用户黑名单检查
4. 创建 `tid=3`（聚合收款码）的订单
5. 根据是否支持直接支付选择不同模式：
   - `direct=1`：JSAPI直接支付，调用 `Plugin::loadForJsapi()` 获取支付参数
   - `direct=0`：跳转到 `submit2.php` 进行页面支付
   - 无支付方式：跳转到 `cashier.php` 收银台

**success.php / error.php — 结果页面**

支付成功/失败后的展示页面。

### 3.7.2 OpenId获取

收款码模块需要在微信/支付宝客户端内获取用户标识以实现JSAPI支付。

**alipayOpenId() — 支付宝快捷登录获取用户ID**

```php
// paypage/inc.php L26-L44
function alipayOpenId($channel){
    $channel = \lib\Channel::get($channel);
    define("PAY_ROOT", PLUGIN_ROOT.$channel['plugin'].'/');
    require_once(PAY_ROOT."inc/AlipayOauthService.php");
    $config['redirect_uri'] = $siteurl.'paypage/';
    $oauth = new AlipayOauthService($config);
    if(isset($_GET['auth_code'])){
        $result = $oauth->getToken($_GET['auth_code']);
        if($result['user_id']){
            return $result['user_id'];
        }
    }else{
        $oauth->oauth(); // 跳转支付宝授权页面
    }
}
```

支付宝OAuth流程：
1. 首次访问时跳转到支付宝授权页面
2. 用户授权后回调带上 `auth_code` 参数
3. 使用 `auth_code` 换取 `user_id`（支付宝用户ID）

**weixinOpenId() — 微信OAuth获取OpenId**

```php
// paypage/inc.php L46-L58
function weixinOpenId($channel){
    $channel = \lib\Channel::get($channel);
    $wxinfo = \lib\Channel::getWeixin($channel['appwxmp']);
    $tools = new \lib\wechat\JsApiPay($wxinfo['appid'], $wxinfo['appsecret']);
    $openId = $tools->GetOpenid();
    if(!$openId)showerror('OpenId获取失败('.$tools->data['errmsg'].')');
    return $openId;
}
```

微信OAuth流程：
1. 从通道配置获取关联的微信公众号（`appwxmp` 字段）
2. 使用公众号的 `appid` 和 `appsecret` 创建 `JsApiPay` 工具
3. 调用 `GetOpenid()` 自动完成OAuth授权流程获取OpenId

### 3.7.3 JSAPI支付

JSAPI支付是在微信/支付宝客户端内直接调起支付的方式，无需跳转到外部页面。

**Plugin::loadForJsapi()**

```php
// Plugin.php L175-L199
static public function loadForJsapi($trade_no,$type,$money,$name,$openid = null){
    global $channel;
    $filename = PLUGIN_ROOT.$channel['plugin'].'/'.$channel['plugin'].'_plugin.php';
    $classname = '\\'.$channel['plugin'].'_plugin';
    $func = 'jsapi';
    if(file_exists($filename)){
        include $filename;
        if (class_exists($classname, false) && method_exists($classname, $func)) {
            define("IN_PLUGIN", true);
            define("PAY_PLUGIN", $channel['plugin']);
            define("PAY_ROOT", PLUGIN_ROOT.PAY_PLUGIN.'/');
            define("TRADE_NO", $trade_no);
            $result = $classname::$func($type,$money,$name,$openid);
            return $result;
        }
    }
}
```

与 `loadForSubmit` 不同，`loadForJsapi` 直接传入支付参数（type/money/name/openid），而非从全局变量读取。插件需要实现 `jsapi()` 静态方法，返回前端调起支付所需的参数（如微信的 `appId`/`timeStamp`/`nonceStr`/`package`/`signType`/`paySign`）。

**直接支付模式（direct=1）**

在 [paypage/index.php](file:///www/wwwroot/pay/paypage/index.php) 中，系统根据客户端环境和通道配置判断是否启用直接支付：

```php
// paypage/index.php L39-L48
$apptype = explode(',',$submitData['apptype']);
if($checktype == 'alipay' && $type == 'alipay' && ($submitData['plugin']=='alipay' || $submitData['plugin']=='alipaysl') && in_array('4',$apptype)){
    $openId = alipayOpenId($submitData['channel']);
    $direct = '1';
}elseif($checktype == 'wxpay' && $type == 'wxpay' && ($submitData['plugin']=='wxpay' || ...) && in_array('2',$apptype)){
    $openId = weixinOpenId($submitData['channel']);
    $direct = '1';
}elseif($checktype == 'qqpay' && $type == 'qqpay' && $submitData['plugin']=='qqpay' && in_array('2',$apptype)){
    $direct = '1';
}
```

直接支付条件：
1. 客户端环境与支付方式匹配（微信内用微信支付、支付宝内用支付宝支付等）
2. 通道插件支持JSAPI（`apptype` 包含对应值：微信=2，支付宝=4）
3. 成功获取用户OpenId（微信/支付宝需要）

`apptype` 字段值含义：
- `1`：PC扫码支付
- `2`：手机H5/JSAPI支付
- `4`：支付宝生活号/JSAPI支付

当 `direct=1` 时，前端AJAX请求 `paypage/ajax.php` 会调用 `Plugin::loadForJsapi()` 获取支付参数，直接在当前页面调起支付；当 `direct=0` 时，则跳转到 `submit2.php` 进行传统的页面支付流程。
