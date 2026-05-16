# 四、API接口规范

## 4.1 支付提交接口

### 4.1.1 接口地址

| 项目 | 说明 |
|------|------|
| 请求URL | `/submit.php` |
| 请求方式 | GET 或 POST |
| Content-Type | `application/x-www-form-urlencoded`（POST时） |
| 字符编码 | UTF-8 |

### 4.1.2 请求参数

| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| pid | int | 是 | 商户ID，由平台分配 |
| type | string | 是 | 支付方式，可选值：`alipay`（支付宝）、`wxpay`（微信支付）、`qqpay`（QQ钱包）、`bank`（云闪付）、`jdpay`（京东支付）。如不传则跳转收银台页面由用户选择 |
| out_trade_no | string | 是 | 商户订单号，格式限制：`[a-zA-Z0-9._\-|]+`，同一商户下不可重复 |
| notify_url | string | 是 | 异步通知地址，支付成功后系统向此地址发送通知 |
| return_url | string | 是 | 同步回调地址，用户支付完成后浏览器跳转至此地址 |
| name | string | 是 | 商品名称，最长127字符（超长自动截断） |
| money | decimal | 是 | 支付金额，必须大于0，支持小数（如 `1.50`） |
| sign | string | 是 | MD5签名，详见4.1.3签名算法 |
| sign_type | string | 是 | 签名类型，固定值：`MD5` |
| sitename | string | 否 | 站点名称 |
| param | string | 否 | 自定义参数，回调时原样返回 |

**金额限制说明：**
- 系统可配置全局最小支付金额（`pay_minmoney`）和最大支付金额（`pay_maxmoney`）
- 支付通道可配置单笔最小限额（`paymin`）和单笔最大限额（`paymax`）
- 金额格式必须为数字，且匹配正则 `/^[0-9.]+$/`

**订单号重复处理：**
- 若同一商户下 `out_trade_no` 已存在且订单状态为已支付（`status > 0`），则拒绝重复支付
- 若同一商户下 `out_trade_no` 已存在且订单状态为未支付，且支付参数（金额、名称、通知地址、回调地址、自定义参数）有变化，则拒绝并提示更换订单号
- 若同一商户下 `out_trade_no` 已存在且订单状态为未支付，且参数未变化，则复用原订单

### 4.1.3 签名算法

签名采用MD5摘要算法，具体步骤如下：

**第一步：过滤参数（paraFilter）**

从请求参数中移除以下参数：
- 键名为 `sign` 的参数
- 键名为 `sign_type` 的参数
- 值为空字符串的参数

**第二步：参数排序（argSort）**

将过滤后的参数按照键名（key）的ASCII码升序排列，使用 `ksort` 函数实现。

**第三步：拼接字符串（createLinkstring）**

将排序后的参数按 `key1=value1&key2=value2&...` 格式拼接成字符串，参数之间用 `&` 连接。

**第四步：追加密钥（md5Sign）**

在拼接字符串末尾直接追加密钥（商户密钥），即：`prestr + key`。

**第五步：MD5运算**

对追加密钥后的字符串进行MD5运算，得到32位小写十六进制字符串，即为签名值。

**签名示例：**

假设商户参数为：
```
pid=1001
type=alipay
out_trade_no=ORDER20240101001
notify_url=https://www.example.com/notify
return_url=https://www.example.com/return
name=测试商品
money=1.50
```

商户密钥为：`abc123key`

1. 过滤后参数（无需过滤，无sign/sign_type/空值）：同上
2. 按key的ASCII排序：
   ```
   money=1.50
   name=测试商品
   notify_url=https://www.example.com/notify
   out_trade_no=ORDER20240101001
   pid=1001
   return_url=https://www.example.com/return
   type=alipay
   ```
3. 拼接字符串：
   ```
   money=1.50&name=测试商品&notify_url=https://www.example.com/notify&out_trade_no=ORDER20240101001&pid=1001&return_url=https://www.example.com/return&type=alipay
   ```
4. 追加密钥：
   ```
   money=1.50&name=测试商品&notify_url=https://www.example.com/notify&out_trade_no=ORDER20240101001&pid=1001&return_url=https://www.example.com/return&type=alipayabc123key
   ```
5. MD5运算：`md5("money=1.50&name=测试商品&notify_url=https://www.example.com/notify&out_trade_no=ORDER20240101001&pid=1001&return_url=https://www.example.com/return&type=alipayabc123key")`

### 4.1.4 响应说明

**签名验证通过：**
- 若 `type` 参数为空，跳转至收银台页面（`cashier.php`），由用户选择支付方式
- 若 `type` 参数有值且通道可用，根据支付插件返回类型渲染支付页面：
  - `jump`：JavaScript跳转至支付URL
  - `html`：直接输出HTML表单（如自动提交的表单）
  - `page`：渲染指定支付页面模板
  - `qrcode`：渲染扫码支付页面
  - `scheme`：渲染URL Scheme跳转页面（用于微信小程序等）
  - `return`：直接跳转至同步回调地址
