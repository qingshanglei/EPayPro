# 一、项目架构概述

## 1.1 系统整体架构

聚合易支付采用经典的三层架构设计，各层职责分明、耦合度低：

### 入口层

入口层由根目录下的多个 PHP 入口文件组成，每个文件承担不同的请求入口角色：

| 入口文件 | 职责说明 |
|---------|---------|
| `index.php` | 首页入口，根据 `mod` 参数加载对应模板页面，处理页面路由 |
| `submit.php` | 支付提交入口，接收商户支付请求，验证签名、创建订单、匹配通道后跳转支付 |
| `submit2.php` | 收银台二次提交入口，从收银台选择支付方式后再次提交，获取通道并跳转支付 |
| `pay.php` | 支付回调路由入口，根据 URL 中的 `s` 参数加载对应支付插件的回调处理逻辑 |
| `cashier.php` | 收银台页面，展示订单信息和可选支付方式列表，供用户选择支付方式 |
| `cron.php` | 计划任务入口，处理自动结算生成、订单统计与清理、异步通知重试等定时任务 |
| `mapi.php` | 移动端 API 入口，提供 JSON 格式的支付提交接口，适配移动端场景 |
| `api.php` | 开放 API 入口，提供订单查询、结算查询、批量订单查询、退款等外部接口 |
| `getshop.php` | 订单状态查询入口，根据订单号返回支付结果和跳转地址 |
| `gold.php` | 微信点金计划 iframe 页面，处理微信支付完成后的商户展示页面跳转 |

### 业务逻辑层

