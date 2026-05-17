# vmqPro插件重命名与回调通知修复 Spec

## Why
上一轮修复将插件重命名为vmpPro，但用户要求正确名称为vmqPro。同时支付成功后v免签服务端的异步通知回调失败（返回"异步通知失败"），需要定位并修复。

## What Changes
- 将插件名称从 `vmpPro` 改为 `vmqPro`，包括目录名、文件名、类名及 `$info` 配置
- 修复 `submit()` 方法中 `price` 参数格式化问题，使用 `sprintf("%.2f", ...)` 确保两位小数一致性
- 修复 `notify()` 方法中输出缓冲问题，在返回前清理缓冲区，确保v免签服务端收到纯"success"响应

## Impact
- Affected specs: 插件重命名、创建订单签名一致性、异步通知回调响应
- Affected code: `/www/wwwroot/pay/plugins/vmpPro/vmpPro_plugin.php` → 重命名为 `/www/wwwroot/pay/plugins/vmqPro/vmqPro_plugin.php`

## 回调通知失败根因分析

### 根因1：price参数格式不一致（签名隐患）

**插件当前创建订单时发送的price：**
```php
"price" => $order['realmoney'],  // PHP float，如 0.1 → 字符串化为 "0.1"
```

**v免签服务端回调时格式化price（NotifyService.php第8行）：**
```php
$price = number_format($price, 2, '.', '');  // 始终两位小数，如 "0.10"
$reallyPrice = number_format($reallyPrice, 2, '.', '');  // 如 "0.10"
```

v免签服务端在 `createOrder` 验签时使用原始输入值（`input("price")`），所以 `price=0.1` 和 `price=0.10` 都能通过验签。但v免签数据库 `price` 字段存储为 `decimal(10,2)`，"0.1"存为"0.10"。回调时 `NotifyService::buildNotifyUrl` 用 `number_format` 格式化为"0.10"。

虽然当前回调验签逻辑本身正确（双方都用格式化后的值计算），但创建订单时price格式不一致可能在特定浮点数场景下产生隐患。应统一使用 `sprintf("%.2f", ...)` 格式化。

### 根因2：输出缓冲污染（关键问题）

v免签服务端 `HttpService::curl` 使用严格字符串比较：
```php
$re = HttpService::curl($url);
if ($re == "success") {  // 必须严格等于 "success"
```

而易支付 `pay.php` 在调用插件 `notify()` 前会 `include("./includes/common.php")`，该文件可能向输出缓冲区写入额外内容（如BOM字符、空白行、PHP警告等）。当 `echoDefault()` 输出 "success" 时，实际HTTP响应体为 `[额外输出]success`，v免签服务端比较时 `"[额外输出]success" != "success"`，判定通知失败。

### 根因3：v免签服务端SSL验证配置

v免签服务端 `HttpService::curl` 启用了严格SSL验证：
```php
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
```
如果易支付notifyUrl使用HTTPS，且v免签服务端未配置CA证书包，cURL请求会直接失败（返回false），导致通知失败。此为服务端配置问题，不在插件代码修改范围内，但需提示用户排查。

## ADDED Requirements

### Requirement: 插件重命名为vmqPro
插件 SHALL 将名称改为 `vmqPro`，作者为 `青衫`，包括目录名、文件名、类名及 `$info` 配置。

#### Scenario: 插件信息正确
- **WHEN** 易支付系统加载vmqPro插件
- **THEN** 插件显示名称为 `vmqPro`，作者为 `青衫`
- **AND** 插件目录为 `/www/wwwroot/pay/plugins/vmqPro/`
- **AND** 插件文件为 `vmqPro_plugin.php`
- **AND** 类名为 `vmqPro_plugin`

### Requirement: price参数格式统一
插件 SHALL 在创建订单时使用 `sprintf("%.2f", $order['realmoney'])` 格式化price参数，确保与v免签服务端 `number_format` 格式一致。

#### Scenario: price格式一致
- **WHEN** 插件调用v免签 `createOrder` 接口
- **THEN** price参数为两位小数格式（如 "0.10" 而非 "0.1"）
- **AND** 签名计算使用格式化后的price值

### Requirement: 异步通知回调输出清理
插件 SHALL 在 `notify()` 方法返回前清理输出缓冲区，确保v免签服务端收到纯"success"响应。

#### Scenario: 通知回调响应纯净
- **WHEN** v免签服务端发送异步通知到易支付系统
- **THEN** 易支付系统响应体严格为 "success"（无BOM、无空白行、无PHP警告）
- **AND** v免签服务端判定通知成功

## MODIFIED Requirements
无

## REMOVED Requirements
无
