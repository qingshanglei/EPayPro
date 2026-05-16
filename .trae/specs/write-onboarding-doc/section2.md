# 二、技术栈说明

## 2.1 服务端技术栈

- **PHP >= 7.1**：项目入口文件 `index.php` 通过 `version_compare(PHP_VERSION, '7.1.0', '<')` 强制要求最低版本；Composer 依赖要求 `>= 7.2.5`（见 `includes/vendor/composer/platform_check.php`）。当前程序版本号 `VERSION = 3045`，数据库版本 `DB_VERSION = 2024`，定义于 `includes/common.php`。
- **MySQL 5.5+**：通过 PDO 扩展连接，字符集为 `utf8mb4`（在 `includes/lib/PdoHelper.php` 构造函数中通过 `set names utf8mb4` 设置），支持完整的 Unicode 字符（包括 Emoji）。
- **Apache/Nginx Web 服务器**：项目通过标准 PHP-CGI 方式运行，支持 HTTPS 检测（`is_https()` 函数兼容 `SERVER_PORT`、`HTTPS`、`HTTP_X_CLIENT_SCHEME`、`HTTP_X_FORWARDED_PROTO`、`REQUEST_SCHEME`、`HTTP_EWS_CUSTOME_SCHEME` 等多种判断方式），适配反向代理和 CDN 环境。
- **数据表前缀机制**：SQL 语句中使用 `pre_` 作为占位前缀，由 `PdoHelper::dealPrefix()` 在执行时自动替换为实际前缀 `{dbqz}_`（如配置中 `dbqz = pay`，则 `pre_config` → `pay_config`），实现多实例部署时的表名隔离。
- **基于数据库的缓存系统**：`includes/lib/Cache.php` 实现了以 MySQL 表 `pre_cache` 为存储后端的缓存机制，通过 `REPLACE INTO` 语句实现键值对读写。系统配置（`pre_config` 表）在每次请求时通过 `pre_fetch()` 反序列化加载到全局变量 `$_CACHE` 中，避免频繁查询。微信 Access Token 等临时数据也通过此缓存系统存储，并利用 `FOR UPDATE` 行锁保证并发安全。
- **Session 管理**：使用 PHP 原生 Session 机制（`session_start()`），时区固定为 `Asia/Shanghai`。
- **PSR-4 自动加载**：通过 `includes/autoloader.php` 注册 Autoloader，命名空间 `lib\` 对应 `includes/lib/` 目录，支持类的按需加载。
- **安全防护**：集成 360 网站卫士（`360safe/360webscan.php`）和腾讯云安全防护（`txprotect.php`），支持代理请求（HTTP/HTTPS/SOCKS4/SOCKS5）。

## 2.2 前端技术栈

### JavaScript 库

| 库名 | 版本 | 使用场景 |
|------|------|----------|
| jQuery | 1.12.4 | 支付页面（扫码支付、JSAPI 支付、H5 支付、收银台等） |
| jQuery | 2.1.4 | 管理后台（`admin688/head.php`） |
| jQuery | 3.4.1 | 商户中心（`user/head.php`、登录/注册/OAuth 页面、首页模板） |
| Modernizr | 2.8.3 | 管理后台浏览器特性检测 |
| jquery.qrcode | 1.0 | 支付二维码前端生成（微信/支付宝/QQ/京东/银联扫码页面） |
| html5shiv | 3.7.3 | 管理后台 IE8 兼容（条件注释加载） |
| respond.js | 1.4.2 | 管理后台 IE8 响应式兼容（条件注释加载） |

### CSS 框架与图标

| 库名 | 版本 | 使用场景 |
|------|------|----------|
| Bootstrap | 3.3.7 | 商户中心 UI 框架 |
| Bootstrap | 3.4.1 | 管理后台 UI 框架 |
| Font Awesome | 4.7.0 | 全站图标字体 |
| Simple Line Icons | 2.4.1 | 商户中心辅助图标 |
| Animate.css | 3.5.2 | CSS 动画效果 |

### 自定义资源

- `user/assets/css/font.css`：自定义字体样式
- `user/assets/css/app.css`：商户中心自定义样式
- `assets/css/bootstrap.min.css`：管理后台自定义 Bootstrap 覆盖样式
- `assets/css/bootstrap-table.css`：数据表格自定义样式

### CDN 支持

系统支持四种公共 CDN 源，通过后台配置项 `cdnpublic` 切换（定义于 `includes/common.php`）：

| 配置值 | CDN 源 | 地址 |
|--------|--------|------|
| 1 | 宝塔 CDN（宝塔魔贴） | `//lib.baomitu.com/` |
| 2 | BootCDN | `https://cdn.bootcdn.net/ajax/libs/` |
| 4 | 字节 CDN | `//lf26-cdn-tos.bytecdntp.com/cdn/expire-1-M/` |
| 其他（默认 3） | StaticFile CDN | `//cdn.staticfile.org/` |