业务逻辑层位于 `includes/lib/` 目录下，以命名空间 `\lib\` 组织核心类库：

| 核心类 | 职责说明 |
|-------|---------|
| `PdoHelper` | 数据库操作封装类，基于 PDO 实现，支持自动表前缀替换（`pre_` → 实际前缀），提供 `query`、`getRow`、`getColumn`、`getAll`、`exec` 等方法 |
| `Cache` | 缓存管理类，基于数据库 `pre_cache` 表实现键值缓存，支持序列化存储，提供 `get`、`read`、`save`、`pre_fetch`、`update`、`clean` 等方法 |
| `Channel` | 支付通道管理类，负责通道查询、通道匹配（`submit`/`submit2`）、费率计算、通道限额判断等核心路由逻辑 |
| `Plugin` | 支付插件管理类，负责插件的发现、加载和调用，提供 `getList`、`getConfig`、`loadForPay`、`loadForSubmit`、`refund` 等方法 |
| `Payment` | 支付结果输出类，根据插件返回的结果类型（`jump`/`html`/`json`/`page`/`qrcode`/`scheme`）渲染不同的响应页面 |
| `PayUtils` | 支付工具类，提供签名相关功能，包括参数拼接（`createLinkstring`）、参数过滤（`paraFilter`）、参数排序（`argSort`）、MD5 签名（`md5Sign`）和验签（`md5Verify`） |
| `Template` | 模板管理类，负责模板的发现和加载，优先加载当前主题模板，回退到 `default` 默认模板 |
| `Oauth` | 快捷登录类，对接第三方 OAuth 登录服务，支持 QQ、微信等快捷登录方式 |
| `GeetestLib` | 极验验证码类，集成极验行为式验证安全平台 SDK |
| `QC` | QQ 互联 SDK 类，封装 QQ 登录 OAuth2.0 接口调用 |
| `hieroglyphy` | 混淆编码类，用于对敏感信息进行 JavaScript 混淆编码输出 |
| `QcloudFaceid` | 腾讯云人脸核身类，封装腾讯云 FaceID API，用于实名认证场景 |
| `AliyunCertify` | 阿里云实人认证类，封装阿里云安全认证 API，用于实名认证场景 |

### 数据层

数据层由 MySQL 数据库和基于数据库的缓存机制组成：

- **MySQL 数据库**：通过 `PdoHelper` 类统一访问，使用 `config.php` 中配置的连接参数，表前缀通过 `$dbconfig['dbqz']` 定义（如 `pay_`），代码中使用 `pre_` 占位符自动替换
- **数据库缓存**：通过 `Cache` 类操作 `pre_cache` 表实现，系统配置（`pre_config` 表全部数据）在每次请求时序列化加载到全局变量 `$_CACHE`/`$conf` 中，支持按需更新和全量清理
- **数据表核心结构**：
  - `pre_config` — 系统配置表
  - `pre_cache` — 缓存键值表
  - `pre_user` — 商户用户表
  - `pre_order` — 支付订单表
  - `pre_channel` — 支付通道表
  - `pre_type` — 支付方式表
  - `pre_settle` — 结算记录表
  - `pre_domain` — 域名白名单表
  - `pre_risk` — 风控记录表
  - `pre_regcode` — 注册验证码表

## 1.2 目录结构说明

```
pay/
├── index.php              # 首页入口，根据 mod 参数加载模板页面
├── submit.php             # 支付提交入口，商户发起支付请求的接收端
├── submit2.php            # 收银台二次提交入口，收银台选择支付方式后提交
├── pay.php                # 支付回调路由入口，根据 URL 路径加载对应插件回调
├── cashier.php            # 收银台页面，展示订单信息和支付方式选择
├── cron.php               # 计划任务入口，处理结算、统计、通知重试等
├── mapi.php               # 移动端 API 入口，JSON 格式支付提交接口
├── api.php                # 开放 API 入口，订单查询/结算查询/退款等外部接口
├── getshop.php            # 订单状态查询，返回支付结果和跳转地址
├── gold.php               # 微信点金计划 iframe 页面
├── config.php             # 数据库配置文件（主机/端口/用户名/密码/库名/表前缀）
├── .htaccess              # Apache URL 重写规则
├── nginx.txt              # Nginx URL 重写规则参考
├── IIS.txt                # IIS URL 重写规则参考（web.config 片段）
│
├── includes/              # 核心框架目录
│   ├── common.php         # 公共引导文件，系统初始化核心流程
│   ├── autoloader.php     # PSR-0 风格自动加载器，注册 spl_autoload_register
│   ├── functions.php      # 全局函数库（curl_get、do_notify、changeUserMoney 等）
│   ├── member.php         # 会员登录状态检测（admin_token/user_token Cookie 解密验证）
│   ├── authcode.php       # 授权码定义文件
│   ├── txprotect.php      # 腾讯云防护/反爬虫屏蔽（过滤恶意蜘蛛和异常请求）
│   ├── security.php       # 安全辅助函数（real_ip 获取真实 IP）
│   ├── ValidateCode.class.php  # 图形验证码生成类
│   ├── composer.json      # Composer 配置文件
│   ├── .htaccess          # 目录访问限制（禁止直接访问）
│   ├── pages/             # 支付页面模板目录
│   │   ├── wxpay_qrcode.php    # 微信扫码支付页面
│   │   ├── wxpay_jspay.php     # 微信 JSAPI 支付页面
│   │   ├── wxpay_h5.php        # 微信 H5 支付页面
│   │   ├── wxpay_wap.php       # 微信 WAP 支付页面
│   │   ├── wxpay_mini.php      # 微信小程序支付页面
│   │   ├── wxopen.php          # 微信授权页面
│   │   ├── alipay_qrcode.php   # 支付宝扫码支付页面
│   │   ├── alipay_qrcodepc.php # 支付宝 PC 扫码支付页面
│   │   ├── alipay_jspay.php    # 支付宝 JSAPI 支付页面
│   │   ├── qqpay_qrcode.php    # QQ 钱包扫码支付页面
│   │   ├── qqpay_jspay.php     # QQ 钱包 JSAPI 支付页面
│   │   ├── qqpay_wap.php       # QQ 钱包 WAP 支付页面
│   │   ├── jdpay_qrcode.php    # 京东支付扫码页面
│   │   ├── bank_qrcode.php     # 银行支付扫码页面
│   │   ├── openid.php          # OpenID 获取页面
│   │   ├── certok.php          # 认证成功页面
│   │   ├── ok.php              # 支付成功页面
│   │   ├── error.php           # 支付错误页面
│   │   └── return.php          # 支付回跳页面
│   └── lib/               # 核心类库目录（命名空间 \lib\）
│       ├── PdoHelper.php       # PDO 数据库操作封装类
│       ├── Cache.php           # 数据库缓存管理类
│       ├── Channel.php         # 支付通道管理与路由类
│       ├── Plugin.php          # 支付插件管理类
│       ├── Payment.php         # 支付结果输出渲染类
│       ├── PayUtils.php        # 支付签名/验签工具类
│       ├── Template.php        # 模板加载与管理类
│       ├── Oauth.php           # 第三方 OAuth 快捷登录类
│       ├── GeetestLib.php      # 极验行为验证码 SDK
│       ├── QC.php              # QQ 互联 OAuth2.0 SDK
│       ├── hieroglyphy.php     # JavaScript 混淆编码类
│       ├── QcloudFaceid.php    # 腾讯云人脸核身 API 封装
│       └── AliyunCertify.php   # 阿里云实人认证 API 封装
│
├── plugins/               # 支付插件目录（每个子目录为一个独立支付插件）
│   ├── .htaccess          # 禁止直接访问（deny from all）
│   ├── alipay/            # 支付宝当面付（旧版）插件
│   ├── alipaysl/          # 支付宝独立商户（沙箱/独立应用）插件
│   ├── aliold/            # 支付宝老版即时到账插件
│   ├── wxpay/             # 微信支付（扫码/JSAPI/H5/小程序）插件
│   ├── wxpayn/            # 微信支付（V3 版本）插件
│   ├── wxpaynp/           # 微信支付（V3 新版）插件
│   ├── wxpaysl/           # 微信支付（服务商模式）插件
│   ├── qqpay/             # QQ 钱包支付插件
│   ├── jdpay/             # 京东支付插件
│   ├── paypal/            # PayPal 国际支付插件
│   ├── unionpay/          # 银联支付插件
│   ├── allinpay/          # 通联支付插件
│   ├── epay/              # 易支付（聚合支付平台对接）插件
│   ├── payjs/             # PayJS 微信支付通道插件
│   ├── xorpay/            # XorPay 支付通道插件
│   ├── xunhupay/          # 迅虎支付插件
│   ├── xunhupay2/         # 迅虎支付 V2 版本插件
│   ├── swiftpass/         # 威富通支付插件
│   ├── swiftpass2/        # 威富通支付 V2 版本插件
│   ├── ympay/             # 云码支付插件
│   ├── sytpay/            # 盛悦通支付插件
│   ├── kayixin/           # 卡易信支付插件
│   ├── mirfupay/          # 米付支付插件
│   ├── duolabao/          # 多拉宝支付插件
│   ├── adapay/            # Adapay 支付插件
│   ├── chinaums/          # 银联商务支付插件
│   ├── zhangyishou/       # 掌易收支付插件
│   ├── ysepay/            # 银盛支付插件
│   ├── jeepay/            # Jeepay 开源支付平台对接插件
│   ├── qxapp/             # QXApp 支付插件
│   ├── vmq/               # V免签支付插件
│   ├── usdt/              # USDT 数字货币支付插件
│   └── woaizf/            # 我爱支付插件
│
├── template/              # 首页模板目录
│   ├── default/           # 默认模板（含 index/head/foot/doc/agreement/wx/payok 等页面）
│   ├── index1/            # 模板1（企业风格，含首页/产品介绍/关于我们/协议/文档等页面）
│   ├── index2/            # 模板2
│   ├── index3/            # 模板3
│   ├── index4/            # 模板4（UASPAY 风格，含后台管理风格首页）
│   ├── index5/            # 模板5（现代滑动风格）
│   ├── index6/            # 模板6
│   ├── index7/            # 模板7（粒子动画风格，含 AOS/Particles 特效）
│   ├── index8/            # 模板8
│   ├── index9/            # 模板9（EasyAPI 风格，SVG 图标风格）
│   ├── index10/           # 模板10（流程展示风格，含支付流程/优势展示等）
│   └── index11/           # 模板11
│
├── user/                  # 商户后台目录
│   ├── index.php          # 商户后台首页/仪表盘
│   ├── login.php          # 商户登录页面
│   ├── reg.php            # 商户注册页面
│   ├── findpwd.php        # 找回密码页面
│   ├── userinfo.php       # 个人信息页面
│   ├── editinfo.php       # 编辑信息页面
│   ├── order.php          # 订单管理页面
│   ├── settle.php         # 结算管理页面
│   ├── domain.php         # 域名管理页面
│   ├── certificate.php    # 实名认证页面
│   ├── certificate_mobile.php  # 手机实名认证页面
│   ├── alipaycert.php     # 支付宝认证页面
│   ├── alipaycertok.php   # 支付宝认证完成页面
│   ├── recharge.php       # 余额充值页面
│   ├── apply.php          # 申请页面
│   ├── record.php         # 交易记录页面
│   ├── help.php           # 帮助中心页面
│   ├── oneode.php         # 一码付页面
│   ├── groupbuy.php       # 用户组购买页面
│   ├── connect.php        # 第三方登录连接页面
│   ├── wxlogin.php        # 微信登录页面
│   ├── qrlogin.php        # 扫码登录页面
│   ├── openid.php         # OpenID 页面
│   ├── oauth.php          # OAuth 回调页面
│   ├── completeinfo.php   # 完善信息页面
│   ├── test.php           # 支付测试页面
│   ├── head.php           # 商户后台头部公共文件
│   ├── foot.php           # 商户后台底部公共文件
│   ├── ajax.php           # 商户后台 AJAX 请求处理
│   ├── ajax2.php          # 商户后台 AJAX 请求处理（补充）
│   ├── sso.php            # 单点登录处理
│   └── assets/            # 商户后台静态资源（CSS/JS/图片/字体/第三方库）
│
├── admin688/              # 管理后台目录
│   ├── index.php          # 管理后台首页/仪表盘
│   ├── login.php          # 管理员登录页面
│   ├── set.php            # 系统设置页面
│   ├── uset.php           # 用户设置页面
│   ├── ulist.php          # 商户列表管理页面
│   ├── ustat.php          # 商户统计页面
│   ├── order.php          # 订单管理页面
│   ├── settle.php         # 结算管理页面
│   ├── slist.php          # 结算列表页面
│   ├── pay_channel.php    # 支付通道管理页面
│   ├── pay_type.php       # 支付方式管理页面
│   ├── pay_plugin.php     # 支付插件管理页面
│   ├── pay_weixin.php     # 微信支付配置页面
│   ├── pay_roll.php       # 支付轮询配置页面
│   ├── group.php          # 商户组管理页面
│   ├── glist.php          # 商户组列表页面
│   ├── domain.php         # 域名管理页面
│   ├── gonggao.php        # 公告管理页面
│   ├── log.php            # 日志查看页面
│   ├── risk.php           # 风控管理页面
│   ├── record.php         # 操作记录页面
│   ├── transfer.php       # 转账/代付页面
│   ├── transfer_batch.php # 批量转账页面
│   ├── code.php           # 验证码页面
│   ├── clean.php          # 数据清理页面
│   ├── download.php       # 文件下载页面
│   ├── export.php         # 数据导出页面
│   ├── testsubmit.php     # 支付测试提交页面
│   ├── sso.php            # 单点登录处理
│   ├── head.php           # 管理后台头部公共文件
│   ├── ajax.php           # 管理后台 AJAX 请求处理
│   ├── ajax_order.php     # 订单相关 AJAX 处理
│   ├── ajax_user.php      # 商户相关 AJAX 处理
│   ├── ajax_settle.php    # 结算相关 AJAX 处理
│   └── ajax_pay.php       # 支付配置相关 AJAX 处理
│
├── paypage/               # 聚合收款码页面目录
│   ├── index.php          # 收款码主页
│   ├── inc.php            # 收款码公共引入文件
│   ├── ajax.php           # 收款码 AJAX 请求处理
│   ├── success.php        # 支付成功页面
│   ├── error.php          # 支付失败页面
│   ├── js/                # 收款码 JS 脚本
│   │   ├── common.js      # 公共脚本
│   │   ├── pay.js         # 支付脚本
│   │   ├── close.js       # 关闭脚本
│   │   └── hammer.js      # Hammer.js 手势库
│   ├── css/               # 收款码样式
│   │   ├── default.css    # 默认样式
│   │   └── style.css      # 主样式
│   └── images/            # 收款码图片资源
│
├── assets/                # 全局静态资源目录
│   ├── css/               # 全局样式文件
│   │   ├── bootstrap.min.css   # Bootstrap 框架样式
│   │   ├── bootstrap-table.css # Bootstrap Table 样式
│   │   ├── reset.css           # 样式重置
│   │   ├── main12.css          # 收银台主样式
│   │   ├── wechat_pay.css      # 微信支付页样式
│   │   ├── alipay_pay.css      # 支付宝支付页样式
│   │   ├── mqq_pay.css         # QQ 钱包支付页样式
│   │   ├── jd_pay.css          # 京东支付页样式
│   │   ├── bank_pay.css        # 银行支付页样式
│   │   └── datepicker.css      # 日期选择器样式
│   ├── js/                # 全局脚本文件
│   │   └── custom.js           # 自定义脚本
│   ├── img/               # 全局图片资源
│   │   ├── logo.png            # 站点 Logo
│   │   ├── loading.gif         # 加载动画
│   │   ├── wx.png / wxwappay.png  # 微信支付图标
│   │   ├── alipay.gif          # 支付宝图标
│   │   ├── qqpay.jpg           # QQ 钱包图标
│   │   └── ...                 # 其他图片资源
│   ├── icon/              # 支付方式图标
│   │   ├── wxpay.ico           # 微信支付图标
│   │   ├── alipay.ico          # 支付宝图标
│   │   ├── qqpay.ico           # QQ 钱包图标
│   │   ├── jdpay.ico           # 京东支付图标
│   │   ├── bank.ico            # 银行支付图标
│   │   ├── paypal.ico          # PayPal 图标
│   │   ├── tenpay.ico          # 财付通图标
│   │   └── wechat.ico          # 微信图标
│   ├── font/              # 字体文件
│   │   └── elephant.ttf        # 验证码字体
│   ├── files/             # 下载文件
│   │   ├── SDK.zip             # SDK 开发包
│   │   └── SDK_old.zip         # 旧版 SDK
│   └── codepay/           # 聚合收款码资源
│       ├── js/codepay_util.js  # 收款码工具脚本
│       ├── css/                # 收款码样式
│       └── img/                # 收款码图片
│
├── install/               # 安装程序目录
│   ├── index.php          # 安装向导页面
│   ├── update.php         # 数据库升级脚本页面
│   ├── install.sql        # 完整安装 SQL 脚本
│   ├── update.sql         # 数据库升级 SQL 脚本
│   ├── update2.sql        # 数据库升级 SQL 脚本（补充）
│   └── install.lock       # 安装锁定文件（存在则禁止重新安装）
│
├── images/                # 首页模板图片资源
├── css/                   # 首页模板样式资源
├── js/                    # 首页模板脚本资源
└── fonts/                 # 首页模板字体资源
```

## 1.3 请求生命周期

从用户发起 HTTP 请求到系统返回响应，完整的请求生命周期如下：

### 第一步：用户发起 HTTP 请求

用户通过浏览器或支付回调向系统发起请求，请求可能指向以下几类 URL：
- 页面请求：如 `/doc.html`、`/about.html` 等
- 支付提交：如 `/submit.php?pid=xxx&type=wxpay&...`
- 支付回调：如 `/pay/wxpay/notify`
- API 请求：如 `/api.php?act=order&pid=xxx&...`

### 第二步：Web 服务器 URL 重写

Web 服务器根据重写规则将友好 URL 映射到实际的 PHP 入口文件：

- `/{name}.html` → `index.php?mod={name}`（页面请求路由到首页入口）
- `/pay/{action}` → `pay.php?s={action}`（支付回调路由到支付入口）

对于直接访问 `.php` 文件的请求（如 `submit.php`、`api.php`），不经过重写，直接由 PHP 处理。

### 第三步：PHP 入口文件加载 common.php

所有入口文件在处理业务逻辑前，首先加载 `includes/common.php` 公共引导文件。部分入口文件会在加载前设置特殊标志：

```php
// submit.php 中设置安全防护和禁用 Session
$is_defend = true;
$nosession = true;

