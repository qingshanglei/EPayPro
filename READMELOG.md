# 聚合易支付（基于彩虹易支付二次开发）发布日志

本项目基于彩虹易支付框架进行二次开发，原项目已长期停更。本项目在保留核心支付功能的基础上，重点进行了全面安全加固和Bug修复。

> ⚠️ 版本号统一在 `includes/common.php` 的 `VERSION` 常量管理，每次发布更新时修改此值即可。

***

## 版本说明

- **上游项目**：彩虹易支付
- **基于版本**：v3045
- **本地版本号**：从 0.01 起始，独立递增
- **版本号规则**：每次发布新的本地修改，版本号 +0.01（如 0.01 → 0.02）

***

## 📋 版本 \[0.02] - 2026-05-17

## 🔍 功能概述

本次更新修复 v0.01 安全加固优化引入的7个Bug，并完成旧版密码兼容代码清理和登录防暴力破解机制改进。这些Bug在优化前不存在，均为安全加固过程中引入的回归问题。

## 🐛 问题修复（均为 v0.01 安全优化引入的Bug）

### 管理后台登录后跳回登录页

- **问题**：管理员登录成功后跳转到首页，又立即跳回登录页面，无法保持登录状态
- **原因**：v0.01 密码安全优化引入。`saveSetting()` 只写数据库不更新缓存，密码升级为bcrypt后，下次请求从缓存读取到旧明文密码，session计算结果不一致导致Cookie验证失败
- **修复**：所有 `saveSetting()` 调用后添加 `$CACHE->update()` 同步更新缓存
- **影响文件**：
  - `admin688/login.php`
  - `admin688/ajax_settle.php`
  - `admin688/transfer.php`
  - `includes/common.php`

### 管理后台退出后自动登录

- **问题**：点击退出后显示"您已成功注销"，但立即又显示"您已登陆"并跳回后台
- **原因**：v0.01 Cookie安全优化引入。Cookie路径从默认的 `/admin688/` 改为 `/`，退出时仅删除 `/` 路径的Cookie，旧路径 `/admin688/` 的Cookie仍有效
- **修复**：退出时同时删除 `/` 和 `/admin688/` 两个路径的Cookie，并 `unset($_COOKIE['admin_token'])`；登录时先清理旧路径Cookie再设置新Cookie
- **影响文件**：
  - `admin688/login.php`

### 登录错误次数锁定后无法登录

- **问题**：登录错误5次后计数器直接重置为0允许再次尝试5次，提示与密码错误相同，用户无法理解为何密码正确却登录失败；且攻击者可无限循环暴力破解
- **原因**：原版锁定机制设计缺陷（非优化引入，但优化时未一并修复）
- **修复**：实现指数延迟锁定策略，锁定检查在密码验证之前执行，锁定期间不验证密码并显示剩余等待时间
- **锁定规则**：
  | 错误次数 | 锁定时间 |
  |---------|---------|
  | 1-2次 | 无锁定，提示剩余尝试次数 |
  | 3次 | 5分钟 |
  | 5次 | 15分钟 |
  | 8次 | 30分钟 |
  | 10次+ | 1小时 |
- **影响文件**：
  - `admin688/login.php`

### 支付密码修改后验证失败

- **问题**：支付密码升级为bcrypt后，修改支付密码时旧密码验证使用明文比较，永远不匹配
- **原因**：v0.01 密码安全优化引入。支付密码修改逻辑未同步更新为bcrypt验证方式
- **修复**：支付密码修改逻辑改用 `password_verify`，新密码改用 `password_hash` 存储
- **影响文件**：
  - `admin688/set.php`

### 管理员密码修改表单CSRF验证失败

- **问题**：管理员密码修改表单提交后提示"CSRF验证失败"，密码修改永远无法成功
- **原因**：v0.01 CSRF防护优化引入。添加CSRF验证时遗漏了密码修改表单的 `csrf_token` 隐藏字段
- **修复**：在表单中添加 `csrf_token` 隐藏字段
- **影响文件**：
  - `admin688/set.php`

