# Nginx伪静态规则修复 Spec

## Why
用户已在宝塔面板配置了伪静态规则，但规则不正确：通用的ThinkPHP规则 `rewrite ^(.*)$ /index.php?s=$1` 将 `/pay/notify/` 和 `/pay/return/` 请求路由到了 `index.php` 而非 `pay.php`，导致回调处理失败。需要修改为易支付官方提供的正确rewrite规则。

## What Changes
- 修改 `/www/server/panel/vhost/rewrite/115.175.13.168_688.conf` 中的rewrite规则
- 将通用ThinkPHP规则替换为易支付专用规则
- 重载Nginx使规则生效

## Impact
- Affected specs: 所有支付插件的异步通知和同步回调功能
- Affected code: `/www/server/panel/vhost/rewrite/115.175.13.168_688.conf`

## 问题根因分析

### 当前规则（错误）

```nginx
location / {
    if (!-e $request_filename){
        rewrite ^(.*)$ /index.php?s=$1 last; break;
    }
}
```

此规则将**所有**不存在的文件路径路由到 `index.php`，包括 `/pay/xxx/`：
- `/pay/notify/2026051722030666608/` → `index.php?s=/pay/notify/2026051722030666608/`
- `index.php` 不识别 `s=/pay/notify/...` 参数 → 返回首页HTML
- v免签服务端收到HTML页面而非 "success" → 判定异步通知失败

### 正确规则（来自易支付官方 nginx.txt）

```nginx
location / {
    if (!-e $request_filename) {
        rewrite ^/(.[a-zA-Z0-9\-\_]+).html$ /index.php?mod=$1 last;
    }
    rewrite ^/pay/(.*)$ /pay.php?s=$1 last;
}
location ^~ /plugins {
    deny all;
}
location ^~ /includes {
    deny all;
}
```

关键区别：
1. `.html` 规则仅匹配 `xxx.html` 格式，不会误捕获 `/pay/` 路径
2. `/pay/` 路径有**独立的无条件rewrite规则**，直接路由到 `pay.php`
3. 额外禁止 `/plugins/` 和 `/includes/` 目录访问

### curl验证

```
当前: curl http://127.0.0.1:688/pay/notify/xxx/ → 返回易支付首页HTML（错误）
期望: curl http://127.0.0.1:688/pay/notify/xxx/ → 返回 "success" 或 "error_sign"（正确）
```

## ADDED Requirements

### Requirement: 修正Nginx伪静态规则
系统 SHALL 使用易支付官方提供的rewrite规则，确保 `/pay/xxx/` 请求路由到 `pay.php?s=xxx`，而非 `index.php`。

#### Scenario: 异步通知回调正常
- **WHEN** v免签服务端发送异步通知到 `http://115.175.13.168:688/pay/notify/xxx/`
- **THEN** Nginx将请求rewrite到 `pay.php?s=notify/xxx/`
- **AND** 易支付系统正确处理通知并返回 "success"

#### Scenario: 同步回调正常
- **WHEN** 用户支付成功后跳转到 `http://115.175.13.168:688/pay/return/xxx/`
- **THEN** Nginx将请求rewrite到 `pay.php?s=return/xxx/`
- **AND** 易支付系统正确处理回调

### Requirement: 禁止访问敏感目录
系统 SHALL 在Nginx配置中添加 `/plugins/` 和 `/includes/` 目录的访问禁止规则。

## MODIFIED Requirements
无

## REMOVED Requirements
无