- 若通道不可用，跳转至收银台页面并提示无可用通道

**签名验证失败：**
- 显示错误提示页面（`sysmsg`），提示"签名校验失败，请返回重试！"

### 4.1.5 错误码

以下为 `submit.php` 可能返回的所有错误提示信息：

| 错误信息 | 触发条件 |
|----------|----------|
| 你还未配置支付接口商户！ | 请求中未包含 `pid` 参数（GET和POST均无） |
| PID不存在 | `pid` 参数为空或为0 |
| 商户不存在！ | 数据库中不存在该 `pid` 对应的商户 |
| 签名校验失败，请返回重试！ | MD5签名验证不通过 |
| 商户已封禁，无法支付！ | 商户状态 `status=0` 或支付权限 `pay=0` |
| 商户没通过审核，请联系官方客服进行审核 | 商户 `pay=2` 且系统开启审核模式 `user_review=1` |
| 订单号(out_trade_no)不能为空 | `out_trade_no` 参数为空 |
| 通知地址(notify_url)不能为空 | `notify_url` 参数为空 |
| 回调地址(return_url)不能为空 | `return_url` 参数为空 |
| 商品名称(name)不能为空 | `name` 参数为空 |
| 金额(money)不能为空 | `money` 参数为空 |
| 金额不合法 | `money` ≤ 0，或非数字，或格式不匹配 `/^[0-9.]+$/` |
| 最大支付金额是{X}元 | 系统配置了最大支付金额限制且超出 |
| 最小支付金额是{X}元 | 系统配置了最小支付金额限制且不足 |
| 订单号(out_trade_no)格式不正确 | `out_trade_no` 不匹配 `/^[a-zA-Z0-9.\_\-|]+$/` |
| 当前商户未完成实名认证，无法收款 | 系统强制实名认证 `cert_force=1` 且商户未认证 |
| 当前商户未填写联系QQ，无法收款 | 系统强制填写QQ `forceqq=1` 且商户QQ为空 |
| 该域名不可发起支付，原因：域名没过白，请前往支付平台授权支付域名 | 系统开启域名白名单 `pay_domain_forbid=1` 且通知域名未授权 |
| 该商品禁止出售 | 商品名称命中系统屏蔽词（或自定义屏蔽提示） |
| 系统异常无法完成付款 | 请求IP在系统IP黑名单中 |
| 该订单({X})已完成支付，请勿重复发起支付 | 同一商户同一订单号已支付 |
| 该订单({X})支付参数有变化，请更换订单号重新发起支付 | 同一商户同一订单号未支付但参数有变化 |
| 创建订单失败，请返回重试！ | 数据库插入订单记录失败 |
| 当前支付方式单笔最小限额为{X}元，请选择其他支付方式！ | 支付金额低于通道单笔最小限额 |
| 当前支付方式单笔最大限额为{X}元，请选择其他支付方式！ | 支付金额超过通道单笔最大限额 |
| 当前商户余额不足，无法完成支付，请商户登录用户中心充值余额 | 商户直清模式下商户余额不足 |
| 当前支付通道信息不存在 | 支付通道配置异常 |

---

## 4.2 MAPI接口

MAPI（Mobile API）接口为移动端/API场景设计，与 `submit.php` 的主要区别在于：MAPI接口以JSON格式返回支付链接信息，而非直接渲染HTML页面。商户获取支付链接后可自行决定如何展示和处理。

### 4.2.1 接口地址

| 项目 | 说明 |
|------|------|
| 请求URL | `/mapi.php` |
| 请求方式 | GET 或 POST |
| Content-Type | `application/x-www-form-urlencoded`（POST时） |
| 字符编码 | UTF-8 |
| 响应格式 | JSON |

### 4.2.2 请求参数

MAPI接口的请求参数与 `submit.php` 基本相同，但额外增加了以下参数：

| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| pid | int | 是 | 商户ID |
| type | string | 是 | 支付方式，**MAPI接口中type为必填**，不可为空 |
| out_trade_no | string | 是 | 商户订单号 |
| notify_url | string | 是 | 异步通知地址 |
| return_url | string | 否 | 同步回调地址（MAPI场景下可为空） |
| name | string | 是 | 商品名称 |
| money | decimal | 是 | 支付金额 |
| sign | string | 是 | MD5签名 |
| sign_type | string | 是 | 固定值：`MD5` |
| sitename | string | 否 | 站点名称 |
| param | string | 否 | 自定义参数 |
| clientip | string | 是 | 用户IP地址，**MAPI接口中为必填** |
| device | string | 否 | 设备类型，可选值：`pc`（默认）、`mobile`、`qq`、`wechat`、`alipay`。当传入 `qq`/`wechat`/`alipay` 时，系统会将其映射为 `mobile` 设备，并记录原始设备类型到 `mdevice` 变量 |