### 支付密码升级后session失效

- **问题**：`ajax_settle.php` 中支付密码升级后 `$conf` 未同步更新，导致session中存储的值与数据库不一致
- **原因**：v0.01 密码安全优化引入。密码升级后未同步更新内存中的 `$conf` 变量
- **修复**：密码升级后同步更新 `$conf` 和缓存
- **影响文件**：
  - `admin688/ajax_settle.php`

### 配置白名单缺少关键键名

- **问题**：`cronkey`、`sms_tpl_login`、`pay_succ_range_minute` 不在白名单中，后台修改这些配置静默失败
- **原因**：v0.01 配置覆盖漏洞修复引入。白名单遗漏了部分在用配置键名
- **修复**：补充到白名单
- **影响文件**：
  - `admin688/ajax.php`

### 支付通道等页面AJAX提交CSRF验证失败

- **问题**：新增支付通道、支付类型等操作报错"CSRF验证失败，请刷新页面重试"
- **原因**：v0.01 CSRF防护优化引入。`$.ajaxSetup({data: {csrf_token: ...}})` 设置的默认数据是对象形式，但 `$("#form").serialize()` 返回字符串会完全替换 `data`，导致 `csrf_token` 丢失
- **修复**：在所有使用 `.serialize()` 的AJAX请求中，手动拼接 `csrf_token` 到序列化字符串末尾
- **影响文件**：
  - `admin688/pay_channel.php` — 2处
  - `admin688/pay_weixin.php` — 1处
  - `admin688/pay_type.php` — 1处
  - `admin688/pay_roll.php` — 2处

## 🏗️ 代码清理

### 移除旧版密码兼容代码

- **说明**：所有密码已升级为bcrypt哈希，移除旧版明文密码和MD5密码的兼容逻辑
- **清理内容**：
  - 删除 `getMd5Pwd()` 函数
  - `verifyPwd()` 简化为纯 `password_verify()` 调用（2参数，移除salt参数）
  - 移除所有 `strlen($hash) < 60` 明文密码兼容判断
  - 移除登录/验证时的密码自动升级逻辑（`saveSetting` + `$CACHE->update`）
- **影响文件**：
  - `includes/functions.php`
  - `admin688/login.php`、`set.php`、`ajax_settle.php`、`ajax_order.php`、`transfer.php`
  - `user/ajax.php`、`user/ajax2.php`

***

## 📋 版本 \[0.01] - 2026-05-16

## 🔍 功能概述

本次更新为项目首次全面安全加固迭代，涵盖SQL注入修复、XSS防护、CSRF防护、密码安全存储升级、硬编码密钥消除、Cookie安全加固等26+项安全漏洞修复。经审计未发现恶意后门代码。在保留全部核心支付功能的前提下，消除了大量安全风险。

## 🔒 安全加固

### SQL注入漏洞修复（30+处）

- **问题**：管理后台多处 `$_POST['column']`/`$_POST['value']`/`trade_no`/`batch`/`checkbox` 等参数直接拼接SQL，存在SQL注入风险
- **修复**：所有动态列名添加白名单验证，所有动态值改用PDO参数绑定（`?`占位符 + 数组参数）
- **影响文件**：
  - `admin688/ajax_order.php` — 12处修复
  - `admin688/ajax_user.php` — 5处修复
  - `admin688/ajax_settle.php` — 3处修复
  - `admin688/ajax_pay.php` — 6处修复
  - `admin688/uset.php` — 2处修复（`uid` 添加 `intval` 转换）
  - `user/ajax2.php` — 8处修复
  - `submit.php` — 1处修复

### XSS漏洞修复（37处）