管理后台（`admin688/head.php`）独立配置 `$admin_cdnpublic`，当前默认使用 StaticFile CDN。

## 2.3 第三方 SDK 与集成服务

### 支付渠道 SDK

| 服务 | 插件目录 | 功能说明 |
|------|----------|----------|
| 支付宝（alipaysl） | `plugins/alipaysl/` | 支付宝官方 SDK，支持当面付（扫码/条码）、手机网站支付、电脑网站支付、单笔转账到支付宝/银行卡、OAuth 授权登录、身份认证初始化与查询、商家授权等 |
| 支付宝（alipay） | `plugins/alipay/` | 支付宝完整 SDK，在 alipaysl 基础上增加实名认证（`AlipayCertifyService`）、证件验证（`AlipayCertdocService`）、安全风险（`AlipaySecurityService`）等服务 |
| 微信支付（wxpaysl） | `plugins/wxpaysl/` | 微信支付官方 SDK，支持 JSAPI 支付、Native 扫码支付、小程序支付、异步通知处理 |
| 微信支付（wxpay） | `plugins/wxpay/` | 微信支付 SDK，在 wxpaysl 基础上支持企业付款到零钱（`WxPayTransfer`） |
| 微信支付 V3（wxpayn） | `plugins/wxpayn/` | 微信支付 V3 版本 SDK，支持商家转账到零钱（含用户姓名加密） |
| 微信支付 V3（wxpaynp） | `plugins/wxpaynp/` | 微信支付 V3 版本 SDK（支付功能） |
| QQ 钱包 | `plugins/qqpay/` | QQ 钱包支付 SDK，支持扫码支付、企业付款（`qpayMchAPI`） |
| 京东支付 | `plugins/jdpay/` | 京东支付 SDK，含 RSA 签名、TDES 加密、XML 工具等 |
| PayPal | `plugins/paypal/` | PayPal REST API SDK，支持国际支付 |
| USDT | `plugins/usdt/` | USDT 加密货币支付插件 |

### 第三方聚合支付插件

| 插件名 | 目录 | 说明 |
|--------|------|------|
| Jeepay | `plugins/jeepay/` | 开源聚合支付平台，支持支付宝/微信转账 |
| SwiftPass（威富通） | `plugins/swiftpass2/` | 威富通聚合支付 |
| 多拉宝 | `plugins/duolabao/` | 多拉宝聚合支付 |
| Adapay | `plugins/adapay/` | Adapay 聚合支付 |
| 讯虎支付 | `plugins/xunhupay/`、`plugins/xunhupay2/` | 讯虎支付（两个版本） |
| PayJS | `plugins/payjs/` | PayJS 微信支付 |
| 易码支付 | `plugins/epay/` | 易码支付接口 |
| 银联商务 | `plugins/chinaums/` | 银联商务（ChinaUMS） |
| 掌易收 | `plugins/zhangyishou/` | 掌易收支付 |
| 开心支付 | `plugins/kayixin/` | 开心支付 |
| VMQ | `plugins/vmq/` | V免签支付 |
| 青橙支付 | `plugins/qxapp/` | 青橙支付 |
| 爱支付 | `plugins/woaizf/` | 爱支付 |
| 众语 | `plugins/zyu/` | 众语支付 |
| 米付 | `plugins/mirfupay/` | 米付支付 |
| XorPay | `plugins/xorpay/` | XorPay 支付 |
| 云码支付 | `plugins/ympay/` | 云码支付 |
| 速通付 | `plugins/sytpay/` | 速通付支付 |
| 支付宝旧版 | `plugins/aliold/` | 支付宝 MD5 签名旧版接口 |

### 验证码服务

- **极验验证码（GeeTest）**：`includes/lib/GeetestLib.php`，SDK 版本 `php_3.0.0`，支持正常模式和宕机降级模式。当配置了 `captcha_id` 和 `private_key` 时使用自有极验服务，否则使用极验 Demo 服务。

### 邮件服务

通过 `send_mail()` 函数统一调用，根据配置项 `mail_cloud` 选择驱动：

| 配置值 | 驱动 | 实现类 |
|--------|------|--------|
| 0（默认） | PHPMailer（SMTP） | `lib\mail\PHPMailer\PHPMailer`，支持 TLS/SSL 加密，端口自适应（587→TLS，465+→SSL） |
| 1 | SendCloud | `lib\mail\Sendcloud` |
| 2 | 阿里云邮件推送 | `lib\mail\Aliyun` |