**与 `submit.php` 的差异：**
- `type` 参数在MAPI中为必填（`submit.php` 中可不填跳转收银台）
- `clientip` 参数在MAPI中为必填
- `device` 参数为MAPI独有参数
- `return_url` 在MAPI中非必填

### 4.2.3 返回格式

**成功响应：**

```json
{
  "code": 1,
  "trade_no": "20240101123456789",
  "payurl": "https://pay.example.com/..."
}
```

或

```json
{
  "code": 1,
  "trade_no": "20240101123456789",
  "qrcode": "https://pay.example.com/qrcode/..."
}
```

或

```json
{
  "code": 1,
  "trade_no": "20240101123456789",
  "urlscheme": "weixin://..."
}
```

**失败响应：**

```json
{
  "code": -2,
  "msg": "错误信息描述"
}
```

**无商户ID时：**

```json
{
  "code": -4,
  "msg": "商户ID不能为空"
}
```

### 4.2.4 返回值说明

| 字段 | 类型 | 说明 |
|------|------|------|
| code | int | 返回码。`1` 表示成功，`-2` 表示支付通道返回错误，`-4` 表示未传入商户ID |
| trade_no | string | 系统订单号，支付成功时返回 |
| payurl | string | 支付跳转URL，当支付方式为 `jump` 类型时返回 |
| qrcode | string | 扫码支付URL，当支付方式为 `qrcode` 类型时返回 |
| urlscheme | string | URL Scheme链接，当支付方式为 `scheme` 类型时返回（如微信小程序跳转） |
| msg | string | 错误信息，仅在 `code=-2` 时返回 |

**返回字段与支付方式对应关系：**

| 支付插件返回类型 | 返回字段 | 说明 |
|------------------|----------|------|
| jump | payurl | 直接跳转的支付链接，适用于H5支付等场景 |
| qrcode | qrcode | 二维码内容URL，商户需自行生成二维码展示给用户 |
| scheme | urlscheme | URL Scheme链接，适用于微信小程序等唤起支付 |
| error | code=-2, msg | 支付通道返回错误 |
| 其他（插件无mapi方法时） | payurl | 降级为跳转到系统内嵌支付页面 |

**插件mapi方法降级机制：**

当支付插件未实现 `mapi` 方法但实现了 `submit` 方法时，系统会自动降级，返回一个跳转到系统内嵌支付页面的URL：

```json
{
  "code": 1,
  "trade_no": "20240101123456789",
  "payurl": "https://yoursite.com/pay/submit/20240101123456789/"
}
```

### 4.2.5 MAPI错误码

MAPI接口的错误信息以JSON格式返回，`code` 为 `-1`（通用错误）或 `-4`（参数缺失）：

| 错误信息 | code | 触发条件 |
|----------|------|----------|
| 商户ID不能为空 | -4 | 请求中未包含 `pid` 参数 |
| PID不存在 | -1 | `pid` 参数为空或为0 |
| 商户不存在！ | -1 | 数据库中不存在该商户 |
| 签名校验失败，请返回重试！ | -1 | MD5签名验证不通过 |
| 商户已封禁，无法支付！ | -1 | 商户被封禁 |
| 商户没通过审核，请联系官方客服进行审核 | -1 | 商户未通过审核 |
| 订单号(out_trade_no)不能为空 | -1 | `out_trade_no` 为空 |
| 通知地址(notify_url)不能为空 | -1 | `notify_url` 为空 |
| 商品名称(name)不能为空 | -1 | `name` 为空 |
| 金额(money)不能为空 | -1 | `money` 为空 |
| 支付方式(type)不能为空 | -1 | `type` 为空（MAPI中type必填） |
| 用户IP地址(clientip)不能为空 | -1 | `clientip` 为空（MAPI中clientip必填） |
| 金额不合法 | -1 | 金额格式错误 |
| 最大支付金额是{X}元 | -1 | 超出系统最大金额限制 |
| 最小支付金额是{X}元 | -1 | 低于系统最小金额限制 |
| 订单号(out_trade_no)格式不正确 | -1 | 订单号格式不合法 |
| 当前商户未完成实名认证，无法收款 | -1 | 需实名认证 |
| 当前商户未填写联系QQ，无法收款 | -1 | 需填写QQ |
| 该域名不可发起支付，原因：域名没过白，请前往支付平台授权支付域名 | -1 | 域名未授权 |
| 该商品禁止出售 | -1 | 商品名命中屏蔽词 |
| 系统异常无法完成付款 | -1 | IP在黑名单中 |
| 该订单({X})已完成支付，请勿重复发起支付 | -1 | 订单已支付 |
| 该订单({X})支付参数有变化，请更换订单号重新发起支付 | -1 | 订单参数变化 |
| 创建订单失败，请返回重试！ | -1 | 数据库插入失败 |
| 当前支付方式单笔最小限额为{X}元，请选择其他支付方式！ | -1 | 低于通道限额 |
| 当前支付方式单笔最大限额为{X}元，请选择其他支付方式！ | -1 | 超出通道限额 |
| 当前商户余额不足，无法完成支付，请商户登录用户中心充值余额 | -1 | 商户余额不足 |