- **问题**：管理后台多处用户输入和数据库字段未经 `htmlspecialchars` 转义直接输出到HTML属性
- **修复**：所有输出统一使用 `htmlspecialchars($var, ENT_QUOTES, 'UTF-8')` 转义
- **影响文件**：
  - `admin688/login.php` — `$_POST['user']` 未转义输出
  - `admin688/uset.php` — 数据库字段直接输出到HTML属性
  - `admin688/ajax_user.php`、`ajax_settle.php`、`ajax_pay.php` — 结算信息和通道配置未转义输出
  - `admin688/set.php` — 31处 `$conf` 配置值未转义输出

### CSRF防护完善

- **问题**：管理后台全部AJAX接口缺少CSRF Token验证，攻击者可构造恶意页面诱骗管理员操作
- **修复**：在 `admin688/head.php` 中生成并输出 `csrf_token`，通过 jQuery `$.ajaxSetup` 自动为所有AJAX POST请求携带Token；非AJAX表单页面添加隐藏 `csrf_token` 字段
- **影响文件**：
  - `admin688/head.php`
  - `admin688/ajax.php`、`ajax_order.php`、`ajax_user.php`、`ajax_settle.php`、`ajax_pay.php`
  - `admin688/set.php`、`transfer.php`、`uset.php`、`clean.php`

### 密码安全存储升级

- **问题**：管理员密码和支付密码以明文存储在数据库中；商户密码使用双重MD5（弱哈希），存在严重安全隐患
- **修复**：使用 bcrypt 哈希存储（`password_hash`/`password_verify`），新增 `verifyPwd()` 函数兼容旧版密码自动迁移
- **影响文件**：
  - `includes/functions.php` — 新增 `verifyPwd()` 函数
  - `admin688/login.php` — 管理员登录验证改用 `password_verify`
  - `admin688/set.php` — 密码修改改用 `password_hash` 存储
  - `admin688/ajax_settle.php`、`ajax_order.php`、`transfer.php` — 支付密码验证改用 `password_verify`
  - `user/ajax.php` — 商户登录验证改用 `verifyPwd`，注册改用 `password_hash`
  - `user/ajax2.php` — 商户密码修改改用 `verifyPwd` + `password_hash`
  - `install/install.sql` — 默认管理员密码改为bcrypt哈希

### authcode硬编码密钥修复

- **问题**：`authcode.php` 中密钥 `cdb677514f95a00b0a6cb1f5347a9b4f` 所有安装共享，攻击者可伪造任意用户Cookie
- **修复**：移除硬编码密钥，`SYS_KEY` 改为从数据库配置读取，首次安装自动生成随机密钥（`random_bytes(16)`）
- **影响文件**：
  - `includes/authcode.php` — 删除硬编码密钥
  - `includes/common.php` — 从数据库读取syskey，不存在则自动生成

### 任意配置覆盖漏洞修复

- **问题**：`admin688/ajax.php` 的 `set` 接口直接遍历 `$_POST` 保存所有配置，攻击者可覆盖 `admin_pwd`、`syskey` 等敏感配置
- **修复**：添加80+配置键白名单验证，`admin_user`、`admin_pwd`、`admin_paypwd`、`syskey` 等敏感配置不在白名单中
- **影响文件**：
  - `admin688/ajax.php`

### Cookie安全标志修复

- **问题**：所有认证Cookie缺少 `HttpOnly`、`Secure`、`SameSite` 标志，且部分Cookie缺少 `path=/` 导致跨路径不可见
- **修复**：统一使用标量格式 `setcookie`，添加 `path=/`、`httponly=true`、`secure`（HTTPS时）；设置新Cookie前清理旧路径残留Cookie
- **影响文件**：
  - `admin688/login.php` — admin_token
  - `admin688/sso.php` — user_token
  - `user/ajax.php`、`user/login.php`、`user/oauth.php`、`user/connect.php`、`user/wxlogin.php`

### IP伪造漏洞修复