// pay.php、cron.php、api.php 等禁用 Session
$nosession = true;
```

### 第四步：common.php 初始化流程

`common.php` 是整个系统的初始化核心，按以下顺序执行：

1. **错误报告设置**：设置 `error_reporting(E_ERROR | E_PARSE | E_COMPILE_ERROR)`，仅显示严重错误
2. **常量定义**：定义 `VERSION`（版本号 `3045`）、`DB_VERSION`（数据库版本 `2024`）、`IN_CRONLITE`（防重复引入标志）、`SYSTEM_ROOT`、`ROOT`、`PAYPAGE_ROOT`、`TEMPLATE_ROOT`、`PLUGIN_ROOT` 等路径常量
3. **时区设置**：`date_default_timezone_set('Asia/Shanghai')`
4. **Session 启动**：若未设置 `$nosession` 标志，则启动 `session_start()`
5. **HTTPS 检测**：通过 `is_https()` 函数判断当前协议，构建站点 URL `$siteurl`
6. **安全扫描**：检测 360 网站卫士（当前已注释）
7. **自动加载注册**：加载 `autoloader.php` 并调用 `Autoloader::register()`，注册 PSR-0 风格的类自动加载机制
8. **腾讯云防护**：若 `$is_defend` 为 true，加载 `txprotect.php` 进行反爬虫和恶意请求过滤
9. **数据库配置加载**：`require ROOT.'config.php'`，获取数据库连接参数，定义表前缀常量 `DBQZ`
10. **安装检测**：检查数据库配置是否完整，检查 `pre_config` 表是否存在，未安装则引导至安装页面
11. **数据库连接**：`$DB = new \lib\PdoHelper($dbconfig)`，创建全局数据库操作对象
12. **缓存加载**：`$CACHE = new \lib\Cache()`，`$conf = $CACHE->pre_fetch()`，从 `pre_cache` 表加载系统配置到全局变量 `$conf`，同时定义 `SYS_KEY` 常量
13. **版本检测**：比较 `$conf['version']` 与 `DB_VERSION`，版本不一致则引导升级
14. **函数加载**：`include_once(SYSTEM_ROOT."functions.php")`，加载全局工具函数
15. **会员检测**：`include_once(SYSTEM_ROOT."member.php")`，解析 Cookie 中的 `admin_token`/`user_token`，验证管理员或商户登录状态
16. **安装锁检测**：检查 `install/install.lock` 文件是否存在，防止恶意重装
17. **CDN 配置**：根据 `$conf['cdnpublic']` 选择公共 CDN 源（宝塔/BootCDN/字节/StaticFile）

### 第五步：业务逻辑处理

初始化完成后，各入口文件执行自身业务逻辑：

- **index.php**：根据 `mod` 参数通过 `Template::load()` 加载对应模板文件并 include
- **submit.php**：验证商户签名 → 创建订单 → 匹配支付通道（`Channel::submit`）→ 加载支付插件（`Plugin::loadForSubmit`）→ 输出支付页面（`Payment::echoDefault`）
- **pay.php**：根据 `s` 参数加载对应支付插件（`Plugin::loadForPay`）处理支付回调
- **cashier.php**：查询订单信息，获取可用支付方式列表（`Channel::getTypes`），渲染收银台页面
- **cron.php**：根据 `do` 参数执行结算生成/订单清理/通知重试等定时任务
- **api.php**：根据 `act` 参数执行订单查询/结算查询/退款等 API 操作

### 第六步：响应输出

根据业务处理结果，系统以不同方式输出响应：

- **HTML 页面**：直接输出 HTML 内容（如收银台、支付跳转页）
- **模板渲染**：通过 `Template::load()` 加载模板文件并 include 渲染
- **支付页面**：通过 `Payment::echoDefault()` 根据结果类型渲染跳转/扫码/表单等页面
- **JSON 响应**：API 接口返回 JSON 格式数据
- **脚本跳转**：支付提交后通过 JavaScript `window.location.replace()` 跳转到支付渠道

## 1.4 URL 路由机制

系统通过 Web 服务器的 URL 重写规则实现友好的 URL 路由，支持 Apache、Nginx、IIS 三种 Web 服务器。

### 路由规则总览

系统定义了两条核心路由规则：

| 规则 | URL 模式 | 映射目标 | 说明 |
|-----|---------|---------|------|
| 规则一 | `/{name}.html` | `index.php?mod={name}` | 页面路由，将 `.html` 后缀的 URL 映射到首页入口的 `mod` 参数 |
| 规则二 | `/pay/{action}` | `pay.php?s={action}` | 支付回调路由，将 `/pay/` 前缀的 URL 映射到支付入口的 `s` 参数 |

**规则一详解**：`{name}` 匹配由字母、数字、连字符（`-`）和下划线（`_`）组成的字符串。例如 `/doc.html` 映射为 `index.php?mod=doc`，`index.php` 通过 `Template::load('doc')` 加载对应模板文件。仅当请求的文件或目录不存在时才触发重写。

**规则二详解**：`{action}` 匹配任意非空路径。例如 `/pay/wxpay/notify` 映射为 `pay.php?s=wxpay/notify`，`pay.php` 通过 `Plugin::loadForPay('wxpay/notify')` 加载微信支付插件的回调处理。

### Apache 配置（.htaccess）

```apache
<IfModule mod_rewrite.c>
  Options +FollowSymlinks
  RewriteEngine On

  RewriteCond %{REQUEST_FILENAME} !-d
  RewriteCond %{REQUEST_FILENAME} !-f
  RewriteRule ^(.[a-zA-Z0-9\-\_]+).html$ index.php?mod=$1 [QSA,PT,L]
  RewriteRule ^pay/(.*)$ pay.php?s=$1 [QSA,PT,L]