---

## 4.3 异步通知接口

### 4.3.1 通知触发

当订单支付成功后，系统会主动向商户在支付请求中提供的 `notify_url` 发送GET请求，通知商户订单支付结果。

**通知流程：**
1. 支付通道回调系统，确认订单支付成功
2. 系统更新订单状态为已支付（`status=1`）
3. 系统调用 `creat_callback` 函数构建带签名的通知URL
4. 系统通过 `do_notify` 函数向商户 `notify_url` 发送GET请求
5. 若商户返回包含 `success`（不区分大小写）字符串，则通知成功
6. 若商户未返回 `success`，系统将按重试机制继续通知

### 4.3.2 通知参数

系统向商户 `notify_url` 发送GET请求时，参数以URL查询字符串形式附加：

| 参数名 | 类型 | 说明 |
|--------|------|------|
| pid | int | 商户ID |
| trade_no | string | 系统订单号 |
| out_trade_no | string | 商户订单号 |
| type | string | 支付方式（如 `alipay`、`wxpay` 等） |
| name | string | 商品名称。若系统配置 `notifyordername=1`，则固定返回 `product` |
| money | float | 支付金额（浮点数类型，如 `1.5`） |
| trade_status | string | 交易状态，固定值：`TRADE_SUCCESS` |
| param | string | 自定义参数（仅当商户提交时传入了 `param` 才会返回） |
| sign | string | MD5签名 |
| sign_type | string | 签名类型，固定值：`MD5` |

**通知URL示例：**

```
https://www.example.com/notify?money=1.5&name=测试商品&out_trade_no=ORDER001&pid=1001&trade_no=20240101123456789&trade_status=TRADE_SUCCESS&type=alipay&sign=a1b2c3d4e5f6...&sign_type=MD5
```

若商户的 `notify_url` 本身已包含查询参数（含有 `?`），则参数以 `&` 方式追加；否则以 `?` 方式追加。

### 4.3.3 签名验证

商户收到异步通知后，必须按以下步骤验证签名：

1. 从通知参数中取出所有参数
2. 调用 `paraFilter` 过滤掉 `sign`、`sign_type` 参数和空值参数
3. 对剩余参数按key的ASCII升序排序
4. 按 `key1=value1&key2=value2` 格式拼接
5. 在拼接字符串末尾追加密钥
6. 对结果做MD5运算
7. 将计算得到的签名与通知中的 `sign` 参数对比，一致则验证通过

**注意事项：**
- `money` 参数在通知中为浮点数类型（如 `1.5` 而非 `1.50`），签名时需使用原始值
- `param` 参数仅在商户提交订单时传入了该参数时才会出现在通知中
- 签名验证通过后，商户还需校验 `pid`、`out_trade_no`、`money` 等业务参数是否与原始订单一致

### 4.3.4 响应要求

商户收到异步通知并验证签名通过后，需在响应中返回字符串 `success`（不区分大小写）。

- **验证成功且业务处理完成**：返回 `success`（或 `SUCCESS`、`Success` 等不区分大小写的形式）
- **验证失败或业务处理异常**：不返回 `success`

系统判断逻辑为：响应内容中包含 `success`（不区分大小写）子串即视为通知成功。

### 4.3.5 通知重试机制

如果商户未返回 `success`，系统将按照以下时间间隔进行重试通知：

| 重试次数 | 距首次通知的时间间隔 | 说明 |
|----------|----------------------|------|
| 第1次 | 1分钟 | 首次通知失败后1分钟重试 |
| 第2次 | 3分钟 | 距首次通知约3分钟 |
| 第3次 | 约20分钟 | 距首次通知约20分钟 |
| 第4次 | 约1小时 | 距首次通知约1小时 |
| 第5次 | 约2小时 | 距首次通知约2小时 |

**重试机制详细说明：**

1. 首次通知在订单支付成功时立即发送
2. 若首次通知失败，系统将订单的 `notify` 字段设为 `1`，并设置 `notifytime` 为当前时间+1分钟
3. 定时任务（`cron.php?do=notify`）每次最多处理20条待通知订单
4. 每次重试时，`notify` 字段递增1，并根据当前重试次数计算下次重试时间间隔：
   - `notify=2`：间隔2分钟（累计约3分钟）
   - `notify=3`：间隔16分钟（累计约20分钟）
   - `notify=4`：间隔36分钟（累计约1小时）
   - `notify=5`：间隔1小时（累计约2小时）
5. 当 `notify` 超过5次（即 `notify=6`）时，系统将 `notify` 设为 `-1`，停止自动重试
6. 管理员可在后台手动对 `notify=-1` 的订单重新发起通知（`cron.php?do=notify2`）
7. 重试仅针对支付完成时间在1天内的订单