- **问题**：`real_ip()` 函数默认信任 `X-Forwarded-For` 等代理头部，攻击者可伪造IP绕过IP黑名单
- **修复**：默认使用 `REMOTE_ADDR`，仅当配置 `ip_type > 0` 时才解析代理头部，添加 `filter_var` IP格式验证
- **影响文件**：
  - `includes/functions.php` — `real_ip()` 函数
  - `admin688/ajax.php`、`admin688/set.php` — IP获取方式配置选项

### SSL证书验证启用

- **问题**：`curl_get()` 和 `get_curl()` 函数禁用SSL证书验证，存在中间人攻击风险
- **修复**：启用 `CURLOPT_SSL_VERIFYPEER=true` 和 `CURLOPT_SSL_VERIFYHOST=2`，添加CA证书路径自动检测
- **影响文件**：
  - `includes/functions.php`

### 文件上传安全加固

- **问题**：`admin688/set.php` 文件上传无类型验证，可上传任意文件
- **修复**：添加MIME类型验证，仅允许 `image/png`、`image/jpeg`、`image/gif`、`image/webp` 格式
- **影响文件**：
  - `admin688/set.php`

### 弱随机数修复

- **问题**：`random()` 函数使用 `mt_rand()`，非密码学安全；订单号随机部分仅5位数字
- **修复**：`random()` 改用 `random_bytes()` 和 `random_int()`；订单号随机部分从5位数字增强为8位数字
- **影响文件**：
  - `includes/functions.php` — `random()` 函数
  - `submit.php` — 订单号生成逻辑

### 签名比较安全加固

- **问题**：签名/密钥比较使用 `==`，存在时序攻击风险
- **修复**：统一改用 `hash_equals()` 防止时序攻击
- **影响文件**：
  - `includes/lib/PayUtils.php` — 签名比较
  - `api.php` — 密钥比对
  - `cron.php` — 密钥验证，同时支持GET和POST传参

### 其他安全修复

- **问题**：`install/update.php` 无认证可执行；注册密码明文缓存；`file_put_contents` 使用相对路径；多处 `$DB->error()` 信息泄露
- **修复**：
  - `install/update.php` 添加 `install.lock` 文件检查
  - `.htaccess` 添加install目录访问限制规则
  - `user/ajax.php` 注册密码缓存改为bcrypt哈希
  - `user/ajax2.php` 中 `file_put_contents` 改用绝对路径
  - 多处 `$DB->error()` 改为通用错误提示
- **影响文件**：
  - `install/update.php`、`.htaccess`、`user/ajax.php`、`user/ajax2.php`、`admin688/ajax_pay.php`、`admin688/ajax_user.php`、`admin688/ajax_settle.php`、`admin688/ajax_order.php`

## 🔄 兼容性说明

| 兼容项 | 说明 |
|--------|------|
| 支付插件签名算法 | ✅ 未变更，MD5签名正常工作 |
| 商户API对接 | ✅ 完全兼容，接口参数未变更 |
| 数据库结构 | ✅ 无变更，`pre_user.pwd` 字段从 `varchar(32)` 扩展为 `varchar(255)` |
| 旧版明文/MD5密码 | ⚠️ 不再兼容，需确保所有密码已升级为bcrypt哈希 |

***

## 📋 上游版本 \[v3045] - 初始部署

| 项目 | 说明 |
|------|------|
| 项目名称 | 彩虹易支付 |
| 技术栈 | PHP + MySQL + Bootstrap + jQuery |
| 核心功能 | 聚合支付接入（支付宝/微信/QQ/京东/PayPal/USDT等20+插件）、商户系统、通道管理、轮询机制、风控系统、管理后台 |
| 状态 | **已停更** |

**上游原始功能清单**：

- 支付订单模块：创建订单 / 查询订单 / 关闭订单 / 退款
- 通道管理：支付通道配置 / 轮询策略 / 通道分组
- 商户系统：注册 / 登录 / 结算 / API对接
- 管理后台：订单管理 / 商户管理 / 通道管理 / 系统设置 / 企业付款
- 聚合收款码：paypage目录
- 自动结算、通知重试、企业付款等核心功能