</IfModule>
```

- **规则一**：带 `RewriteCond` 条件判断，仅当请求的文件不存在（`!-f`）且目录不存在（`!-d`）时才重写
- **规则二**：无条件重写，所有 `/pay/` 前缀的请求直接路由到 `pay.php`
- **标志说明**：`QSA`（追加查询字符串）、`PT`（传递给下一个处理器）、`L`（停止后续规则处理）

### Nginx 配置（nginx.txt）

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

- **规则一**：在 `if (!-e $request_filename)` 条件内，仅当请求文件不存在时重写，使用 `last` 标志停止后续 rewrite 处理
- **规则二**：无条件重写 `/pay/` 前缀请求
- **安全限制**：通过 `location ^~` 禁止直接访问 `/plugins/` 和 `/includes/` 目录，防止敏感文件泄露
- **注意**：Nginx 配置额外增加了目录访问限制，这是 Apache 和 IIS 配置中未显式体现的安全措施

### IIS 配置（IIS.txt / web.config）

```xml
<rule name="payrule1_rewrite" stopProcessing="true">
    <match url="^(.[a-zA-Z0-9-_]+).html"/>
    <conditions logicalGrouping="MatchAll">
        <add input="{REQUEST_FILENAME}" matchType="IsFile" negate="true"/>
        <add input="{REQUEST_FILENAME}" matchType="IsDirectory" negate="true"/>
    </conditions>
    <action type="Rewrite" url="index.php?mod={R:1}"/>