---

## 4.4 同步回调接口

### 4.4.1 回调触发

用户支付完成后，系统通过浏览器跳转至商户在支付请求中提供的 `return_url`。同步回调的参数格式与异步通知相同，参数以URL查询字符串形式附加。

**跳转逻辑：**
- 支付完成后5分钟内：跳转至带签名参数的 `return_url`
- 支付完成后超过5分钟：跳转至系统支付成功页面（`/payok.html`）
- 订单状态为退款/异常（`status=2`）：跳转至支付失败页面（`/payerr.html`）

### 4.4.2 回调参数

同步回调的参数与异步通知参数完全一致：

| 参数名 | 类型 | 说明 |
|--------|------|------|
| pid | int | 商户ID |
| trade_no | string | 系统订单号 |
| out_trade_no | string | 商户订单号 |
| type | string | 支付方式 |
| name | string | 商品名称。若系统配置 `notifyordername=1`，则固定返回 `product` |
| money | float | 支付金额 |
| trade_status | string | 交易状态：`TRADE_SUCCESS` |
| param | string | 自定义参数（如有） |
| sign | string | MD5签名 |
| sign_type | string | 签名类型：`MD5` |

**回调URL示例：**

```
https://www.example.com/return?money=1.5&name=测试商品&out_trade_no=ORDER001&pid=1001&trade_no=20240101123456789&trade_status=TRADE_SUCCESS&type=alipay&sign=a1b2c3d4e5f6...&sign_type=MD5
```

### 4.4.3 签名验证

商户在 `return_url` 页面中应按4.3.3所述的签名算法验证回调参数的签名，确保回调数据未被篡改。

**重要提示：**
- 同步回调仅通过浏览器跳转触发，不保证一定能到达（用户可能关闭浏览器）
- 商户应以异步通知（`notify_url`）的结果为准，同步回调仅用于页面展示
- 同步回调的签名验证方式与异步通知完全相同

---

## 4.5 订单查询接口

### 4.5.1 支付状态查询（getshop）

#### 接口地址

| 项目 | 说明 |
|------|------|
| 请求URL | `/getshop.php` |
| 请求方式 | GET |
| 参数 | `trade_no`（系统订单号） |

#### 请求参数

| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| trade_no | string | 是 | 系统订单号 |

#### 返回格式

**支付成功：**

```json
{
  "code": 1,
  "msg": "付款成功",
  "backurl": "https://www.example.com/return?money=1.5&name=...&sign=...&sign_type=MD5"
}
```

**未支付：**

```json
{
  "code": -1,
  "msg": "未付款"
}
```

**支付异常/已退款（status=2）：**

```json
{
  "code": 1,
  "msg": "付款成功",
  "backurl": "/payerr.html"
}
```

#### 返回值说明

| 字段 | 类型 | 说明 |
|------|------|------|
| code | int | `1` 表示订单已处理（含成功和异常），`-1` 表示未支付 |
| msg | string | 状态描述 |
| backurl | string | 跳转地址。支付成功5分钟内为带签名的 `return_url`，超过5分钟为 `/payok.html`；订单异常时为 `/payerr.html` |

### 4.5.2 商户API订单查询（api）

#### 接口地址

| 项目 | 说明 |
|------|------|
| 请求URL | `/api.php?act=order` |
| 请求方式 | GET |
| 认证方式 | pid + key 明文验证 |

#### 请求参数

| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| act | string | 是 | 固定值：`order` |
| pid | int | 是 | 商户ID |
| key | string | 是 | 商户密钥 |
| trade_no | string | 否 | 系统订单号（与 `out_trade_no` 二选一） |
| out_trade_no | string | 否 | 商户订单号（与 `trade_no` 二选一） |

#### 返回格式

**查询成功：**

```json
{
  "code": 1,
  "msg": "查询订单号成功！",
  "trade_no": "20240101123456789",
  "out_trade_no": "ORDER001",
  "type": "alipay",
  "pid": 1001,
  "addtime": "2024-01-01 12:34:56",
  "endtime": "2024-01-01 12:35:30",
  "name": "测试商品",
  "money": "1.50",
  "param": "custom_param",
  "buyer": "openid_xxx",
  "status": 1
}
```

**订单不存在：**

```json
{
  "code": -1,
  "msg": "订单号不存在"
}
```

**参数错误：**

```json
{
  "code": -4,
  "msg": "订单号不能为空"
}
```

**认证失败：**

```json
{
  "code": -3,
  "msg": "商户ID不存在"
}
```

或

```json
{
  "code": -3,
  "msg": "商户密钥错误"
}
```

#### 返回值说明