### 短信服务

通过 `send_sms()` 函数统一调用，根据配置项 `sms_api` 选择驱动，支持注册、登录、找回密码、修改信息等场景模板：

| 配置值 | 驱动 | 实现类 |
|--------|------|--------|
| 1 | 腾讯云短信 | `lib\sms\TencentSms` |
| 2 | 阿里云短信 | `lib\sms\Aliyun` |
| 3 | 顶想云短信 | 通过 `https://api.topthink.com/sms/send` API 调用 |
| 其他 | 自定义短信接口 | 通过 `http://sms.php.gs/sms/send/yzm` API 调用 |

### 实名认证服务

系统支持四种实名认证方式（`show_cert_method()` 函数），覆盖个人和企业认证：

| 认证方式 | 实现说明 |
|----------|----------|
| 手机号三要素认证 | 阿里云市场 API（`phone3.market.alicloudapi.com/phonethree`），验证姓名+身份证号+手机号一致性 |
| 腾讯云人脸核身 | `includes/lib/QcloudFaceid.php`，调用 `faceid.tencentcloudapi.com`，支持 `GetRealNameAuthToken`（获取认证 Token）和 `GetRealNameAuthResult`（查询认证结果），使用 TC3-HMAC-SHA256 签名算法 |
| 阿里云金融实名认证 | `includes/lib/AliyunCertify.php`，调用 `saf.cn-shanghai.aliyuncs.com`，支持 `FACE_SDK` 方式的身份认证初始化和结果查询，使用 HMAC-SHA1 签名 |
| 支付宝快捷认证 | 通过 `plugins/alipay/` 的 `AlipayCertifyService` 实现，调用支付宝身份认证 API（初始化/查询）和证件验证 API |
| 人工审核认证 | 后台人工审核方式 |
| 企业实名认证 | 阿里云市场 API（`companythree.shumaidata.com/companythree/check`），验证企业名称+统一信用代码+法人姓名一致性 |

### 第三方登录

| 登录方式 | 实现类 | 说明 |
|----------|--------|------|
| QQ 登录 | `includes/lib/QC.php` | QQ 互联 OAuth 2.0 SDK v2.0，支持获取授权码、Access Token、OpenID |
| 支付宝登录 | `plugins/alipaysl/` 的 `AlipayOauthService` | 支付宝 OAuth 授权，支持换取 Access Token 和获取用户信息 |
| 微信登录 | `includes/lib/Oauth.php` + 微信 OAuth API | 通过聚合 OAuth 接口或直接调用微信 API 实现 |
| 聚合 OAuth | `includes/lib/Oauth.php` | 通用第三方登录接口，支持通过统一 API 接入多种社交登录 |

### QR 码相关

| 功能 | 实现方式 | 说明 |
|------|----------|------|
| QR 码生成 | `jquery.qrcode` 1.0（前端） | 在支付页面通过 jQuery 插件在浏览器端生成二维码 |
| QR 码解码 | `includes/qrcodedecoder/`（Zxing PHP 移植版） | PHP 端二维码解码库，用于识别上传的二维码图片 |

### 微信公众号/小程序

- `includes/lib/wechat/MiniAppPay.php`：微信小程序支付
- `includes/lib/wechat/JsApiPay.php`：微信公众号 JSAPI 支付
- `wx_get_access_token()`：微信 Access Token 管理，带数据库缓存和行锁
- `wxa_generate_scheme()`：微信小程序 URL Scheme 生成

## 2.4 PHP 扩展依赖

| 扩展名 | 用途 | 使用位置 |
|--------|------|----------|
| pdo_mysql | MySQL 数据库连接 | `PdoHelper` 类核心依赖，所有数据库操作 |
| curl | HTTP 请求 | `curl_get()`、`get_curl()` 函数，支付 API 调用、短信/邮件服务、实名认证接口、极验验证码通信等 |
| gd | 图像处理 | QR 码解码（`Zxing\GDLuminanceSource`）、验证码图片生成 |
| mbstring | 多字节字符串处理 | `mb_convert_encoding()` 用于 IP 归属地查询的 GB2312→UTF-8 编码转换 |
| json | JSON 编解码 | 全局使用，API 响应解析、配置序列化、前后端数据交互 |
| openssl | 加密与签名 | 支付宝 RSA 签名/验签、微信支付证书加密、HMAC-SHA256 签名、`authcode()` 加解密函数 |
| session | 会话管理 | 用户登录状态、OAuth state、验证码会话等 |