</rule>
<rule name="payrule2_rewrite" stopProcessing="true">
    <match url="^pay/(.*)"/>
    <conditions logicalGrouping="MatchAll">
        <add input="{REQUEST_FILENAME}" matchType="IsFile" negate="true"/>
        <add input="{REQUEST_FILENAME}" matchType="IsDirectory" negate="true"/>
    </conditions>
    <action type="Rewrite" url="pay.php?s={R:1}"/>
</rule>
```

- **规则一**（`payrule1_rewrite`）：匹配 `.html` 后缀 URL，条件为请求的文件和目录均不存在（`negate="true"`），重写到 `index.php?mod={R:1}`
- **规则二**（`payrule2_rewrite`）：匹配 `/pay/` 前缀 URL，同样带文件/目录不存在的条件判断，重写到 `pay.php?s={R:1}`
- **与 Apache/Nginx 的差异**：IIS 的规则二额外增加了文件/目录存在性检查条件，而 Apache 和 Nginx 的规则二是无条件重写的

### 路由处理流程

```
用户请求 URL
    │
    ├── 匹配 /{name}.html 模式？
    │       ├── 是 → 文件/目录不存在？ → 是 → index.php?mod={name}
    │       │                            → 否 → 返回静态文件
    │       └── 否 ↓
    │
    ├── 匹配 /pay/{action} 模式？
    │       ├── 是 → pay.php?s={action}
    │       └── 否 ↓
    │
    └── 直接访问 .php 文件？
            ├── 是 → 直接执行对应 PHP 文件
            └── 否 → 404 Not Found
```

在 `index.php` 中，`mod` 参数通过 `Template::load($mod)` 方法查找模板文件：优先查找当前主题目录下的 `{mod}.php`，若不存在则回退到 `default/{mod}.php`。在 `pay.php` 中，`s` 参数通过 `Plugin::loadForPay($s)` 方法解析插件名称和操作，加载对应插件的回调处理逻辑。