| 字段 | 类型 | 说明 |
|------|------|------|
| code | int | `1` 成功，`-1` 订单不存在，`-3` 认证失败，`-4` 参数缺失 |
| msg | string | 状态描述 |
| trade_no | string | 系统订单号 |
| out_trade_no | string | 商户订单号 |
| type | string | 支付方式名称（如 `alipay`、`wxpay`） |
| pid | int | 商户ID |
| addtime | string | 订单创建时间 |
| endtime | string | 订单完成时间 |
| name | string | 商品名称 |
| money | string | 订单金额 |
| param | string | 自定义参数 |
| buyer | string | 买家标识（如微信openid） |
| status | int | 订单状态：`0` 未支付，`1` 已支付，`2` 已退款/异常 |

### 4.5.3 商户API批量订单查询

#### 接口地址

| 项目 | 说明 |
|------|------|
| 请求URL | `/api.php?act=orders` |
| 请求方式 | GET |
| 认证方式 | pid + key 明文验证 |

#### 请求参数

| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| act | string | 是 | 固定值：`orders` |
| pid | int | 是 | 商户ID |
| key | string | 是 | 商户密钥 |
| limit | int | 否 | 每页数量，默认10，最大50 |
| offset | int | 否 | 偏移量，默认0 |
| status | int | 否 | 订单状态筛选：`0` 未支付，`1` 已支付，`2` 已退款 |

#### 返回格式

```json
{
  "code": 1,
  "msg": "查询订单记录成功！",
  "count": 10,
  "data": [
    {
      "trade_no": "20240101123456789",
      "out_trade_no": "ORDER001",
      "type": "alipay",
      "pid": 1001,
      "addtime": "2024-01-01 12:34:56",
      "endtime": "2024-01-01 12:35:30",
      "name": "测试商品",
      "money": "1.50",
      "param": "custom_param",
      "buyer": "openid_xxx",
      "status": 1
    }
  ]
}
```

### 4.5.4 商户信息查询

#### 接口地址

| 项目 | 说明 |
|------|------|
| 请求URL | `/api.php?act=query` |
| 请求方式 | GET |
| 认证方式 | pid + key 明文验证 |

#### 请求参数

| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| act | string | 是 | 固定值：`query` |
| pid | int | 是 | 商户ID |
| key | string | 是 | 商户密钥 |

#### 返回格式

```json
{
  "code": 1,
  "pid": 1001,
  "key": "abc123key",
  "active": 1,
  "money": "100.00",
  "type": 1,
  "account": "account@example.com",
  "username": "张三",
  "orders": 150,
  "orders_today": 10,
  "orders_lastday": 8
}
```

### 4.5.5 结算记录查询

#### 接口地址

| 项目 | 说明 |
|------|------|
| 请求URL | `/api.php?act=settle` |
| 请求方式 | GET |
| 认证方式 | pid + key 明文验证 |

#### 请求参数

| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| act | string | 是 | 固定值：`settle` |
| pid | int | 是 | 商户ID |
| key | string | 是 | 商户密钥 |
| limit | int | 否 | 每页数量，默认10，最大50 |
| offset | int | 否 | 偏移量，默认0 |

### 4.5.6 订单退款接口

#### 接口地址

| 项目 | 说明 |
|------|------|
| 请求URL | `/api.php?act=refund` |
| 请求方式 | POST |
| 认证方式 | pid + key 明文验证 |

#### 请求参数

| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| act | string | 是 | 固定值：`refund` |
| pid | int | 是 | 商户ID |
| key | string | 是 | 商户密钥 |
| trade_no | string | 否 | 系统订单号（与 `out_trade_no` 二选一） |
| out_trade_no | string | 否 | 商户订单号（与 `trade_no` 二选一） |
| money | decimal | 是 | 退款金额，不能大于订单金额 |

#### 返回格式

**退款成功：**

```json
{
  "code": 1,
  "msg": "退款成功",
  "money": "1.50"
}
```

**退款失败：**

```json
{
  "code": -1,
  "msg": "退款失败：具体错误原因"
}
```

#### 退款说明

- 系统需开启商户后台自助退款功能（`user_refund` 配置项）
- 商户需开启订单退款API接口（商户 `refund` 字段不为0）
- 仅支持退款已支付状态（`status=1`）的订单
- 退款金额不能大于订单金额
- 当退款金额等于订单金额或大于等于商户实际收入时，扣除商户全部实际收入
- 当退款金额小于商户实际收入时，按退款金额等额扣除商户余额
- 直清模式（`mode=0`）下，退款会从商户余额中扣除对应金额

---

## 4.6 签名规则详解

### 4.6.1 签名算法步骤

本系统所有接口均使用MD5签名算法，签名流程如下：

```
原始参数 → paraFilter(过滤) → argSort(排序) → createLinkstring(拼接) → 追加密钥 → MD5运算 → 签名值
```

**详细步骤：**

1. **参数过滤（paraFilter）**
   - 移除键名为 `sign` 的参数
   - 移除键名为 `sign_type` 的参数
   - 移除值为空字符串（`""`）的参数
   - 保留其他所有参数

2. **参数排序（argSort）**
   - 使用PHP的 `ksort()` 函数对过滤后的参数按键名进行升序排序
   - 排序规则为键名的ASCII码升序

3. **拼接字符串（createLinkstring）**
   - 将排序后的参数按 `key1=value1&key2=value2&key3=value3` 格式拼接
   - 参数之间用 `&` 连接
   - 最后一个参数后不加 `&`
   - 参数值不做URL编码

4. **追加密钥（md5Sign）**
   - 将商户密钥直接追加到拼接字符串末尾
   - 格式为：`拼接字符串 + 商户密钥`
   - 注意：密钥前不加任何连接符（如 `&` 等）

5. **MD5运算**
   - 对追加密钥后的完整字符串进行MD5哈希运算
   - 输出32位小写十六进制字符串

### 4.6.2 PHP代码示例

**签名生成示例：**

```php
<?php
class PayUtils {

    public static function paraFilter($para) {
        $para_filter = array();
        foreach ($para as $key => $val) {
            if ($key == "sign" || $key == "sign_type" || $val === "") {
                continue;
            } else {
                $para_filter[$key] = $para[$key];
            }
        }
        return $para_filter;
    }

    public static function argSort($para) {
        ksort($para);
        reset($para);
        return $para;
    }

    public static function createLinkstring($para) {
        $arg = "";
        foreach ($para as $key => $val) {
            $arg .= $key . "=" . $val . "&";
        }
        $arg = substr($arg, 0, -1);
        return $arg;
    }

    public static function md5Sign($prestr, $key) {
        $prestr = $prestr . $key;
        return md5($prestr);
    }

    public static function md5Verify($prestr, $sign, $key) {
        $prestr = $prestr . $key;
        $mysgin = md5($prestr);
        if ($mysgin == $sign) {
            return true;
        } else {
            return false;
        }
    }
}

// ============ 生成签名示例 ============

$params = array(
    'pid'            => '1001',
    'type'           => 'alipay',
    'out_trade_no'   => 'ORDER20240101001',
    'notify_url'     => 'https://www.example.com/notify',
    'return_url'     => 'https://www.example.com/return',
    'name'           => '测试商品',
    'money'          => '1.50',
    'sign_type'      => 'MD5',  // 此参数会被过滤，不参与签名
);

$key = 'abc123key'; // 商户密钥

// 第1步：过滤参数
$filtered = PayUtils::paraFilter($params);

// 第2步：参数排序
$sorted = PayUtils::argSort($filtered);

// 第3步：拼接字符串
$prestr = PayUtils::createLinkstring($sorted);

// 第4步+第5步：追加密钥并MD5
$sign = PayUtils::md5Sign($prestr, $key);

echo "待签名字符串: " . $prestr . "\n";
echo "追加密钥后: " . $prestr . $key . "\n";
echo "签名结果: " . $sign . "\n";

// 将签名加入请求参数
$params['sign'] = $sign;

// ============ 验证签名示例 ============

// 模拟收到回调通知参数
$notifyParams = array(
    'pid'           => '1001',
    'trade_no'      => '20240101123456789',
    'out_trade_no'  => 'ORDER20240101001',
    'type'          => 'alipay',
    'name'          => '测试商品',
    'money'         => '1.5',
    'trade_status'  => 'TRADE_SUCCESS',
    'sign'          => $sign,       // 通知中的签名
    'sign_type'     => 'MD5',       // 通知中的签名类型
);

$receivedSign = $notifyParams['sign'];

// 第1步：过滤参数（sign和sign_type会被移除）
$filtered = PayUtils::paraFilter($notifyParams);

// 第2步：参数排序
$sorted = PayUtils::argSort($filtered);

// 第3步：拼接字符串
$prestr = PayUtils::createLinkstring($sorted);

// 第4步+第5步：验证签名
if (PayUtils::md5Verify($prestr, $receivedSign, $key)) {
    echo "签名验证通过！\n";

    // 验证业务参数
    if ($notifyParams['trade_status'] === 'TRADE_SUCCESS') {
        // 处理支付成功逻辑
        echo "支付成功，订单号: " . $notifyParams['out_trade_no'] . "\n";
    }

    // 返回success
    echo "success";
} else {
    echo "签名验证失败！\n";
}
?>
```

### 4.6.3 其他语言示例

**Python签名示例：**

```python
import hashlib
from collections import OrderedDict

def para_filter(params):
    """过滤sign、sign_type和空值参数"""
    return {k: v for k, v in params.items()
            if k not in ('sign', 'sign_type') and v != ''}

def create_linkstring(params):
    """拼接为key1=value1&key2=value2格式"""
    sorted_params = OrderedDict(sorted(params.items(), key=lambda x: x[0]))
    return '&'.join(f'{k}={v}' for k, v in sorted_params.items())

def md5_sign(prestr, key):
    """MD5签名：拼接字符串+密钥，再做MD5"""
    return hashlib.md5((prestr + key).encode('utf-8')).hexdigest()

def md5_verify(prestr, sign, key):
    """验证签名"""
    return md5_sign(prestr, key) == sign

# 生成签名
params = {
    'pid': '1001',
    'type': 'alipay',
    'out_trade_no': 'ORDER20240101001',
    'notify_url': 'https://www.example.com/notify',
    'return_url': 'https://www.example.com/return',
    'name': '测试商品',
    'money': '1.50',
}
key = 'abc123key'

filtered = para_filter(params)
prestr = create_linkstring(filtered)
sign = md5_sign(prestr, key)

print(f"待签名字符串: {prestr}")
print(f"签名结果: {sign}")

# 验证签名
notify_params = {
    'pid': '1001',
    'trade_no': '20240101123456789',
    'out_trade_no': 'ORDER20240101001',
    'type': 'alipay',
    'name': '测试商品',
    'money': '1.5',
    'trade_status': 'TRADE_SUCCESS',
    'sign': sign,
    'sign_type': 'MD5',
}

received_sign = notify_params.pop('sign', '')
notify_params.pop('sign_type', '')

filtered = para_filter(notify_params)
prestr = create_linkstring(filtered)

if md5_verify(prestr, received_sign, key):
    print("签名验证通过！")
else:
    print("签名验证失败！")
```

**Java签名示例：**

```java
import java.security.MessageDigest;
import java.util.ArrayList;
import java.util.Collections;
import java.util.HashMap;
import java.util.List;
import java.util.Map;

public class PaySignUtil {

    public static Map<String, String> paraFilter(Map<String, String> params) {
        Map<String, String> result = new HashMap<>();
        for (Map.Entry<String, String> entry : params.entrySet()) {
            String key = entry.getKey();
            String value = entry.getValue();
            if (!key.equals("sign") && !key.equals("sign_type") && !value.isEmpty()) {
                result.put(key, value);
            }
        }
        return result;
    }

    public static String createLinkstring(Map<String, String> params) {
        List<String> keys = new ArrayList<>(params.keySet());
        Collections.sort(keys);
        StringBuilder sb = new StringBuilder();
        for (int i = 0; i < keys.size(); i++) {
            String key = keys.get(i);
            String value = params.get(key);
            if (i > 0) sb.append("&");
            sb.append(key).append("=").append(value);
        }
        return sb.toString();
    }

    public static String md5Sign(String prestr, String key) throws Exception {
        String str = prestr + key;
        MessageDigest md = MessageDigest.getInstance("MD5");
        byte[] digest = md.digest(str.getBytes("UTF-8"));
        StringBuilder sb = new StringBuilder();
        for (byte b : digest) {
            sb.append(String.format("%02x", b & 0xff));
        }
        return sb.toString();
    }

    public static boolean md5Verify(String prestr, String sign, String key) throws Exception {
        String mysign = md5Sign(prestr, key);
        return mysign.equals(sign);
    }
}
```

**Node.js签名示例：**

```javascript
const crypto = require('crypto');

function paraFilter(params) {
    const result = {};
    for (const [key, value] of Object.entries(params)) {
        if (key !== 'sign' && key !== 'sign_type' && value !== '') {
            result[key] = value;
        }
    }
    return result;
}

function createLinkstring(params) {
    const keys = Object.keys(params).sort();
    return keys.map(key => `${key}=${params[key]}`).join('&');
}

function md5Sign(prestr, key) {
    return crypto.createHash('md5').update(prestr + key, 'utf8').digest('hex');
}

function md5Verify(prestr, sign, key) {
    return md5Sign(prestr, key) === sign;
}
```

### 4.6.4 签名注意事项

1. **参数值不做URL编码**：签名时参数值使用原始值，不做 `urlencode` 编码。系统在构建通知URL时使用 `createLinkstringUrlencode` 方法对参数值做URL编码，但签名计算始终使用原始值。

2. **空值处理**：值为空字符串的参数不参与签名计算。如果某个参数传了空值，它会被过滤掉，等同于未传该参数。

3. **数据类型**：签名时所有参数值均作为字符串处理。注意 `money` 字段在异步通知中为浮点数类型（如 `1.5`），签名时需使用通知中的原始值。

4. **字符编码**：签名计算使用UTF-8编码。

5. **密钥安全**：商户密钥（`key`）应妥善保管，不得泄露。密钥仅参与签名计算，不在请求参数中传输。

6. **签名比较**：签名验证时使用简单的字符串相等比较（`==`），不使用时间安全比较，因此在实际生产环境中需注意防范时序攻击（本系统未做此防护）。

7. **自定义参数**：`param` 参数参与签名计算。如果商户提交时传入了 `param`，异步通知中也会包含该参数并参与签名；如果未传入，则通知中不包含该参数。
