# 聚合易支付项目对接文档

本文档是聚合易支付项目的完整对接指南，涵盖项目架构、技术栈、核心功能模块、API接口规范、数据库设计、环境配置、开发规范、扩展点说明及常见问题解决方案，旨在帮助开发者快速理解系统设计并完成对接集成。

## 目录

- [一、项目架构概述](#一项目架构概述)
  - [1.1 系统整体架构](#11-系统整体架构)
  - [1.2 目录结构说明](#12-目录结构说明)
  - [1.3 请求生命周期](#13-请求生命周期)
  - [1.4 URL路由机制](#14-url路由机制)
- [二、技术栈说明](#二技术栈说明)
  - [2.1 服务端技术栈](#21-服务端技术栈)
  - [2.2 前端技术栈](#22-前端技术栈)
  - [2.3 第三方SDK与集成服务](#23-第三方sdk与集成服务)
  - [2.4 PHP扩展依赖](#24-php扩展依赖)
- [三、核心功能模块解析](#三核心功能模块解析)
  - [3.1 支付流程模块](#31-支付流程模块)
  - [3.2 通道管理模块](#32-通道管理模块)
  - [3.3 插件系统模块](#33-插件系统模块)
  - [3.4 订单处理模块](#34-订单处理模块)
  - [3.5 用户系统模块](#35-用户系统模块)
  - [3.6 管理后台模块](#36-管理后台模块)
  - [3.7 聚合收款码模块](#37-聚合收款码模块)
- [四、API接口规范](#四api接口规范)
  - [4.1 支付提交接口](#41-支付提交接口)
  - [4.2 MAPI接口](#42-mapi接口)
  - [4.3 异步通知接口](#43-异步通知接口)
  - [4.4 同步回调接口](#44-同步回调接口)
  - [4.5 订单查询接口](#45-订单查询接口)
  - [4.6 签名规则详解](#46-签名规则详解)
- [五、数据库结构设计](#五数据库结构设计)
  - [5.1 系统配置相关表](#51-系统配置相关表)
  - [5.2 支付相关表](#52-支付相关表)
  - [5.3 订单相关表](#53-订单相关表)
  - [5.4 用户相关表](#54-用户相关表)
  - [5.5 资金相关表](#55-资金相关表)
  - [5.6 安全相关表](#56-安全相关表)
  - [5.7 其他表](#57-其他表)
  - [5.8 表间关联关系](#58-表间关联关系)
  - [5.9 数据库设计总结](#59-数据库设计总结)
- [六、环境配置指南](#六环境配置指南)
  - [6.1 服务器环境要求](#61-服务器环境要求)
  - [6.2 安装流程](#62-安装流程)
  - [6.3 Nginx配置](#63-nginx配置)
  - [6.4 Apache配置](#64-apache配置)
  - [6.5 IIS配置](#65-iis配置)
  - [6.6 计划任务配置](#66-计划任务配置)
  - [6.7 CDN配置](#67-cdn配置)
- [七、开发规范与最佳实践](#七开发规范与最佳实践)
  - [7.1 代码风格规范](#71-代码风格规范)
  - [7.2 安全规范](#72-安全规范)
  - [7.3 插件开发规范](#73-插件开发规范)
  - [7.4 模板开发规范](#74-模板开发规范)
- [八、功能模块扩展点说明](#八功能模块扩展点说明)
  - [8.1 支付插件扩展指南](#81-支付插件扩展指南)
  - [8.2 首页模板扩展指南](#82-首页模板扩展指南)
  - [8.3 支付方式扩展指南](#83-支付方式扩展指南)
  - [8.4 用户组扩展指南](#84-用户组扩展指南)
  - [8.5 实名认证扩展指南](#85-实名认证扩展指南)
  - [8.6 转账通道扩展指南](#86-转账通道扩展指南)
- [九、常见问题解决方案](#九常见问题解决方案)
  - [9.1 支付相关问题](#91-支付相关问题)
  - [9.2 部署相关问题](#92-部署相关问题)
  - [9.3 安全相关问题](#93-安全相关问题)
  - [9.4 兼容性问题](#94-兼容性问题)

***

# 一、项目架构概述

## 1.1 系统整体架构

聚合易支付采用经典的三层架构设计，各层职责分明、耦合度低：

### 入口层

入口层由根目录下的多个 PHP 入口文件组成，每个文件承担不同的请求入口角色：

| 入口文件          | 职责说明                                     |
| ------------- | ---------------------------------------- |
| `index.php`   | 首页入口，根据 `mod` 参数加载对应模板页面，处理页面路由          |
| `submit.php`  | 支付提交入口，接收商户支付请求，验证签名、创建订单、匹配通道后跳转支付      |
| `submit2.php` | 收银台二次提交入口，从收银台选择支付方式后再次提交，获取通道并跳转支付      |
| `pay.php`     | 支付回调路由入口，根据 URL 中的 `s` 参数加载对应支付插件的回调处理逻辑 |
| `cashier.php` | 收银台页面，展示订单信息和可选支付方式列表，供用户选择支付方式          |
| `cron.php`    | 计划任务入口，处理自动结算生成、订单统计与清理、异步通知重试等定时任务      |
| `mapi.php`    | 移动端 API 入口，提供 JSON 格式的支付提交接口，适配移动端场景     |
| `api.php`     | 开放 API 入口，提供订单查询、结算查询、批量订单查询、退款等外部接口     |
| `getshop.php` | 订单状态查询入口，根据订单号返回支付结果和跳转地址                |
| `gold.php`    | 微信点金计划 iframe 页面，处理微信支付完成后的商户展示页面跳转      |

### 业务逻辑层

业务逻辑层位于 `includes/lib/` 目录下，以命名空间 `\lib\` 组织核心类库：

| 核心类             | 职责说明                                                                                                           |
| --------------- | -------------------------------------------------------------------------------------------------------------- |
| `PdoHelper`     | 数据库操作封装类，基于 PDO 实现，支持自动表前缀替换（`pre_` → 实际前缀），提供 `query`、`getRow`、`getColumn`、`getAll`、`exec` 等方法                |
| `Cache`         | 缓存管理类，基于数据库 `pre_cache` 表实现键值缓存，支持序列化存储，提供 `get`、`read`、`save`、`pre_fetch`、`update`、`clean` 等方法                |
| `Channel`       | 支付通道管理类，负责通道查询、通道匹配（`submit`/`submit2`）、费率计算、通道限额判断等核心路由逻辑                                                     |
| `Plugin`        | 支付插件管理类，负责插件的发现、加载和调用，提供 `getList`、`getConfig`、`loadForPay`、`loadForSubmit`、`refund` 等方法                       |
| `Payment`       | 支付结果输出类，根据插件返回的结果类型（`jump`/`html`/`json`/`page`/`qrcode`/`scheme`）渲染不同的响应页面                                    |
| `PayUtils`      | 支付工具类，提供签名相关功能，包括参数拼接（`createLinkstring`）、参数过滤（`paraFilter`）、参数排序（`argSort`）、MD5 签名（`md5Sign`）和验签（`md5Verify`） |
| `Template`      | 模板管理类，负责模板的发现和加载，优先加载当前主题模板，回退到 `default` 默认模板                                                                 |
| `Oauth`         | 快捷登录类，对接第三方 OAuth 登录服务，支持 QQ、微信等快捷登录方式                                                                         |
| `GeetestLib`    | 极验验证码类，集成极验行为式验证安全平台 SDK                                                                                       |
| `QC`            | QQ 互联 SDK 类，封装 QQ 登录 OAuth2.0 接口调用                                                                             |
| `hieroglyphy`   | 混淆编码类，用于对敏感信息进行 JavaScript 混淆编码输出                                                                              |
| `QcloudFaceid`  | 腾讯云人脸核身类，封装腾讯云 FaceID API，用于实名认证场景                                                                             |
| `AliyunCertify` | 阿里云实人认证类，封装阿里云安全认证 API，用于实名认证场景                                                                                |

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

| 规则  | URL 模式          | 映射目标                   | 说明                                       |
| --- | --------------- | ---------------------- | ---------------------------------------- |
| 规则一 | `/{name}.html`  | `index.php?mod={name}` | 页面路由，将 `.html` 后缀的 URL 映射到首页入口的 `mod` 参数 |
| 规则二 | `/pay/{action}` | `pay.php?s={action}`   | 支付回调路由，将 `/pay/` 前缀的 URL 映射到支付入口的 `s` 参数 |

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

***

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

| 库名            | 版本     | 使用场景                                      |
| ------------- | ------ | ----------------------------------------- |
| jQuery        | 1.12.4 | 支付页面（扫码支付、JSAPI 支付、H5 支付、收银台等）            |
| jQuery        | 2.1.4  | 管理后台（`admin688/head.php`）                 |
| jQuery        | 3.4.1  | 商户中心（`user/head.php`、登录/注册/OAuth 页面、首页模板） |
| Modernizr     | 2.8.3  | 管理后台浏览器特性检测                               |
| jquery.qrcode | 1.0    | 支付二维码前端生成（微信/支付宝/QQ/京东/银联扫码页面）            |
| html5shiv     | 3.7.3  | 管理后台 IE8 兼容（条件注释加载）                       |
| respond.js    | 1.4.2  | 管理后台 IE8 响应式兼容（条件注释加载）                    |

### CSS 框架与图标

| 库名                | 版本    | 使用场景       |
| ----------------- | ----- | ---------- |
| Bootstrap         | 3.3.7 | 商户中心 UI 框架 |
| Bootstrap         | 3.4.1 | 管理后台 UI 框架 |
| Font Awesome      | 4.7.0 | 全站图标字体     |
| Simple Line Icons | 2.4.1 | 商户中心辅助图标   |
| Animate.css       | 3.5.2 | CSS 动画效果   |

### 自定义资源

- `user/assets/css/font.css`：自定义字体样式
- `user/assets/css/app.css`：商户中心自定义样式
- `assets/css/bootstrap.min.css`：管理后台自定义 Bootstrap 覆盖样式
- `assets/css/bootstrap-table.css`：数据表格自定义样式

### CDN 支持

系统支持四种公共 CDN 源，通过后台配置项 `cdnpublic` 切换（定义于 `includes/common.php`）：

| 配置值      | CDN 源          | 地址                                             |
| -------- | -------------- | ---------------------------------------------- |
| 1        | 宝塔 CDN（宝塔魔贴）   | `//lib.baomitu.com/`                           |
| 2        | BootCDN        | `https://cdn.bootcdn.net/ajax/libs/`           |
| 4        | 字节 CDN         | `//lf26-cdn-tos.bytecdntp.com/cdn/expire-1-M/` |
| 其他（默认 3） | StaticFile CDN | `//cdn.staticfile.org/`                        |

管理后台（`admin688/head.php`）独立配置 `$admin_cdnpublic`，当前默认使用 StaticFile CDN。

## 2.3 第三方 SDK 与集成服务

### 支付渠道 SDK

| 服务               | 插件目录                | 功能说明                                                                                                                 |
| ---------------- | ------------------- | -------------------------------------------------------------------------------------------------------------------- |
| 支付宝（alipaysl）    | `plugins/alipaysl/` | 支付宝官方 SDK，支持当面付（扫码/条码）、手机网站支付、电脑网站支付、单笔转账到支付宝/银行卡、OAuth 授权登录、身份认证初始化与查询、商家授权等                                        |
| 支付宝（alipay）      | `plugins/alipay/`   | 支付宝完整 SDK，在 alipaysl 基础上增加实名认证（`AlipayCertifyService`）、证件验证（`AlipayCertdocService`）、安全风险（`AlipaySecurityService`）等服务 |
| 微信支付（wxpaysl）    | `plugins/wxpaysl/`  | 微信支付官方 SDK，支持 JSAPI 支付、Native 扫码支付、小程序支付、异步通知处理                                                                      |
| 微信支付（wxpay）      | `plugins/wxpay/`    | 微信支付 SDK，在 wxpaysl 基础上支持企业付款到零钱（`WxPayTransfer`）                                                                     |
| 微信支付 V3（wxpayn）  | `plugins/wxpayn/`   | 微信支付 V3 版本 SDK，支持商家转账到零钱（含用户姓名加密）                                                                                    |
| 微信支付 V3（wxpaynp） | `plugins/wxpaynp/`  | 微信支付 V3 版本 SDK（支付功能）                                                                                                 |
| QQ 钱包            | `plugins/qqpay/`    | QQ 钱包支付 SDK，支持扫码支付、企业付款（`qpayMchAPI`）                                                                                |
| 京东支付             | `plugins/jdpay/`    | 京东支付 SDK，含 RSA 签名、TDES 加密、XML 工具等                                                                                    |
| PayPal           | `plugins/paypal/`   | PayPal REST API SDK，支持国际支付                                                                                           |
| USDT             | `plugins/usdt/`     | USDT 加密货币支付插件                                                                                                        |

### 第三方聚合支付插件

| 插件名            | 目录                                       | 说明                  |
| -------------- | ---------------------------------------- | ------------------- |
| Jeepay         | `plugins/jeepay/`                        | 开源聚合支付平台，支持支付宝/微信转账 |
| SwiftPass（威富通） | `plugins/swiftpass2/`                    | 威富通聚合支付             |
| 多拉宝            | `plugins/duolabao/`                      | 多拉宝聚合支付             |
| Adapay         | `plugins/adapay/`                        | Adapay 聚合支付         |
| 讯虎支付           | `plugins/xunhupay/`、`plugins/xunhupay2/` | 讯虎支付（两个版本）          |
| PayJS          | `plugins/payjs/`                         | PayJS 微信支付          |
| 易码支付           | `plugins/epay/`                          | 易码支付接口              |
| 银联商务           | `plugins/chinaums/`                      | 银联商务（ChinaUMS）      |
| 掌易收            | `plugins/zhangyishou/`                   | 掌易收支付               |
| 开心支付           | `plugins/kayixin/`                       | 开心支付                |
| VMQ            | `plugins/vmq/`                           | V免签支付               |
| 青橙支付           | `plugins/qxapp/`                         | 青橙支付                |
| 爱支付            | `plugins/woaizf/`                        | 爱支付                 |
| 众语             | `plugins/zyu/`                           | 众语支付                |
| 米付             | `plugins/mirfupay/`                      | 米付支付                |
| XorPay         | `plugins/xorpay/`                        | XorPay 支付           |
| 云码支付           | `plugins/ympay/`                         | 云码支付                |
| 速通付            | `plugins/sytpay/`                        | 速通付支付               |
| 支付宝旧版          | `plugins/aliold/`                        | 支付宝 MD5 签名旧版接口      |

### 验证码服务

- **极验验证码（GeeTest）**：`includes/lib/GeetestLib.php`，SDK 版本 `php_3.0.0`，支持正常模式和宕机降级模式。当配置了 `captcha_id` 和 `private_key` 时使用自有极验服务，否则使用极验 Demo 服务。

### 邮件服务

通过 `send_mail()` 函数统一调用，根据配置项 `mail_cloud` 选择驱动：

| 配置值   | 驱动              | 实现类                                                                  |
| ----- | --------------- | -------------------------------------------------------------------- |
| 0（默认） | PHPMailer（SMTP） | `lib\mail\PHPMailer\PHPMailer`，支持 TLS/SSL 加密，端口自适应（587→TLS，465+→SSL） |
| 1     | SendCloud       | `lib\mail\Sendcloud`                                                 |
| 2     | 阿里云邮件推送         | `lib\mail\Aliyun`                                                    |

### 短信服务

通过 `send_sms()` 函数统一调用，根据配置项 `sms_api` 选择驱动，支持注册、登录、找回密码、修改信息等场景模板：

| 配置值 | 驱动      | 实现类                                           |
| --- | ------- | --------------------------------------------- |
| 1   | 腾讯云短信   | `lib\sms\TencentSms`                          |
| 2   | 阿里云短信   | `lib\sms\Aliyun`                              |
| 3   | 顶想云短信   | 通过 `https://api.topthink.com/sms/send` API 调用 |
| 其他  | 自定义短信接口 | 通过 `http://sms.php.gs/sms/send/yzm` API 调用    |

### 实名认证服务

系统支持四种实名认证方式（`show_cert_method()` 函数），覆盖个人和企业认证：

| 认证方式      | 实现说明                                                                                                                                                           |
| --------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| 手机号三要素认证  | 阿里云市场 API（`phone3.market.alicloudapi.com/phonethree`），验证姓名+身份证号+手机号一致性                                                                                         |
| 腾讯云人脸核身   | `includes/lib/QcloudFaceid.php`，调用 `faceid.tencentcloudapi.com`，支持 `GetRealNameAuthToken`（获取认证 Token）和 `GetRealNameAuthResult`（查询认证结果），使用 TC3-HMAC-SHA256 签名算法 |
| 阿里云金融实名认证 | `includes/lib/AliyunCertify.php`，调用 `saf.cn-shanghai.aliyuncs.com`，支持 `FACE_SDK` 方式的身份认证初始化和结果查询，使用 HMAC-SHA1 签名                                               |
| 支付宝快捷认证   | 通过 `plugins/alipay/` 的 `AlipayCertifyService` 实现，调用支付宝身份认证 API（初始化/查询）和证件验证 API                                                                                |
| 人工审核认证    | 后台人工审核方式                                                                                                                                                       |
| 企业实名认证    | 阿里云市场 API（`companythree.shumaidata.com/companythree/check`），验证企业名称+统一信用代码+法人姓名一致性                                                                              |

### 第三方登录

| 登录方式     | 实现类                                        | 说明                                                   |
| -------- | ------------------------------------------ | ---------------------------------------------------- |
| QQ 登录    | `includes/lib/QC.php`                      | QQ 互联 OAuth 2.0 SDK v2.0，支持获取授权码、Access Token、OpenID |
| 支付宝登录    | `plugins/alipaysl/` 的 `AlipayOauthService` | 支付宝 OAuth 授权，支持换取 Access Token 和获取用户信息               |
| 微信登录     | `includes/lib/Oauth.php` + 微信 OAuth API    | 通过聚合 OAuth 接口或直接调用微信 API 实现                          |
| 聚合 OAuth | `includes/lib/Oauth.php`                   | 通用第三方登录接口，支持通过统一 API 接入多种社交登录                        |

### QR 码相关

| 功能     | 实现方式                                     | 说明                          |
| ------ | ---------------------------------------- | --------------------------- |
| QR 码生成 | `jquery.qrcode` 1.0（前端）                  | 在支付页面通过 jQuery 插件在浏览器端生成二维码 |
| QR 码解码 | `includes/qrcodedecoder/`（Zxing PHP 移植版） | PHP 端二维码解码库，用于识别上传的二维码图片    |

### 微信公众号/小程序

- `includes/lib/wechat/MiniAppPay.php`：微信小程序支付
- `includes/lib/wechat/JsApiPay.php`：微信公众号 JSAPI 支付
- `wx_get_access_token()`：微信 Access Token 管理，带数据库缓存和行锁
- `wxa_generate_scheme()`：微信小程序 URL Scheme 生成

## 2.4 PHP 扩展依赖

| 扩展名        | 用途          | 使用位置                                                           |
| ---------- | ----------- | -------------------------------------------------------------- |
| pdo\_mysql | MySQL 数据库连接 | `PdoHelper` 类核心依赖，所有数据库操作                                      |
| curl       | HTTP 请求     | `curl_get()`、`get_curl()` 函数，支付 API 调用、短信/邮件服务、实名认证接口、极验验证码通信等 |
| gd         | 图像处理        | QR 码解码（`Zxing\GDLuminanceSource`）、验证码图片生成                      |
| mbstring   | 多字节字符串处理    | `mb_convert_encoding()` 用于 IP 归属地查询的 GB2312→UTF-8 编码转换         |
| json       | JSON 编解码    | 全局使用，API 响应解析、配置序列化、前后端数据交互                                    |
| openssl    | 加密与签名       | 支付宝 RSA 签名/验签、微信支付证书加密、HMAC-SHA256 签名、`authcode()` 加解密函数       |
| session    | 会话管理        | 用户登录状态、OAuth state、验证码会话等                                      |

***

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

1. `PayUtils::paraFilter()` 过滤掉空值和sign/sign\_type参数
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

| func值    | 说明           | 调用的插件方法        |
| -------- | ------------ | -------------- |
| `notify` | 异步通知回调       | `插件::notify()` |
| `return` | 同步跳转回调       | `插件::return()` |
| `alipay` | 支付宝支付（mapi）  | `插件::alipay()` |
| `wxpay`  | 微信支付（mapi）   | `插件::wxpay()`  |
| `qqpay`  | QQ钱包支付（mapi） | `插件::qqpay()`  |
| `bank`   | 云闪付支付（mapi）  | `插件::bank()`   |
| `jdpay`  | 京东支付（mapi）   | `插件::jdpay()`  |
| `submit` | 页面支付提交       | `插件::submit()` |

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

| 方法                                                | 说明          | 调用场景               |
| ------------------------------------------------- | ----------- | ------------------ |
| `submit($type, $gid, $money, $device)`            | 按支付方式名称分配通道 | submit.php API支付提交 |
| `submit2($typeid, $gid, $money)`                  | 按支付方式ID分配通道 | submit2.php 收银台支付  |
| `getSubmitInfo($typeid, $typename, $gid, $money)` | 核心通道分配逻辑    | 被submit/submit2调用  |
| `getTypes($gid)`                                  | 获取商户可用支付方式  | cashier.php 收银台    |
| `getChannelFromRoll($channel, $money)`            | 轮询组通道分配     | getSubmitInfo内部调用  |
| `get($id, $channelinfo)`                          | 获取通道详情      | 多处使用               |
| `info($id)`                                       | 获取通道简要信息    | paypage/index.php  |
| `getWeixin($id)`                                  | 获取微信公众号配置   | weixinOpenId()     |

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

| channel值 | 含义           | 处理方式                                 |
| -------- | ------------ | ------------------------------------ |
| `0`      | 关闭该支付方式      | 直接返回 `false`                         |
| `-1`     | 随机选择可用通道     | 查询 `pre_channel` 表中该支付方式的所有可用通道，随机选取 |
| 正数       | 指定通道ID或轮询组ID | 若type为"roll"则走轮询组逻辑，否则直接使用指定通道       |

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

| 方法                                                       | 说明                          |
| -------------------------------------------------------- | --------------------------- |
| `getList()`                                              | 扫描plugins目录获取所有插件名称列表       |
| `getConfig($name)`                                       | 获取插件的 `$info` 静态属性（元信息）     |
| `loadForSubmit($plugin, $trade_no, $ismapi)`             | 加载插件处理支付提交（调用submit或mapi方法） |
| `loadForPay($s)`                                         | 加载插件处理支付回调（解析URL后调用对应方法）    |
| `loadForJsapi($trade_no, $type, $money, $name, $openid)` | 加载插件处理JSAPI支付（调用jsapi方法）    |
| `refund($trade_no, $money, &$message)`                   | 调用插件的退款方法                   |
| `exists($name)`                                          | 检查插件文件是否存在                  |
| `isrefund($name)`                                        | 检查插件是否支持退款                  |
| `updateAll()`                                            | 更新插件数据库表（清空后重新注册所有插件）       |
| `get($name)`                                             | 从数据库获取插件信息                  |
| `getAll()`                                               | 从数据库获取所有插件信息                |

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

每个插件必须实现为一个PHP类，类名格式为 `{name}_plugin`，放置在 `plugins/{name}/{name}_plugin.php` 文件中。以 [epay\_plugin](file:///www/wwwroot/pay/plugins/epay/epay_plugin.php) 为示例：

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

| 方法         | 必须 | 说明      | 返回值格式                                                 |
| ---------- | -- | ------- | ----------------------------------------------------- |
| `submit()` | 是  | 页面支付提交  | `['type'=>'jump/html', 'url'=>'...'/'data'=>'...']`   |
| `mapi()`   | 否  | API支付提交 | 同submit，或按支付方式名分方法                                    |
| `notify()` | 是  | 异步通知处理  | `['type'=>'html', 'data'=>'success/fail']`            |
| `return()` | 是  | 同步回调处理  | `['type'=>'error/return', 'msg'=>'...'/'url'=>'...']` |
| `jsapi()`  | 否  | JSAPI支付 | 返回支付参数供前端调用                                           |
| `refund()` | 否  | 退款      | `['code'=>0, 'ret'=>1, 'msg'=>'success']`             |

**返回值type类型说明**

| type值    | 说明           | 附加字段                         |
| -------- | ------------ | ---------------------------- |
| `jump`   | 跳转到指定URL     | `url`                        |
| `html`   | 输出HTML内容     | `data`                       |
| `json`   | 输出JSON数据     | `data`                       |
| `page`   | 包含指定页面模板     | `page`（模板名）, `data`（模板变量）    |
| `qrcode` | 扫码支付页面       | `url`（二维码内容）, `page`（模板名）    |
| `scheme` | URL Scheme跳转 | `url`（scheme内容）, `page`（模板名） |
| `return` | 同步回调跳转       | `url`                        |
| `error`  | 错误提示         | `msg`                        |

### 3.3.4 已有插件清单

系统共包含33个支付插件，覆盖主流支付渠道：

| 插件名     | 目录名           | 说明                     |
| ------- | ------------- | ---------------------- |
| 支付宝官方   | `alipay`      | 支付宝官方接口，支持扫码、H5、JSAPI等 |
| 支付宝旧版   | `aliold`      | 支付宝旧版接口                |
| 支付宝服务商  | `alipaysl`    | 支付宝服务商模式接口             |
| 微信支付官方  | `wxpay`       | 微信支付V2接口，支持扫码、H5、JSAPI |
| 微信支付V3  | `wxpayn`      | 微信支付V3接口（商家转账）         |
| 微信支付V3+ | `wxpaynp`     | 微信支付V3增强版              |
| 微信支付服务商 | `wxpaysl`     | 微信支付服务商模式接口            |
| QQ钱包    | `qqpay`       | QQ钱包支付接口               |
| 彩虹易支付   | `epay`        | 彩虹易支付对接接口              |
| Jeepay  | `jeepay`      | Jeepay聚合支付平台接口         |
| PayJS   | `payjs`       | PayJS微信支付接口            |
| PayPal  | `paypal`      | PayPal国际支付接口           |
| 威富通     | `swiftpass`   | 威富通支付接口                |
| 威富通V2   | `swiftpass2`  | 威富通V2接口                |
| 汇付天下    | `adapay`      | 汇付天下Adapay接口           |
| 通联支付    | `allinpay`    | 通联支付接口                 |
| 银联商务    | `chinaums`    | 银联商务接口                 |
| 银联在线    | `unionpay`    | 银联在线支付接口               |
| 京东支付    | `jdpay`       | 京东支付接口                 |
| 多拉宝     | `duolabao`    | 多拉宝支付接口                |
| 易生支付    | `mirfupay`    | 易生支付接口                 |
| 开鑫支付    | `kayixin`     | 开鑫支付接口                 |
| 迅虎支付    | `xunhupay`    | 迅虎支付接口                 |
| 迅虎支付V2  | `xunhupay2`   | 迅虎支付V2接口               |
| XorPay  | `xorpay`      | XorPay支付接口             |
| 码支付     | `vmq`         | 码支付（V免签）接口             |
| USDT支付  | `usdt`        | USDT数字货币支付接口           |
| 易码支付    | `ympay`       | 易码支付接口                 |
| 银盛支付    | `ysepay`      | 银盛支付接口                 |
| 随通支付    | `sytpay`      | 随通支付接口                 |
| 我爱支付    | `woaizf`      | 我爱支付接口                 |
| 掌易付     | `zyu`         | 掌易付接口                  |
| 张一搜     | `zhangyishou` | 张一搜支付接口                |
| QXApp   | `qxapp`       | QXApp支付接口              |

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

| 重试次数       | 距上次通知的间隔       | 距首次通知的累计时间 |
| ---------- | -------------- | ---------- |
| 第1次（首次失败后） | 1分钟            | 1分钟        |
| 第2次        | 2分钟            | 3分钟        |
| 第3次        | 16分钟           | 19分钟       |
| 第4次        | 36分钟           | 55分钟       |
| 第5次        | 1小时            | 1小时55分钟    |
| 超过5次       | 标记 `notify=-1` | 不再重试       |

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

| 登录方式    | type值 | 说明               |
| ------- | ----- | ---------------- |
| 账号密码    | 1     | 使用邮箱或手机号 + 密码登录  |
| 商户密钥    | 0     | 使用商户ID + 密钥登录    |
| QQ快捷登录  | —     | 通过OAuth绑定QQ的uid  |
| 支付宝快捷登录 | —     | 通过OAuth绑定支付宝的uid |
| 微信扫码登录  | —     | 通过微信OpenID绑定     |

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

| 类型     | 函数                      | 说明              |
| ------ | ----------------------- | --------------- |
| 支付宝    | `transferToAlipay()`    | 支付宝单笔转账到账户/银行卡  |
| 微信V2   | `transferToWeixin()`    | 微信企业付款到零钱       |
| 微信V3   | `transferToWeixinNew()` | 微信商家转账到零钱（V3接口） |
| QQ钱包   | `transferToQQ()`        | QQ钱包企业付款        |
| 银行卡    | `transferToBank()`      | 支付宝转账到银行卡       |
| Jeepay | `transferJeepay()`      | 通过Jeepay平台转账    |

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

| 文件                   | 功能     | 说明                                            |
| -------------------- | ------ | --------------------------------------------- |
| **首页与概览**            | <br /> | <br />                                        |
| `index.php`          | 后台首页   | 显示订单总数、商户数量、总余额、结算总额、支付方式/通道收入统计              |
| **订单管理**             | <br /> | <br />                                        |
| `order.php`          | 订单列表   | 查看、搜索、管理所有支付订单                                |
| `ajax_order.php`     | 订单AJAX | 订单数据查询接口                                      |
| `export.php`         | 导出订单   | 导出订单数据为文件                                     |
| `download.php`       | 下载文件   | 下载导出的文件                                       |
| **结算管理**             | <br /> | <br />                                        |
| `slist.php`          | 结算列表   | 查看结算记录                                        |
| `settle.php`         | 结算处理   | 审核和处理结算申请                                     |
| `ajax_settle.php`    | 结算AJAX | 结算操作接口                                        |
| **商户管理**             | <br /> | <br />                                        |
| `ulist.php`          | 用户列表   | 查看、编辑、封禁商户                                    |
| `glist.php`          | 用户组设置  | 配置用户组的通道和费率                                   |
| `group.php`          | 用户组购买  | 设置可购买的用户组                                     |
| `record.php`         | 资金明细   | 查看商户资金变动记录                                    |
| `uset.php`           | 商户设置   | 修改商户信息                                        |
| `ustat.php`          | 支付统计   | 商户支付数据统计                                      |
| `domain.php`         | 授权域名   | 管理商户支付域名白名单                                   |
| `ajax_user.php`      | 商户AJAX | 商户操作接口                                        |
| **支付接口**             | <br /> | <br />                                        |
| `pay_channel.php`    | 支付通道   | 配置支付通道参数（appid/appkey/appsecret等）             |
| `pay_type.php`       | 支付方式   | 管理支付方式（支付宝/微信/QQ等）                            |
| `pay_plugin.php`     | 支付插件   | 查看和管理已安装的支付插件                                 |
| `pay_roll.php`       | 通道轮询   | 配置通道轮询组                                       |
| `pay_weixin.php`     | 公众号小程序 | 管理微信公众号和小程序配置                                 |
| `ajax_pay.php`       | 支付AJAX | 支付配置操作接口                                      |
| **系统设置**             | <br /> | <br />                                        |
| `set.php`            | 系统设置   | 多模块配置（网站信息/支付结算/企业付款/快捷登录/实名认证/邮箱短信/模板/计划任务等） |
| `gonggao.php`        | 公告配置   | 管理网站公告                                        |
| **其他功能**             | <br /> | <br />                                        |
| `transfer.php`       | 企业付款   | 手动发起企业付款                                      |
| `transfer_batch.php` | 批量付款   | 批量企业付款                                        |
| `risk.php`           | 风控记录   | 查看风控拦截记录                                      |
| `log.php`            | 登录日志   | 查看商户登录日志                                      |
| `clean.php`          | 数据清理   | 清理过期数据                                        |
| `testsubmit.php`     | 测试支付   | 测试支付流程                                        |
| **通用**               | <br /> | <br />                                        |
| `login.php`          | 管理员登录  | 管理员登录页面                                       |
| `head.php`           | 导航头部   | 后台导航菜单                                        |
| `ajax.php`           | 通用AJAX | 通用数据接口                                        |
| `code.php`           | 验证码    | 图形验证码生成                                       |
| `sso.php`            | SSO登录  | 单点登录                                          |

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

***

# 四、API接口规范

## 4.1 支付提交接口

### 4.1.1 接口地址

| 项目           | 说明                                         |
| ------------ | ------------------------------------------ |
| 请求URL        | `/submit.php`                              |
| 请求方式         | GET 或 POST                                 |
| Content-Type | `application/x-www-form-urlencoded`（POST时） |
| 字符编码         | UTF-8                                      |

### 4.1.2 请求参数

| 参数名            | 类型      | 必填 | 说明                                                                                            | <br />         |
| -------------- | ------- | -- | --------------------------------------------------------------------------------------------- | :------------- |
| pid            | int     | 是  | 商户ID，由平台分配                                                                                    | <br />         |
| type           | string  | 是  | 支付方式，可选值：`alipay`（支付宝）、`wxpay`（微信支付）、`qqpay`（QQ钱包）、`bank`（云闪付）、`jdpay`（京东支付）。如不传则跳转收银台页面由用户选择 | <br />         |
| out\_trade\_no | string  | 是  | 商户订单号，格式限制：\`\[a-zA-Z0-9.\_-                                                                  | ]+\`，同一商户下不可重复 |
| notify\_url    | string  | 是  | 异步通知地址，支付成功后系统向此地址发送通知                                                                        | <br />         |
| return\_url    | string  | 是  | 同步回调地址，用户支付完成后浏览器跳转至此地址                                                                       | <br />         |
| name           | string  | 是  | 商品名称，最长127字符（超长自动截断）                                                                          | <br />         |
| money          | decimal | 是  | 支付金额，必须大于0，支持小数（如 `1.50`）                                                                     | <br />         |
| sign           | string  | 是  | MD5签名，详见4.1.3签名算法                                                                             | <br />         |
| sign\_type     | string  | 是  | 签名类型，固定值：`MD5`                                                                                | <br />         |
| sitename       | string  | 否  | 站点名称                                                                                          | <br />         |
| param          | string  | 否  | 自定义参数，回调时原样返回                                                                                 | <br />         |

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

1. 过滤后参数（无需过滤，无sign/sign\_type/空值）：同上
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

| 错误信息                             | 触发条件                                     | <br /> |
| -------------------------------- | ---------------------------------------- | :----- |
| 你还未配置支付接口商户！                     | 请求中未包含 `pid` 参数（GET和POST均无）              | <br /> |
| PID不存在                           | `pid` 参数为空或为0                            | <br /> |
| 商户不存在！                           | 数据库中不存在该 `pid` 对应的商户                     | <br /> |
| 签名校验失败，请返回重试！                    | MD5签名验证不通过                               | <br /> |
| 商户已封禁，无法支付！                      | 商户状态 `status=0` 或支付权限 `pay=0`            | <br /> |
| 商户没通过审核，请联系官方客服进行审核              | 商户 `pay=2` 且系统开启审核模式 `user_review=1`     | <br /> |
| 订单号(out\_trade\_no)不能为空          | `out_trade_no` 参数为空                      | <br /> |
| 通知地址(notify\_url)不能为空            | `notify_url` 参数为空                        | <br /> |
| 回调地址(return\_url)不能为空            | `return_url` 参数为空                        | <br /> |
| 商品名称(name)不能为空                   | `name` 参数为空                              | <br /> |
| 金额(money)不能为空                    | `money` 参数为空                             | <br /> |
| 金额不合法                            | `money` ≤ 0，或非数字，或格式不匹配 `/^[0-9.]+$/`    | <br /> |
| 最大支付金额是{X}元                      | 系统配置了最大支付金额限制且超出                         | <br /> |
| 最小支付金额是{X}元                      | 系统配置了最小支付金额限制且不足                         | <br /> |
| 订单号(out\_trade\_no)格式不正确         | `out_trade_no` 不匹配 \`/^\[a-zA-Z0-9.\_-   | ]+$/\` |
| 当前商户未完成实名认证，无法收款                 | 系统强制实名认证 `cert_force=1` 且商户未认证           | <br /> |
| 当前商户未填写联系QQ，无法收款                 | 系统强制填写QQ `forceqq=1` 且商户QQ为空             | <br /> |
| 该域名不可发起支付，原因：域名没过白，请前往支付平台授权支付域名 | 系统开启域名白名单 `pay_domain_forbid=1` 且通知域名未授权 | <br /> |
| 该商品禁止出售                          | 商品名称命中系统屏蔽词（或自定义屏蔽提示）                    | <br /> |
| 系统异常无法完成付款                       | 请求IP在系统IP黑名单中                            | <br /> |
| 该订单({X})已完成支付，请勿重复发起支付           | 同一商户同一订单号已支付                             | <br /> |
| 该订单({X})支付参数有变化，请更换订单号重新发起支付     | 同一商户同一订单号未支付但参数有变化                       | <br /> |
| 创建订单失败，请返回重试！                    | 数据库插入订单记录失败                              | <br /> |
| 当前支付方式单笔最小限额为{X}元，请选择其他支付方式！     | 支付金额低于通道单笔最小限额                           | <br /> |
| 当前支付方式单笔最大限额为{X}元，请选择其他支付方式！     | 支付金额超过通道单笔最大限额                           | <br /> |
| 当前商户余额不足，无法完成支付，请商户登录用户中心充值余额    | 商户直清模式下商户余额不足                            | <br /> |
| 当前支付通道信息不存在                      | 支付通道配置异常                                 | <br /> |

***

## 4.2 MAPI接口

MAPI（Mobile API）接口为移动端/API场景设计，与 `submit.php` 的主要区别在于：MAPI接口以JSON格式返回支付链接信息，而非直接渲染HTML页面。商户获取支付链接后可自行决定如何展示和处理。

### 4.2.1 接口地址

| 项目           | 说明                                         |
| ------------ | ------------------------------------------ |
| 请求URL        | `/mapi.php`                                |
| 请求方式         | GET 或 POST                                 |
| Content-Type | `application/x-www-form-urlencoded`（POST时） |
| 字符编码         | UTF-8                                      |
| 响应格式         | JSON                                       |

### 4.2.2 请求参数

MAPI接口的请求参数与 `submit.php` 基本相同，但额外增加了以下参数：

| 参数名            | 类型      | 必填 | 说明                                                                                                                          |
| -------------- | ------- | -- | --------------------------------------------------------------------------------------------------------------------------- |
| pid            | int     | 是  | 商户ID                                                                                                                        |
| type           | string  | 是  | 支付方式，**MAPI接口中type为必填**，不可为空                                                                                                |
| out\_trade\_no | string  | 是  | 商户订单号                                                                                                                       |
| notify\_url    | string  | 是  | 异步通知地址                                                                                                                      |
| return\_url    | string  | 否  | 同步回调地址（MAPI场景下可为空）                                                                                                          |
| name           | string  | 是  | 商品名称                                                                                                                        |
| money          | decimal | 是  | 支付金额                                                                                                                        |
| sign           | string  | 是  | MD5签名                                                                                                                       |
| sign\_type     | string  | 是  | 固定值：`MD5`                                                                                                                   |
| sitename       | string  | 否  | 站点名称                                                                                                                        |
| param          | string  | 否  | 自定义参数                                                                                                                       |
| clientip       | string  | 是  | 用户IP地址，**MAPI接口中为必填**                                                                                                       |
| device         | string  | 否  | 设备类型，可选值：`pc`（默认）、`mobile`、`qq`、`wechat`、`alipay`。当传入 `qq`/`wechat`/`alipay` 时，系统会将其映射为 `mobile` 设备，并记录原始设备类型到 `mdevice` 变量 |

**与** **`submit.php`** **的差异：**

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

| 字段        | 类型     | 说明                                           |
| --------- | ------ | -------------------------------------------- |
| code      | int    | 返回码。`1` 表示成功，`-2` 表示支付通道返回错误，`-4` 表示未传入商户ID  |
| trade\_no | string | 系统订单号，支付成功时返回                                |
| payurl    | string | 支付跳转URL，当支付方式为 `jump` 类型时返回                  |
| qrcode    | string | 扫码支付URL，当支付方式为 `qrcode` 类型时返回                |
| urlscheme | string | URL Scheme链接，当支付方式为 `scheme` 类型时返回（如微信小程序跳转） |
| msg       | string | 错误信息，仅在 `code=-2` 时返回                        |

**返回字段与支付方式对应关系：**

| 支付插件返回类型       | 返回字段         | 说明                         |
| -------------- | ------------ | -------------------------- |
| jump           | payurl       | 直接跳转的支付链接，适用于H5支付等场景       |
| qrcode         | qrcode       | 二维码内容URL，商户需自行生成二维码展示给用户   |
| scheme         | urlscheme    | URL Scheme链接，适用于微信小程序等唤起支付 |
| error          | code=-2, msg | 支付通道返回错误                   |
| 其他（插件无mapi方法时） | payurl       | 降级为跳转到系统内嵌支付页面             |

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

| 错误信息                             | code | 触发条件                           |
| -------------------------------- | ---- | ------------------------------ |
| 商户ID不能为空                         | -4   | 请求中未包含 `pid` 参数                |
| PID不存在                           | -1   | `pid` 参数为空或为0                  |
| 商户不存在！                           | -1   | 数据库中不存在该商户                     |
| 签名校验失败，请返回重试！                    | -1   | MD5签名验证不通过                     |
| 商户已封禁，无法支付！                      | -1   | 商户被封禁                          |
| 商户没通过审核，请联系官方客服进行审核              | -1   | 商户未通过审核                        |
| 订单号(out\_trade\_no)不能为空          | -1   | `out_trade_no` 为空              |
| 通知地址(notify\_url)不能为空            | -1   | `notify_url` 为空                |
| 商品名称(name)不能为空                   | -1   | `name` 为空                      |
| 金额(money)不能为空                    | -1   | `money` 为空                     |
| 支付方式(type)不能为空                   | -1   | `type` 为空（MAPI中type必填）         |
| 用户IP地址(clientip)不能为空             | -1   | `clientip` 为空（MAPI中clientip必填） |
| 金额不合法                            | -1   | 金额格式错误                         |
| 最大支付金额是{X}元                      | -1   | 超出系统最大金额限制                     |
| 最小支付金额是{X}元                      | -1   | 低于系统最小金额限制                     |
| 订单号(out\_trade\_no)格式不正确         | -1   | 订单号格式不合法                       |
| 当前商户未完成实名认证，无法收款                 | -1   | 需实名认证                          |
| 当前商户未填写联系QQ，无法收款                 | -1   | 需填写QQ                          |
| 该域名不可发起支付，原因：域名没过白，请前往支付平台授权支付域名 | -1   | 域名未授权                          |
| 该商品禁止出售                          | -1   | 商品名命中屏蔽词                       |
| 系统异常无法完成付款                       | -1   | IP在黑名单中                        |
| 该订单({X})已完成支付，请勿重复发起支付           | -1   | 订单已支付                          |
| 该订单({X})支付参数有变化，请更换订单号重新发起支付     | -1   | 订单参数变化                         |
| 创建订单失败，请返回重试！                    | -1   | 数据库插入失败                        |
| 当前支付方式单笔最小限额为{X}元，请选择其他支付方式！     | -1   | 低于通道限额                         |
| 当前支付方式单笔最大限额为{X}元，请选择其他支付方式！     | -1   | 超出通道限额                         |
| 当前商户余额不足，无法完成支付，请商户登录用户中心充值余额    | -1   | 商户余额不足                         |

***

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

| 参数名            | 类型     | 说明                                             |
| -------------- | ------ | ---------------------------------------------- |
| pid            | int    | 商户ID                                           |
| trade\_no      | string | 系统订单号                                          |
| out\_trade\_no | string | 商户订单号                                          |
| type           | string | 支付方式（如 `alipay`、`wxpay` 等）                     |
| name           | string | 商品名称。若系统配置 `notifyordername=1`，则固定返回 `product` |
| money          | float  | 支付金额（浮点数类型，如 `1.5`）                            |
| trade\_status  | string | 交易状态，固定值：`TRADE_SUCCESS`                       |
| param          | string | 自定义参数（仅当商户提交时传入了 `param` 才会返回）                 |
| sign           | string | MD5签名                                          |
| sign\_type     | string | 签名类型，固定值：`MD5`                                 |

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

| 重试次数 | 距首次通知的时间间隔 | 说明           |
| ---- | ---------- | ------------ |
| 第1次  | 1分钟        | 首次通知失败后1分钟重试 |
| 第2次  | 3分钟        | 距首次通知约3分钟    |
| 第3次  | 约20分钟      | 距首次通知约20分钟   |
| 第4次  | 约1小时       | 距首次通知约1小时    |
| 第5次  | 约2小时       | 距首次通知约2小时    |

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

***

## 4.4 同步回调接口

### 4.4.1 回调触发

用户支付完成后，系统通过浏览器跳转至商户在支付请求中提供的 `return_url`。同步回调的参数格式与异步通知相同，参数以URL查询字符串形式附加。

**跳转逻辑：**

- 支付完成后5分钟内：跳转至带签名参数的 `return_url`
- 支付完成后超过5分钟：跳转至系统支付成功页面（`/payok.html`）
- 订单状态为退款/异常（`status=2`）：跳转至支付失败页面（`/payerr.html`）

### 4.4.2 回调参数

同步回调的参数与异步通知参数完全一致：

| 参数名            | 类型     | 说明                                             |
| -------------- | ------ | ---------------------------------------------- |
| pid            | int    | 商户ID                                           |
| trade\_no      | string | 系统订单号                                          |
| out\_trade\_no | string | 商户订单号                                          |
| type           | string | 支付方式                                           |
| name           | string | 商品名称。若系统配置 `notifyordername=1`，则固定返回 `product` |
| money          | float  | 支付金额                                           |
| trade\_status  | string | 交易状态：`TRADE_SUCCESS`                           |
| param          | string | 自定义参数（如有）                                      |
| sign           | string | MD5签名                                          |
| sign\_type     | string | 签名类型：`MD5`                                     |

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

***

## 4.5 订单查询接口

### 4.5.1 支付状态查询（getshop）

#### 接口地址

| 项目    | 说明                |
| ----- | ----------------- |
| 请求URL | `/getshop.php`    |
| 请求方式  | GET               |
| 参数    | `trade_no`（系统订单号） |

#### 请求参数

| 参数名       | 类型     | 必填 | 说明    |
| --------- | ------ | -- | ----- |
| trade\_no | string | 是  | 系统订单号 |

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

| 字段      | 类型     | 说明                                                                         |
| ------- | ------ | -------------------------------------------------------------------------- |
| code    | int    | `1` 表示订单已处理（含成功和异常），`-1` 表示未支付                                             |
| msg     | string | 状态描述                                                                       |
| backurl | string | 跳转地址。支付成功5分钟内为带签名的 `return_url`，超过5分钟为 `/payok.html`；订单异常时为 `/payerr.html` |

### 4.5.2 商户API订单查询（api）

#### 接口地址

| 项目    | 说明                   |
| ----- | -------------------- |
| 请求URL | `/api.php?act=order` |
| 请求方式  | GET                  |
| 认证方式  | pid + key 明文验证       |

#### 请求参数

| 参数名            | 类型     | 必填 | 说明                          |
| -------------- | ------ | -- | --------------------------- |
| act            | string | 是  | 固定值：`order`                 |
| pid            | int    | 是  | 商户ID                        |
| key            | string | 是  | 商户密钥                        |
| trade\_no      | string | 否  | 系统订单号（与 `out_trade_no` 二选一） |
| out\_trade\_no | string | 否  | 商户订单号（与 `trade_no` 二选一）     |

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

| 字段             | 类型     | 说明                                    |
| -------------- | ------ | ------------------------------------- |
| code           | int    | `1` 成功，`-1` 订单不存在，`-3` 认证失败，`-4` 参数缺失 |
| msg            | string | 状态描述                                  |
| trade\_no      | string | 系统订单号                                 |
| out\_trade\_no | string | 商户订单号                                 |
| type           | string | 支付方式名称（如 `alipay`、`wxpay`）            |
| pid            | int    | 商户ID                                  |
| addtime        | string | 订单创建时间                                |
| endtime        | string | 订单完成时间                                |
| name           | string | 商品名称                                  |
| money          | string | 订单金额                                  |
| param          | string | 自定义参数                                 |
| buyer          | string | 买家标识（如微信openid）                       |
| status         | int    | 订单状态：`0` 未支付，`1` 已支付，`2` 已退款/异常       |

### 4.5.3 商户API批量订单查询

#### 接口地址

| 项目    | 说明                    |
| ----- | --------------------- |
| 请求URL | `/api.php?act=orders` |
| 请求方式  | GET                   |
| 认证方式  | pid + key 明文验证        |

#### 请求参数

| 参数名    | 类型     | 必填 | 说明                             |
| ------ | ------ | -- | ------------------------------ |
| act    | string | 是  | 固定值：`orders`                   |
| pid    | int    | 是  | 商户ID                           |
| key    | string | 是  | 商户密钥                           |
| limit  | int    | 否  | 每页数量，默认10，最大50                 |
| offset | int    | 否  | 偏移量，默认0                        |
| status | int    | 否  | 订单状态筛选：`0` 未支付，`1` 已支付，`2` 已退款 |

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

| 项目    | 说明                   |
| ----- | -------------------- |
| 请求URL | `/api.php?act=query` |
| 请求方式  | GET                  |
| 认证方式  | pid + key 明文验证       |

#### 请求参数

| 参数名 | 类型     | 必填 | 说明          |
| --- | ------ | -- | ----------- |
| act | string | 是  | 固定值：`query` |
| pid | int    | 是  | 商户ID        |
| key | string | 是  | 商户密钥        |

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

| 项目    | 说明                    |
| ----- | --------------------- |
| 请求URL | `/api.php?act=settle` |
| 请求方式  | GET                   |
| 认证方式  | pid + key 明文验证        |

#### 请求参数

| 参数名    | 类型     | 必填 | 说明             |
| ------ | ------ | -- | -------------- |
| act    | string | 是  | 固定值：`settle`   |
| pid    | int    | 是  | 商户ID           |
| key    | string | 是  | 商户密钥           |
| limit  | int    | 否  | 每页数量，默认10，最大50 |
| offset | int    | 否  | 偏移量，默认0        |

### 4.5.6 订单退款接口

#### 接口地址

| 项目    | 说明                    |
| ----- | --------------------- |
| 请求URL | `/api.php?act=refund` |
| 请求方式  | POST                  |
| 认证方式  | pid + key 明文验证        |

#### 请求参数

| 参数名            | 类型      | 必填 | 说明                          |
| -------------- | ------- | -- | --------------------------- |
| act            | string  | 是  | 固定值：`refund`                |
| pid            | int     | 是  | 商户ID                        |
| key            | string  | 是  | 商户密钥                        |
| trade\_no      | string  | 否  | 系统订单号（与 `out_trade_no` 二选一） |
| out\_trade\_no | string  | 否  | 商户订单号（与 `trade_no` 二选一）     |
| money          | decimal | 是  | 退款金额，不能大于订单金额               |

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

***

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

***

# 五、数据库结构设计

> **说明**：所有表名中的 `pre_` 为表前缀占位符，实际表前缀由 [config.php](file:///www/wwwroot/pay/config.php) 中的 `dbqz` 配置决定（默认为 `pay_`）。例如 `pre_config` 实际对应 `pay_config`，`pre_order` 实际对应 `pay_order`，以此类推。
>
> 所有表均使用 InnoDB 引擎，字符集为 utf8。

***

## 5.1 系统配置相关表

### 5.1.1 pre\_config（系统配置表）

系统全局配置的键值对存储表，采用 KV 结构，所有配置项均以键值对形式存储。

| 字段名 | 类型          | 默认值  | 说明                 |
| --- | ----------- | ---- | ------------------ |
| k   | varchar(32) | -    | 配置键名（主键），唯一标识一个配置项 |
| v   | text        | NULL | 配置值，可为空            |

**已知配置项详细说明（从 install.sql 及代码中提取）：**

#### 基础配置

| 配置键           | 默认值                     | 说明                                                      |
| ------------- | ----------------------- | ------------------------------------------------------- |
| version       | 2024                    | 系统版本号，用于版本判断和升级检测                                       |
| admin\_user   | admin                   | 管理后台登录用户名                                               |
| admin\_pwd    | 123456                  | 管理后台登录密码（MD5 加密存储）                                      |
| admin\_paypwd | 123456                  | 管理后台支付/操作密码，用于敏感操作二次验证（MD5 加密存储）                        |
| sitename      | 聚合易支付                   | 站点名称，显示在页面标题和品牌位置                                       |
| title         | 聚合易支付 - 行业领先的免签约支付平台    | 站点标题（SEO title）                                         |
| keywords      | 聚合易支付,支付宝免签约即时到账,...    | 站点关键词（SEO keywords）                                     |
| description   | 聚合易支付是XX公司旗下的免签约支付产品... | 站点描述（SEO description）                                   |
| orgname       | XX公司                    | 运营主体公司名称，显示在页面底部和结算信息中                                  |
| kfqq          | 123456789               | 客服QQ号，显示在页面联系信息中                                        |
| template      | index11                 | 前台模板目录名称，对应 template 目录下的子目录                            |
| homepage      | 0                       | 首页显示模式，0=默认首页，1=自定义首页（frame 嵌入）                         |
| homepage\_url | （空）                     | 自定义首页URL，当 homepage=1 时以 frame 方式显示该URL                 |
| localurl      | （空）                     | 本站点URL地址，必须以 http\:// 或 https\:// 开头并以 / 结尾，填错会导致订单无法回调 |
| cdnpublic     | （空）                     | 静态资源CDN地址，用于加速前端资源加载                                    |
| syskey        | （空）                     | 系统密钥，用于加密签名等安全操作，在 common.php 中定义为常量 SYS\_KEY           |

#### 支付配置

| 配置键                 | 默认值  | 说明                                        |
| ------------------- | ---- | ----------------------------------------- |
| pay\_maxmoney       | 1000 | 最大支付金额（元），0 表示不限制，超过此金额的订单将被拒绝            |
| pay\_minmoney       | （空）  | 最小支付金额（元），低于此金额的订单将被拒绝                    |
| pay\_payaddstart    | （空）  | 订单金额随机增减起始阈值，订单满此金额后触发随机增减，留空不启用          |
| pay\_payaddmin      | （空）  | 随机增减最小金额，负数表示减少                           |
| pay\_payaddmax      | （空）  | 随机增减最大金额，负数表示减少                           |
| pay\_domain\_open   | 0    | 域名白名单开关，0=关闭，1=开启（开启后仅白名单域名可发起支付）         |
| pay\_domain\_forbid | 0    | 域名黑名单开关，0=关闭，1=开启（开启后黑名单域名禁止发起支付）         |
| localurl\_alipay    | （空）  | 支付宝专用跳转URL，适用于多域名场景下支付宝域名限制，留空使用当前网址      |
| localurl\_wxpay     | （空）  | 微信支付专用跳转URL，适用于多域名场景下微信公众号域名授权限制，留空使用当前网址 |

#### 结算配置

| 配置键              | 默认值 | 说明                                 |
| ---------------- | --- | ---------------------------------- |
| settle\_open     | 1   | 结算功能开关，0=关闭，1=开启                   |
| settle\_type     | 1   | 结算方式，1=自动结算（达到最低结算金额自动进入结算），0=手动结算 |
| settle\_money    | 30  | 最低结算金额（元），商户可用余额需达到此金额才能申请结算       |
| settle\_rate     | 0.5 | 结算手续费率（百分比），如 0.5 表示 0.5%          |
| settle\_fee\_min | 0.1 | 单笔结算最低手续费（元）                       |
| settle\_fee\_max | 20  | 单笔结算最高手续费（元）                       |
| settle\_alipay   | 1   | 支持支付宝结算，0=不支持，1=支持                 |
| settle\_wxpay    | 1   | 支持微信结算，0=不支持，1=支持                  |
| settle\_qqpay    | 1   | 支持QQ钱包结算，0=不支持，1=支持                |
| settle\_bank     | 0   | 支持银行卡结算，0=不支持，1=支持                 |

#### 转账配置

| 配置键              | 默认值       | 说明                   |
| ---------------- | --------- | -------------------- |
| transfer\_alipay | 0         | 支付宝自动转账开关，0=关闭，1=开启  |
| transfer\_wxpay  | 0         | 微信自动转账开关，0=关闭，1=开启   |
| transfer\_qqpay  | 0         | QQ钱包自动转账开关，0=关闭，1=开启 |
| transfer\_name   | 聚合易支付     | 自动转账付款方名称            |
| transfer\_desc   | 聚合易支付自动结算 | 自动转账备注描述             |

#### 登录配置

| 配置键                    | 默认值 | 说明                  |
| ---------------------- | --- | ------------------- |
| login\_qq              | 0   | QQ登录开关，0=关闭，1=开启    |
| login\_alipay          | 0   | 支付宝登录开关，0=关闭，1=开启   |
| login\_wx              | 0   | 微信登录开关，0=关闭，1=开启    |
| login\_alipay\_channel | 0   | 支付宝登录使用的支付通道ID，0=默认 |
| login\_wx\_channel     | 0   | 微信登录使用的支付通道ID，0=默认  |

#### 注册配置

| 配置键             | 默认值  | 说明                       |
| --------------- | ---- | ------------------------ |
| reg\_open       | 1    | 注册功能开关，0=关闭，1=开启         |
| reg\_pay        | 1    | 付费注册开关，0=免费注册，1=需支付费用后注册 |
| reg\_pay\_uid   | 1000 | 付费注册收款商户UID，注册费用将打入该商户账户 |
| reg\_pay\_price | 5    | 付费注册价格（元）                |

#### 验证配置

| 配置键           | 默认值 | 说明                              |
| ------------- | --- | ------------------------------- |
| verifytype    | 1   | 验证码类型，1=邮件验证                    |
| captcha\_open | 1   | 图形验证码开关，0=关闭，1=开启               |
| captcha\_id   | （空） | 验证码服务ID（如极验等第三方验证码平台）           |
| captcha\_key  | （空） | 验证码服务密钥                         |
| onecode       | 1   | 登录二次验证开关，0=关闭，1=开启（登录时需输入邮件验证码） |

#### 邮件短信

| 配置键         | 默认值         | 说明                           |
| ----------- | ----------- | ---------------------------- |
| mail\_cloud | 0           | 云邮件服务开关，0=使用SMTP发送，1=使用云邮件服务 |
| mail\_smtp  | smtp.qq.com | SMTP邮件服务器地址                  |
| mail\_port  | 465         | SMTP邮件服务器端口                  |
| mail\_name  | （空）         | SMTP登录用户名/邮箱地址               |
| mail\_pwd   | （空）         | SMTP登录密码/授权码                 |
| sms\_api    | 0           | 短信API开关，0=关闭，其他值为短信服务商标识     |

#### 实名认证

| 配置键            | 默认值 | 说明                                                                     |
| -------------- | --- | ---------------------------------------------------------------------- |
| cert\_open     | 0   | 实名认证方式，0=关闭，1=支付宝身份验证，2=手机号三要素实名认证，3=支付宝实名信息验证，4=微信扫码实名认证，5=阿里云金融级实人认证 |
| cert\_force    | 0   | 强制实名认证开关，0=关闭，1=开启（开启后商户必须实名认证才能正常使用支付接口收款）                            |
| cert\_appcode  | （空） | 实名认证API授权码（用于手机号三要素等认证方式）                                              |
| cert\_appcode2 | （空） | 实名认证API授权码2（用于阿里云金融级实人认证等）                                             |

#### 其他配置

| 配置键             | 默认值            | 说明                               |
| --------------- | -------------- | -------------------------------- |
| blockname       | 云盘\|网盘\|Q币     | 商品名称违禁词，多个用\|分隔，包含这些词的商品名将被拦截    |
| blockalert      | 温馨提醒该商品禁止出售... | 违禁商品拦截提示语                        |
| blockips        | （空）            | IP黑名单，多个用\|分隔，匹配的IP将禁止访问         |
| blockusers      | （空）            | 买家黑名单，多个用\|分隔，只支持微信公众号支付和支付宝JS支付 |
| recharge        | 1              | 余额充值功能开关，0=关闭，1=开启               |
| user\_review    | 0              | 商户注册审核开关，0=关闭（自动通过），1=开启（需管理员审核） |
| close\_keylogin | 0              | 密钥登录开关，0=开启（商户可使用密钥登录），1=关闭      |
| cronkey         | （空）            | 计划任务密钥，用于验证定时任务请求的合法性            |
| test\_open      | 1              | 测试支付开关，0=关闭，1=开启                 |
| test\_pay\_uid  | 1000           | 测试支付收款商户UID                      |
| pageordername   | 1              | 页面显示订单名称开关，0=隐藏，1=显示             |
| notifyordername | 1              | 回调通知订单名称开关，0=隐藏，1=显示             |

***

### 5.1.2 pre\_cache（缓存表）

系统缓存键值对存储表，支持过期时间机制。

| 字段名    | 类型          | 默认值  | 说明                                       |
| ------ | ----------- | ---- | ---------------------------------------- |
| k      | varchar(32) | -    | 缓存键名（主键），唯一标识一个缓存项                       |
| v      | longtext    | NULL | 缓存值，使用 longtext 类型可存储大体积数据（如序列化后的配置、列表等） |
| expire | int(11)     | 0    | 过期时间戳（Unix 时间戳），0 表示永不过期                 |

***

## 5.2 支付相关表

### 5.2.1 pre\_type（支付方式表）

定义系统支持的所有支付方式，是支付路由的基础数据表。

| 字段名      | 类型               | 默认值             | 说明                                 |
| -------- | ---------------- | --------------- | ---------------------------------- |
| id       | int(11) unsigned | AUTO\_INCREMENT | 支付方式ID（主键），自增                      |
| name     | varchar(30)      | -               | 支付方式英文标识码，如 alipay、wxpay 等，用于代码中引用 |
| device   | int(1) unsigned  | 0               | 设备类型，0=通用/PC，其他值可区分不同设备端           |
| showname | varchar(30)      | -               | 支付方式中文显示名称，如"支付宝"、"微信支付"等          |
| status   | tinyint(1)       | 0               | 启用状态，0=禁用，1=启用                     |

**索引：** PRIMARY(id), KEY name(name, device)

**初始数据：**

| id | name   | device | showname | status |
| -- | ------ | ------ | -------- | ------ |
| 1  | alipay | 0      | 支付宝      | 1      |
| 2  | wxpay  | 0      | 微信支付     | 1      |
| 3  | qqpay  | 0      | QQ钱包     | 1      |
| 4  | bank   | 0      | 网银支付     | 0      |
| 5  | jdpay  | 0      | 京东支付     | 0      |
| 6  | paypal | 0      | PayPal   | 0      |

> 注：id=1\~3 为默认启用的支付方式，id=4\~6 默认禁用，需管理员手动开启。

***

### 5.2.2 pre\_plugin（支付插件表）

存储已安装的支付插件信息，每个插件对应一种第三方支付接口的对接实现。

| 字段名      | 类型           | 默认值  | 说明                                          |
| -------- | ------------ | ---- | ------------------------------------------- |
| name     | varchar(30)  | -    | 插件标识名（主键），如 alipay、wxpay 等，与插件目录名对应         |
| showname | varchar(60)  | NULL | 插件显示名称，如"支付宝官方接口"                           |
| author   | varchar(60)  | NULL | 插件作者                                        |
| link     | varchar(255) | NULL | 插件相关链接（如官网或文档地址）                            |
| types    | varchar(50)  | NULL | 插件支持的支付方式，逗号分隔的支付方式 name 值，如 "alipay,wxpay" |

> 注：update.sql 中还包含 `inputs`（text）和 `select`（text）两个字段，用于存储插件配置表单的输入项定义和选项定义，以序列化格式存储。这两个字段在代码中被使用（如 admin688/ajax\_pay.php 中读取 `$plugin['inputs']` 和 `$plugin['select']`），但 install.sql 中未包含，属于增量更新新增字段。

***

### 5.2.3 pre\_channel（支付通道表）

存储各个支付通道的详细配置，是支付路由的核心表。每个通道对应一个具体的支付接口实例（如某个支付宝商户号、某个微信商户号等）。

| 字段名       | 类型               | 默认值             | 说明                                             |
| --------- | ---------------- | --------------- | ---------------------------------------------- |
| id        | int(11) unsigned | AUTO\_INCREMENT | 通道ID（主键），自增                                    |
| mode      | int(1)           | 0               | 通道模式，0=普通模式，1=直清模式（直清模式下资金直接结算到商户账户）           |
| type      | int(11) unsigned | -               | 支付方式ID，关联 pre\_type.id                         |
| plugin    | varchar(30)      | -               | 支付插件标识名，关联 pre\_plugin.name                    |
| name      | varchar(30)      | -               | 通道名称，如"支付宝官方-主通道"                              |
| rate      | decimal(5,2)     | 100.00          | 通道费率（百分比），如 100.00 表示 100%（即无折扣），95.00 表示 95%  |
| status    | tinyint(1)       | 0               | 通道状态，0=禁用，1=启用                                 |
| appid     | varchar(255)     | NULL            | 应用ID/商户AppID，不同插件的含义可能不同                       |
| appkey    | text             | NULL            | 应用密钥/API Key                                   |
| appsecret | text             | NULL            | 应用密钥/App Secret                                |
| appurl    | varchar(255)     | NULL            | 应用接口URL，用于自定义网关地址                              |
| appmchid  | varchar(255)     | NULL            | 商户号/MCH ID                                     |
| apptype   | varchar(50)      | NULL            | 支付类型代码，逗号分隔，如 "1,2" 表示支持支付方式ID为1和2的类型          |
| daytop    | int(10)          | 0               | 每日交易限额（分），0=不限制，超过后自动禁用该通道                     |
| daystatus | int(1)           | 0               | 每日限额状态，0=正常，1=已达限额（系统自动标记，次日重置）                |
| paymin    | varchar(10)      | NULL            | 单笔最小支付金额（元），低于此金额不路由到该通道                       |
| paymax    | varchar(10)      | NULL            | 单笔最大支付金额（元），高于此金额不路由到该通道                       |
| appwxmp   | int(11)          | NULL            | 关联微信公众号ID，关联 pre\_weixin.id，用于微信公众号支付获取 openid |
| appwxa    | int(11)          | NULL            | 关联微信小程序ID，关联 pre\_weixin.id，用于微信小程序支付          |
| appswitch | tinyint(4)       | NULL            | 支付方式切换配置，用于控制该通道在不同场景下的支付方式切换行为                |

**索引：** PRIMARY(id), KEY type(type)

**mode 字段详细说明：**

| 值 | 含义   | 说明                    |
| - | ---- | --------------------- |
| 0 | 普通模式 | 资金先进入平台账户，再通过结算流程转给商户 |
| 1 | 直清模式 | 资金直接结算到商户账户，平台仅收取手续费  |

***

### 5.2.4 pre\_roll（通道轮询组表）

存储支付通道轮询组配置，实现多通道负载均衡和故障转移。

| 字段名    | 类型               | 默认值             | 说明                                   |
| ------ | ---------------- | --------------- | ------------------------------------ |
| id     | int(11) unsigned | AUTO\_INCREMENT | 轮询组ID（主键），自增（起始值101）                 |
| type   | int(11) unsigned | -               | 支付方式ID，关联 pre\_type.id，表示该轮询组对应的支付方式 |
| name   | varchar(30)      | -               | 轮询组名称，如"支付宝轮询组A"                     |
| kind   | int(1) unsigned  | 0               | 轮询策略，0=顺序轮询，1=加权随机轮询                 |
| info   | text             | NULL            | 轮询通道配置信息，JSON 格式                     |
| status | tinyint(1)       | 0               | 轮询组状态，0=禁用，1=启用                      |
| index  | int(11)          | 0               | 当前轮询索引（用于顺序轮询时记录上次使用的通道位置）           |

**索引：** PRIMARY(id)

**info 字段格式说明：**

info 字段存储 JSON 数组，每个元素代表一个通道及其权重配置：

```json
[
  {"channel": 1, "weight": 10},
  {"channel": 2, "weight": 5},
  {"channel": 3, "weight": 1}
]
```

- `channel`：通道ID，关联 pre\_channel.id
- `weight`：权重值，在加权随机模式下，权重越大的通道被选中的概率越高

在顺序轮询模式（kind=0）下，系统按数组顺序依次选择通道，遇到不可用通道则跳过；在加权随机模式（kind=1）下，系统根据权重随机选择通道。

***

### 5.2.5 pre\_weixin（微信公众号/小程序表）

存储微信公众号和小程序的配置信息，用于微信支付中的 openid 获取和公众号授权。

| 字段名       | 类型                  | 默认值             | 说明                 |
| --------- | ------------------- | --------------- | ------------------ |
| id        | int(11) unsigned    | AUTO\_INCREMENT | 记录ID（主键），自增        |
| type      | tinyint(4) unsigned | 0               | 类型，0=微信公众号，1=微信小程序 |
| name      | varchar(30)         | -               | 名称，如"主公众号"、"商城小程序" |
| status    | tinyint(1)          | 0               | 状态，0=禁用，1=启用       |
| appid     | varchar(150)        | NULL            | 微信 AppID           |
| appsecret | varchar(250)        | NULL            | 微信 AppSecret       |

**索引：** PRIMARY(id)

**type 字段详细说明：**

| 值 | 含义    | 说明                          |
| - | ----- | --------------------------- |
| 0 | 微信公众号 | 用于微信公众号支付、获取用户 openid、微信登录等 |
| 1 | 微信小程序 | 用于微信小程序支付、小程序授权登录等          |

> 注：该表通过 pre\_channel 的 appwxmp 和 appwxa 字段与支付通道关联，一个公众号/小程序可被多个通道引用。

***

## 5.3 订单相关表

### 5.3.1 pre\_order（订单表）

存储所有支付订单信息，是系统最核心的业务数据表。

| 字段名            | 类型                   | 默认值  | 说明                                                        |
| -------------- | -------------------- | ---- | --------------------------------------------------------- |
| trade\_no      | char(19)             | -    | 系统订单号（主键），19位定长字符串，格式为 YmdHis+5位随机数，如 2024010112000012345 |
| out\_trade\_no | varchar(150)         | -    | 商户订单号，由商户提交的唯一订单标识                                        |
| api\_trade\_no | varchar(150)         | NULL | 第三方支付平台交易号，如支付宝交易号、微信支付交易号等                               |
| uid            | int(11) unsigned     | -    | 商户ID，关联 pre\_user.uid                                     |
| tid            | tinyint(11) unsigned | 0    | 订单类型，详见下方说明                                               |
| type           | int(10) unsigned     | -    | 支付方式ID，关联 pre\_type.id                                    |
| channel        | int(10) unsigned     | -    | 支付通道ID，关联 pre\_channel.id                                 |
| name           | varchar(64)          | -    | 商品名称                                                      |
| money          | decimal(10,2)        | -    | 订单金额（元），商户提交的原始金额                                         |
| realmoney      | decimal(10,2)        | NULL | 实际支付金额（元），可能与订单金额不同（如随机增减后）                               |
| getmoney       | decimal(10,2)        | NULL | 商户到账金额（元），扣除手续费后的金额                                       |
| notify\_url    | varchar(255)         | NULL | 异步通知回调地址，支付成功后系统向该URL发送通知                                 |
| return\_url    | varchar(255)         | NULL | 同步跳转地址，支付完成后浏览器跳转到该URL                                    |
| param          | varchar(255)         | NULL | 商户自定义参数，原样返回给商户，可用于传递业务附加信息                               |
| addtime        | datetime             | NULL | 订单创建时间                                                    |
| endtime        | datetime             | NULL | 订单完成时间（支付成功或关闭的时间）                                        |
| date           | date                 | NULL | 订单日期，用于按日期查询和统计                                           |
| domain         | varchar(64)          | NULL | 发起支付的域名                                                   |
| domain2        | varchar(64)          | NULL | 实际跳转支付时的域名（多域名场景下可能与 domain 不同）                           |
| ip             | varchar(20)          | NULL | 发起支付的客户端IP地址                                              |
| buyer          | varchar(30)          | NULL | 买家标识（如支付宝买家账号等）                                           |
| status         | tinyint(1)           | 0    | 订单状态，详见下方说明                                               |
| notify         | int(5)               | 0    | 通知状态，详见下方说明                                               |
| notifytime     | datetime             | NULL | 最后一次通知时间                                                  |
| invite         | int(11) unsigned     | 0    | 邀请人商户ID，关联 pre\_user.uid，0 表示无邀请人                         |
| invitemoney    | decimal(10,2)        | NULL | 邀请返利金额（元），支付成功后邀请人获得的返利                                   |

**索引：**

- PRIMARY(trade\_no) — 系统订单号主键索引
- KEY uid(uid) — 商户ID索引，用于查询商户订单
- KEY out\_trade\_no(out\_trade\_no, uid) — 商户订单号+商户ID联合索引，用于按商户订单号查询
- KEY api\_trade\_no(api\_trade\_no) — 第三方交易号索引，用于对账查询
- KEY invite(invite) — 邀请人索引，用于统计邀请返利
- KEY date(date) — 日期索引，用于按日期范围查询和统计

**tid（订单类型）详细说明：**

| 值 | 含义    | 说明                 |
| - | ----- | ------------------ |
| 0 | 普通订单  | 商户通过API提交的标准支付订单   |
| 1 | 商户注册  | 付费注册商户时产生的支付订单     |
| 2 | 余额充值  | 商户充值账户余额时产生的支付订单   |
| 3 | 聚合收款码 | 通过聚合收款码产生的支付订单     |
| 4 | 购买用户组 | 商户购买/升级用户组时产生的支付订单 |

**status（订单状态）详细说明：**

| 值 | 含义  | 说明            |
| - | --- | ------------- |
| 0 | 未支付 | 订单已创建，等待买家付款  |
| 1 | 已支付 | 买家已完成付款，资金已入账 |
| 2 | 已关闭 | 订单超时未支付或被手动关闭 |

**notify（通知状态）详细说明：**

| 值  | 含义   | 说明                  |
| -- | ---- | ------------------- |
| 0  | 已通知  | 异步通知已成功发送并得到商户确认响应  |
| >0 | 待重试  | 异步通知发送失败，数值表示剩余重试次数 |
| -1 | 重试失败 | 异步通知重试次数已用尽，最终失败    |

> 注：通知重试机制采用递减计数方式，每次重试后 notify 值减1，减至0时标记为已通知成功，若重试次数耗尽仍未成功则标记为 -1。

***

## 5.4 用户相关表

### 5.4.1 pre\_user（商户/用户表）

存储所有商户（用户）的完整信息，包括账户、认证、资金、权限等，是用户体系的核心表。自增ID从1000开始。

| 字段名          | 类型               | 默认值             | 说明                                          |
| ------------ | ---------------- | --------------- | ------------------------------------------- |
| uid          | int(11) unsigned | AUTO\_INCREMENT | 商户ID（主键），自增（起始值1000）                        |
| gid          | int(11) unsigned | 0               | 用户组ID，关联 pre\_group.gid，0=默认用户组             |
| upid         | int(11) unsigned | 0               | 上级邀请人商户ID，关联 pre\_user.uid，0=无上级            |
| key          | varchar(32)      | -               | 商户密钥，用于API签名验证，注册时自动生成                      |
| pwd          | varchar(32)      | NULL            | 登录密码（MD5加密存储）                               |
| account      | varchar(128)     | NULL            | 登录账号（邮箱或手机号）                                |
| username     | varchar(128)     | NULL            | 商户显示名称/昵称                                   |
| codename     | varchar(32)      | NULL            | 商户编码名称，用于特定场景标识                             |
| settle\_id   | tinyint(4)       | 1               | 默认结算方式，1=支付宝，2=微信，3=QQ钱包，4=银行卡              |
| alipay\_uid  | varchar(32)      | NULL            | 绑定的支付宝用户ID（用于支付宝登录和结算）                      |
| qq\_uid      | varchar(32)      | NULL            | 绑定的QQ OpenID（用于QQ登录）                        |
| wx\_uid      | varchar(32)      | NULL            | 绑定的微信OpenID（用于微信登录）                         |
| money        | decimal(10,2)    | -               | 账户可用余额（元）                                   |
| email        | varchar(32)      | NULL            | 邮箱地址                                        |
| phone        | varchar(20)      | NULL            | 手机号码                                        |
| qq           | varchar(20)      | NULL            | QQ号码                                        |
| url          | varchar(64)      | NULL            | 商户网站URL                                     |
| cert         | tinyint(4)       | 0               | 实名认证状态，0=未认证，1=已认证                          |
| certtype     | tinyint(4)       | 0               | 实名认证类型，0=个人认证，1=企业认证                        |
| certmethod   | tinyint(4)       | 0               | 实名认证方式，对应 cert\_open 配置的认证方式代码              |
| certno       | varchar(18)      | NULL            | 身份证号码（个人认证）或统一社会信用代码（企业认证）                  |
| certname     | varchar(32)      | NULL            | 真实姓名（个人认证）或企业名称（企业认证）                       |
| certtime     | datetime         | NULL            | 实名认证通过时间                                    |
| certtoken    | varchar(64)      | NULL            | 实名认证令牌，用于认证流程中的临时凭证                         |
| certcorpno   | varchar(30)      | NULL            | 企业营业执照号（企业认证时使用）                            |
| certcorpname | varchar(80)      | NULL            | 企业名称（企业认证时使用）                               |
| addtime      | datetime         | NULL            | 注册时间                                        |
| lasttime     | datetime         | NULL            | 最后登录时间                                      |
| endtime      | datetime         | NULL            | 用户组到期时间（购买的用户组有过期时间）                        |
| level        | tinyint(1)       | 1               | 商户等级，1=普通商户                                 |
| pay          | tinyint(1)       | 1               | 支付权限，0=禁止，1=允许（禁止后商户无法发起支付）                 |
| settle       | tinyint(1)       | 1               | 结算权限，0=禁止，1=允许（禁止后商户无法申请结算）                 |
| keylogin     | tinyint(1)       | 1               | 密钥登录权限，0=禁止，1=允许                            |
| apply        | tinyint(1)       | 0               | 审核状态，0=已通过/无需审核，1=待审核（配合 user\_review 配置使用） |
| mode         | tinyint(4)       | 0               | 商户模式，0=普通商户，1=直清商户                          |
| status       | tinyint(4)       | 0               | 账户状态，0=正常，1=禁用                              |
| refund       | tinyint(1)       | 0               | 退款权限，0=禁止，1=允许                              |
| channelinfo  | text             | NULL            | 商户自定义通道配置，JSON 格式，可覆盖用户组默认通道配置              |
| ordername    | varchar(255)     | NULL            | 商户自定义订单名称，用于覆盖原始订单名称显示                      |

**索引：** PRIMARY(uid), KEY email(email), KEY phone(phone)

> 注：uid 自增起始值为 1000，即第一个注册的商户 ID 为 1000，避免与系统内部 ID 冲突。

***

### 5.4.2 pre\_group（用户组表）

定义商户用户组及其权限配置，不同用户组可拥有不同的费率和通道权限。

| 字段名          | 类型               | 默认值             | 说明                         |
| ------------ | ---------------- | --------------- | -------------------------- |
| gid          | int(11) unsigned | AUTO\_INCREMENT | 用户组ID（主键），自增（gid=0 为默认用户组） |
| name         | varchar(30)      | -               | 用户组名称，如"默认用户组"、"VIP用户组"    |
| info         | varchar(1024)    | NULL            | 用户组通道费率配置，JSON 格式          |
| isbuy        | tinyint(1)       | 0               | 是否可购买，0=不可购买，1=可购买         |
| price        | decimal(10,2)    | NULL            | 购买价格（元），isbuy=1 时有效        |
| sort         | int(10)          | 0               | 排序权重，数值越大越靠前               |
| expire       | int(10)          | 0               | 有效期（天），0=永久有效              |
| settle\_open | int(1)           | 0               | 用户组结算开关，0=继承全局配置，1=开启      |
| settle\_type | int(1)           | 0               | 用户组结算方式，0=继承全局配置，1=自动结算    |
| settings     | text             | NULL            | 用户组其他设置，JSON 格式，存储扩展配置     |

**索引：** PRIMARY(gid)

**info 字段 JSON 格式说明：**

info 字段存储各支付方式的通道和费率配置，以支付方式ID为键：

```json
{
  "1": {"type": "", "channel": "-1", "rate": ""},
  "2": {"type": "", "channel": "-1", "rate": ""},
  "3": {"type": "", "channel": "-1", "rate": ""}
}
```

- 键为支付方式ID（字符串），如 "1"=支付宝，"2"=微信支付，"3"=QQ钱包
- `type`：指定支付方式，空字符串表示使用默认
- `channel`：指定通道ID，"-1"表示使用轮询组，空字符串表示使用默认，正整数表示指定通道ID
- `rate`：自定义费率（百分比），空字符串表示使用全局默认费率

**初始数据：**

| gid | name  | info                                                                                                                         |
| --- | ----- | ---------------------------------------------------------------------------------------------------------------------------- |
| 0   | 默认用户组 | {"1":{"type":"","channel":"-1","rate":""},"2":{"type":"","channel":"-1","rate":""},"3":{"type":"","channel":"-1","rate":""}} |

> 注：默认用户组的 gid=0 是通过 INSERT 后 UPDATE 强制设置的，不是自增值。

***

### 5.4.3 pre\_domain（授权域名表）

存储商户的授权域名信息，配合支付域名白名单/黑名单功能使用。

| 字段名     | 类型               | 默认值             | 说明                    |
| ------- | ---------------- | --------------- | --------------------- |
| id      | int(11) unsigned | AUTO\_INCREMENT | 记录ID（主键），自增           |
| uid     | int(11)          | 0               | 商户ID，关联 pre\_user.uid |
| domain  | varchar(128)     | -               | 授权域名，如 example.com    |
| status  | tinyint(1)       | 0               | 审核状态，0=待审核，1=已通过      |
| addtime | datetime         | NULL            | 添加时间                  |
| endtime | datetime         | NULL            | 审核通过时间                |

**索引：** PRIMARY(id), KEY domain(domain, uid)

> 注：当系统开启域名白名单（pay\_domain\_open=1）时，只有通过审核的域名才能发起支付请求。

***

## 5.5 资金相关表

### 5.5.1 pre\_settle（结算记录表）

存储商户结算申请记录，记录每笔结算的详细信息。

| 字段名              | 类型            | 默认值             | 说明                              |
| ---------------- | ------------- | --------------- | ------------------------------- |
| id               | int(11)       | AUTO\_INCREMENT | 结算记录ID（主键），自增                   |
| uid              | int(11)       | -               | 商户ID，关联 pre\_user.uid           |
| batch            | varchar(20)   | NULL            | 批量转账批次号，关联 pre\_batch.batch     |
| auto             | int(1)        | 1               | 结算方式，0=手动结算，1=自动结算              |
| type             | int(1)        | 1               | 结算类型，1=支付宝，2=微信，3=QQ钱包，4=银行卡    |
| account          | varchar(128)  | -               | 收款账号（支付宝账号、微信号、银行卡号等）           |
| username         | varchar(128)  | -               | 收款人姓名                           |
| money            | decimal(10,2) | -               | 结算金额（元），商户申请的结算金额               |
| realmoney        | decimal(10,2) | -               | 实际到账金额（元），扣除手续费后的金额             |
| addtime          | datetime      | NULL            | 结算申请时间                          |
| endtime          | datetime      | NULL            | 结算完成时间                          |
| status           | int(1)        | 0               | 结算状态，0=待审核，1=已审核待转账，2=已完成，3=已驳回 |
| transfer\_status | int(1)        | 0               | 转账状态，0=未转账，1=转账中，2=转账成功，3=转账失败  |
| transfer\_result | varchar(64)   | NULL            | 转账结果描述                          |
| transfer\_date   | datetime      | NULL            | 转账完成时间                          |
| result           | varchar(64)   | NULL            | 审核结果/备注信息                       |

**索引：** PRIMARY(id), KEY uid(uid), KEY batch(batch)

***

### 5.5.2 pre\_record（资金明细表）

记录商户账户的每一笔资金变动，用于资金流水查询和对账。

| 字段名       | 类型            | 默认值             | 说明                            |
| --------- | ------------- | --------------- | ----------------------------- |
| id        | int(11)       | AUTO\_INCREMENT | 记录ID（主键），自增                   |
| uid       | int(11)       | -               | 商户ID，关联 pre\_user.uid         |
| action    | int(1)        | 0               | 资金变动方向，1=收入，2=支出              |
| money     | decimal(10,2) | -               | 变动金额（元），始终为正数                 |
| oldmoney  | decimal(10,2) | -               | 变动前余额（元）                      |
| newmoney  | decimal(10,2) | -               | 变动后余额（元）                      |
| type      | varchar(20)   | NULL            | 变动类型描述，如 "订单收入"、"结算支出"、"充值" 等 |
| trade\_no | varchar(64)   | NULL            | 关联订单号，关联 pre\_order.trade\_no |
| date      | datetime      | -               | 变动时间                          |

**索引：** PRIMARY(id), KEY uid(uid), KEY trade\_no(trade\_no)

**action 字段详细说明：**

| 值 | 含义 | 典型场景                  |
| - | -- | --------------------- |
| 1 | 收入 | 订单支付成功到账、充值到账、邀请返利到账等 |
| 2 | 支出 | 结算扣款、退款扣款等            |

> 注：通过 oldmoney 和 newmoney 字段可以追溯每次变动前后的余额，确保资金变动的可审计性。

***

### 5.5.3 pre\_batch（批量转账表）

存储批量转账的汇总信息，与 pre\_settle 通过 batch 字段关联。

| 字段名      | 类型            | 默认值  | 说明                            |
| -------- | ------------- | ---- | ----------------------------- |
| batch    | varchar(20)   | -    | 批次号（主键），唯一标识一次批量转账            |
| allmoney | decimal(10,2) | -    | 批次总金额（元），该批次所有结算金额之和          |
| count    | int(11)       | 0    | 批次结算笔数                        |
| time     | datetime      | NULL | 批次创建时间                        |
| status   | int(1)        | 0    | 批次状态，0=待处理，1=处理中，2=已完成，3=部分失败 |

**索引：** PRIMARY(batch)

> 注：一个批次可包含多笔结算记录，通过 pre\_settle.batch 字段关联。管理员可一次性审核并转账一个批次中的所有结算。

***

## 5.6 安全相关表

### 5.6.1 pre\_risk（风控记录表）

记录系统风控拦截的详细信息，用于风险分析和审计。

| 字段名     | 类型          | 默认值             | 说明                                  |
| ------- | ----------- | --------------- | ----------------------------------- |
| id      | int(11)     | AUTO\_INCREMENT | 记录ID（主键），自增                         |
| uid     | int(11)     | 0               | 商户ID，关联 pre\_user.uid，0 表示非商户相关风控   |
| type    | int(1)      | 0               | 风控类型，0=违禁商品，1=黑名单IP，2=黑名单买家，3=域名限制等 |
| url     | varchar(64) | NULL            | 触发风控的请求URL                          |
| content | varchar(64) | NULL            | 风控内容描述，如触发的违禁词、被拦截的IP等              |
| date    | datetime    | NULL            | 风控触发时间                              |
| status  | int(1)      | 0               | 处理状态，0=未处理，1=已处理                    |

**索引：** PRIMARY(id), KEY uid(uid)

***

### 5.6.2 pre\_alipayrisk（支付宝风控表）

存储来自支付宝风控系统的风险预警信息，专门用于对接支付宝风控通知。

| 字段名           | 类型               | 默认值             | 说明                         |
| ------------- | ---------------- | --------------- | -------------------------- |
| id            | int(11)          | AUTO\_INCREMENT | 记录ID（主键），自增                |
| channel       | int(10) unsigned | -               | 支付通道ID，关联 pre\_channel.id  |
| pid           | varchar(40)      | -               | 支付宝合作商户ID（Partner ID）      |
| smid          | varchar(40)      | NULL            | 支付宝二级商户ID（Sub Merchant ID） |
| tradeNos      | varchar(40)      | NULL            | 关联的支付宝交易号                  |
| risktype      | varchar(40)      | NULL            | 风险类型代码                     |
| risklevel     | varchar(60)      | NULL            | 风险等级，如 HIGH、MEDIUM、LOW     |
| riskDesc      | varchar(500)     | NULL            | 风险描述信息                     |
| complainTime  | varchar(128)     | NULL            | 投诉时间                       |
| complainText  | varchar(500)     | NULL            | 投诉内容                       |
| date          | datetime         | NULL            | 记录创建时间                     |
| status        | tinyint(1)       | 0               | 处理状态，0=未处理，1=已处理           |
| process\_code | varchar(2)       | NULL            | 处理结果代码                     |

**索引：** PRIMARY(id)

> 注：该表由 update2.sql 增量更新新增，用于接收和处理支付宝风控系统的回调通知，帮助平台及时识别和处理风险交易。

***

### 5.6.3 pre\_log（登录日志表）

记录用户和管理员的登录日志，用于安全审计和异常检测。

| 字段名  | 类型          | 默认值             | 说明                                   |
| ---- | ----------- | --------------- | ------------------------------------ |
| id   | int(11)     | AUTO\_INCREMENT | 日志ID（主键），自增                          |
| uid  | int(11)     | 0               | 用户ID，0 表示管理员登录                       |
| type | varchar(20) | NULL            | 登录类型，如 "login"（登录）、"keylogin"（密钥登录）等 |
| date | datetime    | -               | 登录时间                                 |
| ip   | varchar(20) | NULL            | 登录IP地址                               |
| city | varchar(20) | NULL            | IP归属地（城市）                            |
| data | text        | NULL            | 附加数据，如 User-Agent 等信息                |

**索引：** PRIMARY(id)

***

### 5.6.4 pre\_regcode（注册验证码表）

存储注册和找回密码时发送的验证码信息。

| 字段名      | 类型          | 默认值             | 说明                      |
| -------- | ----------- | --------------- | ----------------------- |
| id       | int(11)     | AUTO\_INCREMENT | 记录ID（主键），自增             |
| uid      | int(11)     | 0               | 关联用户ID，0 表示未关联          |
| type     | int(1)      | 0               | 验证码类型，0=注册验证码，1=找回密码验证码 |
| code     | varchar(32) | -               | 验证码内容                   |
| to       | varchar(32) | NULL            | 接收验证码的目标地址（邮箱或手机号）      |
| time     | int(11)     | -               | 验证码创建时间（Unix 时间戳）       |
| ip       | varchar(20) | NULL            | 请求验证码的IP地址              |
| status   | int(1)      | 0               | 使用状态，0=未使用，1=已使用        |
| errcount | int(11)     | 0               | 验证错误次数，超过限制后验证码失效       |

**索引：** PRIMARY(id), KEY code(to, type)

> 注：errcount 字段由 update2.sql 增量更新新增，用于防止验证码暴力破解，错误次数达到上限后该验证码自动失效。

***

## 5.7 其他表

### 5.7.1 pre\_anounce（公告表）

存储系统公告信息，在前台页面滚动显示。

| 字段名     | 类型               | 默认值             | 说明                         |
| ------- | ---------------- | --------------- | -------------------------- |
| id      | int(11) unsigned | AUTO\_INCREMENT | 公告ID（主键），自增                |
| content | text             | NULL            | 公告内容                       |
| color   | varchar(10)      | NULL            | 公告文字颜色，如 "#FF0000"、"red" 等 |
| sort    | int(11)          | 1               | 排序权重，数值越小越靠前               |
| addtime | datetime         | NULL            | 发布时间                       |
| status  | tinyint(1)       | 1               | 状态，0=隐藏，1=显示               |

**索引：** PRIMARY(id)

***

## 5.8 表间关联关系

以下是系统各主要表之间的关联关系，理解这些关联对于把握系统数据流至关重要。

### 5.8.1 核心业务关联

```
pre_order.uid ──────→ pre_user.uid          （订单所属商户）
pre_order.type ─────→ pre_type.id           （订单支付方式）
pre_order.channel ──→ pre_channel.id        （订单使用的支付通道）
pre_order.invite ───→ pre_user.uid          （订单邀请人）
```

### 5.8.2 支付通道关联

```
pre_channel.type ───→ pre_type.id           （通道支持的支付方式）
pre_channel.plugin ─→ pre_plugin.name       （通道使用的支付插件）
pre_channel.appwxmp→ pre_weixin.id          （通道关联的微信公众号）
pre_channel.appwxa ─→ pre_weixin.id         （通道关联的微信小程序）
```

### 5.8.3 用户体系关联

```
pre_user.gid ───────→ pre_group.gid         （商户所属用户组）
pre_user.upid ──────→ pre_user.uid          （商户的上级邀请人，自关联）
```

### 5.8.4 资金关联

```
pre_settle.uid ─────→ pre_user.uid          （结算记录所属商户）
pre_settle.batch ───→ pre_batch.batch       （结算记录所属批次）
pre_record.uid ─────→ pre_user.uid          （资金明细所属商户）
pre_record.trade_no → pre_order.trade_no    （资金明细关联订单）
```

### 5.8.5 域名与风控关联

```
pre_domain.uid ─────→ pre_user.uid          （授权域名所属商户）
pre_risk.uid ───────→ pre_user.uid          （风控记录关联商户）
pre_alipayrisk.channel→ pre_channel.id      （支付宝风控关联通道）
```

### 5.8.6 轮询组关联

```
pre_roll.type ──────→ pre_type.id           （轮询组对应的支付方式）
pre_roll.info中的channel → pre_channel.id   （轮询组包含的通道）
```

### 5.8.7 关联关系总览图

```
┌──────────┐     ┌──────────┐     ┌──────────┐
│ pre_type │←────│pre_channel│←────│ pre_roll │
│ 支付方式  │     │ 支付通道   │     │ 轮询组   │
└──────────┘     └────┬─────┘     └──────────┘
                      │                ↑
                      │                │
                 ┌────┴────┐     ┌────┴────┐
                 │pre_plugin│     │ info中  │
                 │ 支付插件  │     │ 通道ID  │
                 └─────────┘     └─────────┘
                      │
                 ┌────┴────┐
                 │pre_weixin│
                 │公众号/小程序│
                 └─────────┘

┌──────────┐     ┌──────────┐     ┌──────────┐
│ pre_user │←────│pre_order │     │pre_settle│
│  商户    │     │  订单     │     │  结算    │
│          │←────│          │     │          │←─── pre_batch
│          │←────│(invite)  │     │          │     批量转账
│          │     └──────────┘     └──────────┘
│          │←────┌──────────┐     ┌──────────┐
│          │     │pre_record│     │pre_domain│
│          │←────│ 资金明细  │     │ 授权域名  │
└────┬─────┘     └──────────┘     └──────────┘
     │
     ↓
┌──────────┐
│pre_group │
│ 用户组    │
└──────────┘

┌──────────┐     ┌──────────────┐
│ pre_risk │     │pre_alipayrisk│
│ 风控记录  │     │ 支付宝风控    │
└──────────┘     └──────────────┘

┌──────────┐     ┌──────────┐
│pre_log   │     │pre_regcode│
│ 登录日志  │     │ 注册验证码  │
└──────────┘     └──────────┘

┌──────────┐     ┌──────────┐
│pre_config│     │pre_cache │
│ 系统配置  │     │  缓存     │
└──────────┘     └──────────┘

┌──────────┐
│pre_anounce│
│  公告     │
└──────────┘
```

***

## 5.9 数据库设计总结

### 5.9.1 表清单汇总

| 序号 | 表名              | 说明         | 记录类型       |
| -- | --------------- | ---------- | ---------- |
| 1  | pre\_config     | 系统配置表      | KV键值对      |
| 2  | pre\_cache      | 缓存表        | KV键值对+过期时间 |
| 3  | pre\_type       | 支付方式表      | 基础数据       |
| 4  | pre\_plugin     | 支付插件表      | 基础数据       |
| 5  | pre\_channel    | 支付通道表      | 业务配置       |
| 6  | pre\_roll       | 通道轮询组表     | 业务配置       |
| 7  | pre\_weixin     | 微信公众号/小程序表 | 业务配置       |
| 8  | pre\_order      | 订单表        | 交易数据       |
| 9  | pre\_user       | 商户/用户表     | 用户数据       |
| 10 | pre\_group      | 用户组表       | 业务配置       |
| 11 | pre\_domain     | 授权域名表      | 业务配置       |
| 12 | pre\_settle     | 结算记录表      | 资金数据       |
| 13 | pre\_record     | 资金明细表      | 资金数据       |
| 14 | pre\_batch      | 批量转账表      | 资金数据       |
| 15 | pre\_risk       | 风控记录表      | 安全数据       |
| 16 | pre\_alipayrisk | 支付宝风控表     | 安全数据       |
| 17 | pre\_log        | 登录日志表      | 安全数据       |
| 18 | pre\_regcode    | 注册验证码表     | 安全数据       |
| 19 | pre\_anounce    | 公告表        | 内容数据       |

### 5.9.2 设计特点

1. **KV 配置模式**：系统配置（pre\_config）和缓存（pre\_cache）采用键值对存储，灵活可扩展，新增配置项无需修改表结构。
2. **通道-插件分离**：支付通道（pre\_channel）与支付插件（pre\_plugin）分离设计，一个插件可被多个通道复用，通道配置独立管理。
3. **轮询组机制**：通过 pre\_roll 表实现多通道负载均衡，支持顺序轮询和加权随机两种策略，提高支付可用性。
4. **用户组权限体系**：通过 pre\_group 表实现差异化的费率和通道权限管理，支持用户组购买和有效期控制。
5. **资金可审计**：pre\_record 表记录每笔资金变动的变动前后余额，确保资金流水的完整性和可审计性。
6. **批量结算**：pre\_batch 与 pre\_settle 通过 batch 字段关联，支持批量审核和批量转账，提高运营效率。
7. **多维度风控**：pre\_risk 记录通用风控事件，pre\_alipayrisk 专门对接支付宝风控，形成多层次风险防控体系。
8. **实名认证体系**：pre\_user 表包含完整的实名认证字段（个人认证和企业认证），支持多种认证方式。

***

# 六、环境配置指南

## 6.1 服务器环境要求

### 软件版本要求

| 组件      | 最低版本           | 推荐版本            |
| ------- | -------------- | --------------- |
| PHP     | >= 7.1         | 7.4 / 8.0       |
| MySQL   | >= 5.5         | 5.7 / 8.0       |
| Web 服务器 | Apache / Nginx | Nginx + PHP-FPM |

### PHP 扩展要求

以下扩展为系统运行所必需，安装程序会自动检测：

| 扩展         | 说明               | 必需 |
| ---------- | ---------------- | -- |
| pdo\_mysql | 数据库连接驱动（PDO 方式）  | 是  |
| curl       | HTTP 请求，用于支付接口通信 | 是  |
| gd         | 图像处理，验证码生成       | 是  |
| mbstring   | 多字节字符串处理         | 是  |
| json       | JSON 编解码         | 是  |
| openssl    | 加密与 HTTPS 支持     | 是  |

### 目录权限要求

- 项目根目录需具有**写入权限**，安装程序需要写入 `config.php` 配置文件
- `/install/` 目录需具有写入权限，安装完成后会自动创建 `install.lock` 锁文件

### 推荐服务器配置

- **操作系统**：CentOS 7+ / Ubuntu 18.04+
- **CPU**：2 核及以上
- **内存**：2GB 及以上
- **磁盘**：20GB 及以上（视业务量而定）
- **PHP 配置建议**：
  - `max_execution_time = 300`
  - `memory_limit = 256M`
  - `post_max_size = 50M`
  - `upload_max_filesize = 50M`
  - `disable_functions` 中不要禁用 `curl_exec`、`set_time_limit`、`ignore_user_abort`

***

## 6.2 安装流程

### 步骤一：上传项目文件

将项目所有文件上传至 Web 服务器的网站根目录（如 `/www/wwwroot/pay/`），确保目录结构完整。

### 步骤二：配置数据库连接

编辑项目根目录下的 [config.php](file:///www/wwwroot/pay/config.php) 文件，填写数据库连接信息：

```php
<?php
    $dbconfig=array(
        'host' => 'localhost',   // 数据库服务器地址
        'port' => 3306,          // 数据库端口
        'user' => 'your_user',   // 数据库用户名
        'pwd'  => 'your_pwd',    // 数据库密码
        'dbname' => 'your_db',   // 数据库名
        'dbqz' => 'pay'          // 数据表前缀（默认 pay）
    );
```

> **说明**：也可以在安装向导中在线填写，安装程序会自动生成并保存此文件。

### 步骤三：运行安装向导

在浏览器中访问 `http://你的域名/install/` 进入安装程序，安装流程共 5 步：

1. **环境检测** — 自动检测 PHP 版本（>=7.1）、PDO\_MYSQL 组件、CURL 组件、目录写入权限
2. **数据库配置** — 填写 MySQL 连接信息（地址、端口、用户名、密码、数据库名、表前缀）
3. **保存配置** — 验证数据库连接，保存配置文件到 `config.php`
4. **安装数据表** — 自动执行 `install.sql` 创建所有数据库表并插入初始数据
5. **安装完成** — 显示安装结果

### 步骤四：安装程序自动处理

安装程序（[install/index.php](file:///www/wwwroot/pay/install/index.php)）会自动完成以下操作：

- 创建所有数据库表（共 16 张表，包括 `pre_config`、`pre_order`、`pre_user`、`pre_channel` 等）
- 插入初始配置数据（站点名称、管理员账号、支付类型等）
- 自动生成 `syskey`（32 位随机系统密钥）
- 自动生成 `cronkey`（6 位随机计划任务密钥）
- 记录安装日期
- 在 `/install/` 目录下创建 `install.lock` 文件

安装完成后默认管理员信息：

- **后台地址**：`http://你的域名/admin/`
- **默认密码**：`123456`

> ⚠️ **请务必在安装后立即修改管理员密码！**

### 步骤五：安全加固

安装完成后，系统会在 `/install/` 目录下自动创建 `install.lock` 文件。系统初始化时（[common.php](file:///www/wwwroot/pay/includes/common.php#L87-L89)）会检测此文件是否存在，若不存在则阻止系统运行并提示安装。

**安全建议**：

- **推荐做法**：删除整个 `/install/` 目录
- **备选做法**：保留 `/install/` 目录但确保 `install.lock` 文件存在，并通过 Web 服务器配置禁止访问该目录
- 如需重新安装，需手动删除 `install.lock` 文件

***

## 6.3 Nginx 配置

以下为完整的 Nginx 站点配置示例，基于项目自带的 [nginx.txt](file:///www/wwwroot/pay/nginx.txt) 配置规则：

```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /www/wwwroot/pay;
    index index.php index.html;

    # URL 重写规则
    location / {
        if (!-e $request_filename) {
            rewrite ^/(.[a-zA-Z0-9\-\_]+).html$ /index.php?mod=$1 last;
        }
        rewrite ^/pay/(.*)$ /pay.php?s=$1 last;
    }

    # 禁止直接访问 plugins 目录
    location ^~ /plugins {
        deny all;
    }

    # 禁止直接访问 includes 目录
    location ^~ /includes {
        deny all;
    }

    # 禁止访问安装目录（安装完成后启用）
    # location ^~ /install {
    #     deny all;
    # }

    # 禁止访问隐藏文件和目录
    location ~ /\. {
        deny all;
    }

    # PHP-FPM 配置
    location ~ \.php$ {
        fastcgi_pass unix:/tmp/php-cgi.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;

        # 超时设置（计划任务可能需要较长执行时间）
        fastcgi_read_timeout 300;
    }

    # 静态资源缓存
    location ~ .*\.(gif|jpg|jpeg|png|bmp|swf|js|css)$ {
        expires 30d;
        access_log off;
    }
}
```

### HTTPS 配置建议

```nginx
server {
    listen 443 ssl http2;
    server_name your-domain.com;
    root /www/wwwroot/pay;
    index index.php index.html;

    # SSL 证书配置
    ssl_certificate /etc/ssl/your-domain.com.pem;
    ssl_certificate_key /etc/ssl/your-domain.com.key;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;
    ssl_prefer_server_ciphers on;
    ssl_session_cache shared:SSL:10m;
    ssl_session_timeout 10m;

    # URL 重写规则（同上）
    location / {
        if (!-e $request_filename) {
            rewrite ^/(.[a-zA-Z0-9\-\_]+).html$ /index.php?mod=$1 last;
        }
        rewrite ^/pay/(.*)$ /pay.php?s=$1 last;
    }

    location ^~ /plugins { deny all; }
    location ^~ /includes { deny all; }

    location ~ \.php$ {
        fastcgi_pass unix:/tmp/php-cgi.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_read_timeout 300;
    }
}

# HTTP 自动跳转 HTTPS
server {
    listen 80;
    server_name your-domain.com;
    return 301 https://$host$request_uri;
}
```

### 配置说明

| 规则                                                | 说明                                    |
| ------------------------------------------------- | ------------------------------------- |
| `^(.[a-zA-Z0-9\-\_]+).html$` → `index.php?mod=$1` | 将静态化页面 URL 重写到 `index.php` 的 `mod` 参数 |
| `^/pay/(.*)$` → `pay.php?s=$1`                    | 将支付页面 URL 重写到 `pay.php` 的 `s` 参数      |
| `location ^~ /plugins`                            | 禁止直接访问插件目录，防止源码泄露                     |
| `location ^~ /includes`                           | 禁止直接访问核心类库目录，防止源码泄露                   |

***

## 6.4 Apache 配置

项目自带 [.htaccess](file:///www/wwwroot/pay/.htaccess) 文件，Apache 服务器无需额外配置，只需确保已启用 `mod_rewrite` 模块即可。

### .htaccess 重写规则

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

### 规则说明

| 规则                                       | 说明                                        |
| ---------------------------------------- | ----------------------------------------- |
| `Options +FollowSymlinks`                | 允许跟随符号链接                                  |
| `RewriteEngine On`                       | 启用 URL 重写引擎                               |
| `RewriteCond %{REQUEST_FILENAME} !-d`    | 条件：请求的路径不是已存在的目录                          |
| `RewriteCond %{REQUEST_FILENAME} !-f`    | 条件：请求的路径不是已存在的文件                          |
| `RewriteRule ^(.[a-zA-Z0-9\-\_]+).html$` | 将 `.html` 结尾的 URL 重写到 `index.php?mod=` 参数 |
| `RewriteRule ^pay/(.*)$`                 | 将 `/pay/` 开头的 URL 重写到 `pay.php?s=` 参数     |
| `[QSA]`                                  | Query String Append，保留原始查询参数              |
| `[PT]`                                   | Pass Through，将重写结果传递给下一个处理器               |
| `[L]`                                    | Last，匹配后不再继续处理后续规则                        |

### 启用 mod\_rewrite

```bash
# Ubuntu/Debian
sudo a2enmod rewrite
sudo systemctl restart apache2

# CentOS
# mod_rewrite 通常默认启用，确认 httpd.conf 中有：
# LoadModule rewrite_module modules/mod_rewrite.so
```

### 目录访问限制（可选）

如需在 Apache 中禁止直接访问 `plugins` 和 `includes` 目录，可在站点配置或 `.htaccess` 中添加：

```apache
<DirectoryMatch "^.*(plugins|includes).*$">
    Require all denied
</DirectoryMatch>
```

***

## 6.5 IIS 配置

项目提供了 [IIS.txt](file:///www/wwwroot/pay/IIS.txt) 中的 URL 重写规则，需配合 IIS URL Rewrite 模块使用。

### web.config 配置

将以下内容保存为 `web.config` 文件放置在网站根目录：

```xml
<?xml version="1.0" encoding="UTF-8"?>
<configuration>
  <system.webServer>
    <rewrite>
      <rules>
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
      </rules>
    </rewrite>
  </system.webServer>
</configuration>
```

### 规则说明

| 规则                      | 说明                                                      |
| ----------------------- | ------------------------------------------------------- |
| `payrule1_rewrite`      | 将 `.html` 结尾的 URL 重写到 `index.php?mod=`，仅在请求的文件/目录不存在时生效 |
| `payrule2_rewrite`      | 将 `/pay/` 开头的 URL 重写到 `pay.php?s=`，仅在请求的文件/目录不存在时生效     |
| `stopProcessing="true"` | 匹配后不再处理后续规则                                             |
| `negate="true"`         | 条件取反（即文件不存在且目录不存在时才重写）                                  |

### 前置条件

- 安装 IIS URL Rewrite 2.0 模块：可通过 Microsoft Web Platform Installer 安装
- 确保已安装 PHP 并正确配置 IIS 与 PHP 的集成（通过 FastCGI 方式）

***

## 6.6 计划任务配置

计划任务通过访问 [cron.php](file:///www/wwwroot/pay/cron.php) 执行，所有任务均需通过 `key` 参数进行身份验证，密钥为安装时自动生成的 `cronkey`（可在后台系统设置中查看和修改）。

> ⚠️ **重要**：首次使用前，请确保已在后台系统设置中配置好监控密钥（`cronkey`），否则计划任务将无法执行。

### 6.6.1 自动结算

**调用地址**：`http://你的域名/cron.php?do=settle&key=你的cronkey`

**触发条件**：后台系统设置中 `settle_open` 值为 `1`（自动结算）或 `3`（自动结算+自动转账）

**处理逻辑**：

1. 检查今日是否已执行过结算任务（通过 `settle_time` 配置项判断），避免重复执行
2. 查询所有满足以下条件的商户：
   - 账户余额 >= 结算起付金额（`settle_money`，默认 30 元）
   - 已填写收款账号（`account` 不为空）
   - 已填写收款人姓名（`username` 不为空）
   - 已开启结算功能（`settle=1`）
   - 账户状态正常（`status=1`）
3. 若后台开启了强制实名认证（`cert_force=1`），则跳过未认证商户
4. 计算结算手续费：
   - 若 `settle_rate > 0`，手续费 = 余额 × 手续费率 / 100
   - 手续费下限为 `settle_fee_min`（默认 0.1 元），上限为 `settle_fee_max`（默认 20 元）
   - 实际到账金额 = 余额 - 手续费
5. 生成结算记录并扣除商户余额
6. 更新 `settle_time` 为当前时间

**建议执行频率**：每天 1 次

### 6.6.2 订单统计与清理

**调用地址**：`http://你的域名/cron.php?do=order&key=你的cronkey`

**处理逻辑**：

1. 检查今日是否已执行过（通过 `order_time` 配置项判断），避免重复执行
2. 清理 24 小时前未支付的订单（`status=0` 且 `addtime` 超过 24 小时）
3. 清理 24 小时前过期的验证码记录
4. 清理系统缓存
5. 统计昨日订单数据：
   - 按支付类型（支付宝、微信、QQ 钱包等）汇总交易金额
   - 按支付通道汇总交易金额
   - 计算总交易金额
   - 将统计结果缓存到 `order_YYYYMMDD` 键中
6. 重置所有支付通道的日限额状态（`daystatus` 设为 0）

**建议执行频率**：每天 1 次

### 6.6.3 通知重试

**调用地址**：`http://你的域名/cron.php?do=notify&key=你的cronkey`

**处理逻辑**：

1. 查询满足以下条件的订单：
   - 订单完成时间在 1 天以内
   - 通知状态 `notify > 0`（表示通知尚未成功）
   - 下次通知时间 `notifytime` 已到达
2. 每次最多处理 **20** 个订单
3. 通知间隔采用递增策略：
   | 通知次数          | 距上次通知的间隔           |
   | ------------- | ------------------ |
   | 第 1 次 → 第 2 次 | 2 分钟               |
   | 第 2 次 → 第 3 次 | 16 分钟              |
   | 第 3 次 → 第 4 次 | 36 分钟              |
   | 第 4 次 → 第 5 次 | 1 小时               |
   | 超过 5 次        | 标记为失败（`notify=-1`） |
4. 通知成功则将 `notify` 置为 0，通知失败则递增 `notify` 并设置下次通知时间

**建议执行频率**：每 1-5 分钟

### 6.6.4 失败通知重试

**调用地址**：`http://你的域名/cron.php?do=notify2&key=你的cronkey`

**处理逻辑**：

1. 查询满足以下条件的订单：
   - 订单完成时间在 1 天以内
   - 通知状态 `notify = -1`（表示常规通知已全部失败）
2. 每次最多处理 **20** 个订单
3. 对每个订单重新尝试发送异步通知
4. 通知成功则将 `notify` 置为 0，通知失败则保持 `notify = -1`

**建议执行频率**：每 30 分钟 - 1 小时

### crontab 配置示例

使用 `crontab -e` 命令编辑计划任务，添加以下内容（请将 `你的域名` 和 `你的cronkey` 替换为实际值）：

```bash
# 自动结算 — 每天凌晨 2 点执行
0 2 * * * curl -s "http://你的域名/cron.php?do=settle&key=你的cronkey" > /dev/null 2>&1

# 订单统计与清理 — 每天凌晨 3 点执行
0 3 * * * curl -s "http://你的域名/cron.php?do=order&key=你的cronkey" > /dev/null 2>&1

# 通知重试 — 每 2 分钟执行一次
*/2 * * * * curl -s "http://你的域名/cron.php?do=notify&key=你的cronkey" > /dev/null 2>&1

# 失败通知重试 — 每 30 分钟执行一次
*/30 * * * * curl -s "http://你的域名/cron.php?do=notify2&key=你的cronkey" > /dev/null 2>&1
```

> **提示**：如果服务器使用宝塔面板，可在"计划任务"功能中添加 URL 定时任务，效果相同。

***

## 6.7 CDN 配置

系统支持 4 种公共 CDN 源，用于加载前端静态资源（如 Bootstrap、jQuery 等）。CDN 选项在后台"系统设置"中配置，对应 [common.php](file:///www/wwwroot/pay/includes/common.php#L91-L99) 中的 `cdnpublic` 参数。

### CDN 选项

| 选项值 | CDN 名称         | CDN 前缀地址                                       | 说明                 |
| --- | -------------- | ---------------------------------------------- | ------------------ |
| 0   | StaticFile CDN | `//cdn.staticfile.org/`                        | 默认选项，由国内七牛云提供，稳定性好 |
| 1   | 宝塔 CDN         | `//lib.baomitu.com/`                           | 宝塔面板旗下 CDN，适合宝塔用户  |
| 2   | BootCDN        | `https://cdn.bootcdn.net/ajax/libs/`           | 国内老牌 CDN 服务，资源丰富   |
| 4   | 字节 CDN         | `//lf26-cdn-tos.bytecdntp.com/cdn/expire-1-M/` | 字节跳动旗下 CDN，国内访问速度快 |

### 配置方式

1. 登录管理后台
2. 进入"系统设置"
3. 找到"公共CDN"选项
4. 从下拉菜单中选择合适的 CDN 源
5. 保存设置

### 选择建议

- **国内服务器**：推荐使用宝塔 CDN（1）或字节 CDN（4），国内访问速度最快
- **海外服务器**：推荐使用 StaticFile CDN（0），海外节点覆盖较好
- **宝塔面板用户**：推荐使用宝塔 CDN（1），与宝塔生态集成更好
- **注意**：使用 `//` 协议前缀的 CDN 地址会自动适配 HTTP/HTTPS，无需额外配置

***

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
- 控制结构（if/else/while/for/switch等）使用K\&R风格（大括号不另起一行）

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

| 类型   | 风格                  | 示例                                                               |
| ---- | ------------------- | ---------------------------------------------------------------- |
| 函数名  | snake\_case         | `changeUserMoney`, `get_curl`, `daddslashes`, `checkRefererHost` |
| 变量名  | snake\_case         | `$userrow`, `$trade_no`, `$clientip`, `$siteurl`                 |
| 类名   | PascalCase          | `PdoHelper`, `PayUtils`, `Plugin`, `Template`                    |
| 类方法  | camelCase           | `getRow`, `getAll`, `getColumn`, `dealPrefix`                    |
| 常量   | UPPER\_SNAKE\_CASE  | `SYSTEM_ROOT`, `DB_VERSION`, `PLUGIN_ROOT`, `TRADE_NO`           |
| 数据库表 | pre\_前缀+snake\_case | `pre_order`, `pre_user`, `pre_config`, `pre_channel`             |
| 静态属性 | snake\_case         | `$info`（插件信息属性）                                                  |

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

| 方法                         | 用途             | 返回值                |
| -------------------------- | -------------- | ------------------ |
| `getRow($sql, $params)`    | 查询一行数据         | 关联数组或false         |
| `getAll($sql, $params)`    | 查询全部数据         | 关联数组或false         |
| `getColumn($sql, $params)` | 查询单个字段值        | 标量值或false          |
| `exec($sql, $params)`      | 执行写操作          | 影响行数或false         |
| `query($sql, $params)`     | 获取PDOStatement | PDOStatement或false |
| `getCount($sql, $params)`  | 获取结果行数         | 整数或false           |
| `lastInsertId()`           | 获取最后插入ID       | 整数                 |

**快捷方法**

| 方法                                                | 用途    | 示例                                                      |
| ------------------------------------------------- | ----- | ------------------------------------------------------- |
| `find($table, $fields, $where, $sort, $limit)`    | 查询一行  | `$DB->find('user', '*', ['uid'=>$uid])`                 |
| `findAll($table, $fields, $where, $sort, $limit)` | 查询全部  | `$DB->findAll('order', '*', ['status'=>0], 'id DESC')`  |
| `findColumn($table, $fields, $where, $sort)`      | 查询单字段 | `$DB->findColumn('user', 'key', ['uid'=>$uid])`         |
| `insert($table, $data)`                           | 插入数据  | `$DB->insert('user', ['email'=>$email])`                |
| `update($table, $data, $where)`                   | 更新数据  | `$DB->update('user', ['money'=>$money], ['uid'=>$uid])` |
| `delete($table, $where)`                          | 删除数据  | `$DB->delete('record', ['id'=>$id])`                    |
| `count($table, $where)`                           | 统计行数  | `$DB->count('order', ['uid'=>$uid])`                    |

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

| 变量               | 类型        | 说明                    | 定义位置       |
| ---------------- | --------- | --------------------- | ---------- |
| `$DB`            | PdoHelper | 数据库操作实例               | common.php |
| `$CACHE`         | Cache     | 缓存操作实例                | common.php |
| `$conf`          | array     | 系统配置（从pre\_config表加载） | common.php |
| `$clientip`      | string    | 客户端IP地址               | member.php |
| `$date`          | string    | 当前日期时间（Y-m-d H:i:s）   | common.php |
| `$siteurl`       | string    | 站点URL（含协议和域名）         | common.php |
| `$cdnpublic`     | string    | 公共CDN地址               | common.php |
| `$password_hash` | string    | 密码哈希盐值                | common.php |
| `$islogin`       | int       | 管理员登录状态（1=已登录）        | member.php |
| `$islogin2`      | int       | 商户登录状态（1=已登录）         | member.php |
| `$userrow`       | array     | 当前登录商户信息              | member.php |
| `$order`         | array     | 当前订单信息（插件上下文）         | Plugin.php |
| `$channel`       | array     | 当前支付通道信息（插件上下文）       | Plugin.php |
| `$ordername`     | string    | 订单显示名称（经替换后）          | Plugin.php |

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

| 常量              | 说明             | 定义位置         |
| --------------- | -------------- | ------------ |
| `SYSTEM_ROOT`   | includes目录绝对路径 | common.php   |
| `ROOT`          | 项目根目录绝对路径      | common.php   |
| `PAYPAGE_ROOT`  | 支付页面模板目录       | common.php   |
| `TEMPLATE_ROOT` | 前台模板目录         | common.php   |
| `PLUGIN_ROOT`   | 插件目录           | common.php   |
| `VERSION`       | 系统版本号          | common.php   |
| `DB_VERSION`    | 数据库版本号         | common.php   |
| `SYS_KEY`       | 系统密钥           | common.php   |
| `DBQZ`          | 数据库表前缀标识       | common.php   |
| `IN_PLUGIN`     | 是否在插件上下文中      | Plugin.php   |
| `PAY_PLUGIN`    | 当前插件名称         | Plugin.php   |
| `PAY_ROOT`      | 当前插件目录绝对路径     | Plugin.php   |
| `TRADE_NO`      | 当前订单号          | Plugin.php   |
| `IN_REFUND`     | 是否在退款上下文中      | Plugin.php   |
| `INDEX_ROOT`    | 当前模板目录绝对路径     | Template.php |
| `STATIC_ROOT`   | 当前模板静态资源URL路径  | Template.php |

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

**表单提交携带csrf\_token**

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

**域名白名单（pay\_domain\_forbid）**

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

| 字段         | 类型         | 必填 | 说明                                                      |
| ---------- | ---------- | -- | ------------------------------------------------------- |
| `name`     | string     | 是  | 插件唯一标识，必须与目录名一致                                         |
| `showname` | string     | 是  | 后台显示的插件名称                                               |
| `author`   | string     | 是  | 插件作者名称                                                  |
| `link`     | string     | 否  | 作者链接                                                    |
| `types`    | array      | 是  | 支持的支付方式，可选值：`alipay`, `wxpay`, `qqpay`, `bank`, `jdpay` |
| `inputs`   | array      | 是  | 通道配置参数定义                                                |
| `select`   | array/null | 否  | 额外下拉选项配置                                                |
| `note`     | string     | 否  | 密钥填写说明，显示在通道配置页面                                        |
| `bindwxmp` | bool       | 否  | 是否支持绑定微信公众号（获取openid）                                   |
| `bindwxa`  | bool       | 否  | 是否支持绑定微信小程序（获取openid）                                   |

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

| 键名          | 说明        |
| ----------- | --------- |
| `appid`     | 应用ID/商户ID |
| `appkey`    | 应用密钥/商户密钥 |
| `appsecret` | 应用Secret  |
| `appurl`    | 接口地址      |
| `appmchid`  | 商户号       |

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

| 方法                                     | 说明                 | 参数                 |
| -------------------------------------- | ------------------ | ------------------ |
| `mapi()`                               | API支付接口            | 无（通过全局变量获取参数）      |
| `jsapi($type, $money, $name, $openid)` | JSAPI支付（微信公众号/小程序） | 支付方式、金额、商品名、openid |
| `refund($order)`                       | 退款接口               | 订单信息数组             |

> **注意**：如果插件未实现`mapi()`方法但实现了`submit()`方法，系统会自动降级为页面跳转方式。

### 7.3.4 返回值格式

插件方法必须返回一个包含`type`字段的关联数组，不同`type`对应不同的处理方式：

**页面支付（submit）返回类型**

| type     | 说明           | 必需字段          | 可选字段   | 处理方式                           |
| -------- | ------------ | ------------- | ------ | ------------------------------ |
| `jump`   | 跳转到URL       | `url`         | -      | JavaScript跳转                   |
| `html`   | 显示HTML内容     | `data`        | -      | 直接输出HTML                       |
| `page`   | 显示支付页面模板     | `page`        | `data` | 包含pages目录下的模板文件                |
| `qrcode` | 扫码支付页面       | `page`, `url` | -      | 显示二维码页面，`$code_url`变量可用        |
| `scheme` | URL Scheme跳转 | `page`, `url` | -      | 显示URL Scheme页面，`$code_url`变量可用 |
| `return` | 同步回调跳转       | `url`         | -      | 跳转到回调URL                       |
| `error`  | 错误提示         | `msg`         | -      | 显示错误信息                         |
| `json`   | JSON数据       | `data`        | -      | 输出JSON数据                       |

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

| type     | 说明           | JSON字段           |
| -------- | ------------ | ---------------- |
| `jump`   | 返回支付URL      | `payurl`         |
| `qrcode` | 返回二维码链接      | `qrcode`         |
| `scheme` | 返回URL Scheme | `urlscheme`      |
| `error`  | 错误           | `code=-2`, `msg` |

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

| 常量            | 说明            | 示例值                                     |
| ------------- | ------------- | --------------------------------------- |
| `INDEX_ROOT`  | 当前模板目录绝对路径    | `/www/wwwroot/pay/template/mytemplate/` |
| `STATIC_ROOT` | 当前模板静态资源URL路径 | `/template/mytemplate/assets/`          |

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

| 变量           | 类型     | 说明      | 示例值                        |
| ------------ | ------ | ------- | -------------------------- |
| `$conf`      | array  | 系统配置数组  | 见下方详细字段                    |
| `$siteurl`   | string | 站点URL   | `https://pay.example.com/` |
| `$cdnpublic` | string | 公共CDN地址 | `//cdn.staticfile.org/`    |

**$conf常用字段**

| 字段                            | 说明              |
| ----------------------------- | --------------- |
| `$conf['sitename']`           | 站点名称            |
| `$conf['title']`              | 页面标题            |
| `$conf['keywords']`           | SEO关键词          |
| `$conf['description']`        | SEO描述           |
| `$conf['template']`           | 当前模板名称          |
| `$conf['test_open']`          | 是否开启支付测试        |
| `$conf['reg_open']`           | 是否开放注册          |
| `$conf['captcha_open_login']` | 登录是否开启验证码       |
| `$conf['captcha_id']`         | 极验验证ID          |
| `$conf['captcha_key']`        | 极验验证Key         |
| `$conf['user_review']`        | 商户是否需要审核        |
| `$conf['verifytype']`         | 验证方式（0=邮箱，1=手机） |

**用户状态变量**

| 变量          | 类型         | 说明           |
| ----------- | ---------- | ------------ |
| `$islogin`  | int        | 管理员是否登录（1=是） |
| `$islogin2` | int        | 商户是否登录（1=是）  |
| `$userrow`  | array/null | 当前登录商户信息     |

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

***

# 八、功能模块扩展点说明

聚合易支付采用插件化架构设计，核心业务逻辑与具体支付实现解耦，开发者可以通过扩展支付插件、首页模板、支付方式、用户组、实名认证和转账通道等模块来增强系统功能。本章将详细说明每个扩展点的机制、接口定义和开发步骤。

***

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

**步骤三：定义** **`$info`** **静态属性**

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

***

### 8.1.2 插件接口定义

所有支付插件必须以 PHP 类的形式实现，类名为 `\\{插件名}_plugin`（带全局命名空间前缀），所有方法均为 `static public`。

#### `submit()` — 页面支付提交

```php
static public function submit()
```

**调用时机**：用户在浏览器中发起支付请求，系统加载插件后首先调用此方法。

**可用全局变量**：`$siteurl`, `$channel`, `$order`, `$ordername`, `$sitename`, `$conf`

**返回值格式**：返回一个关联数组，通过 `type` 字段区分不同的响应类型：

| type 值   | 含义         | 附加字段                             | 说明                                |
| -------- | ---------- | -------------------------------- | --------------------------------- |
| `jump`   | URL跳转      | `url` — 跳转地址                     | 浏览器直接跳转到支付页面                      |
| `html`   | 输出HTML     | `data` — HTML内容                  | 直接输出表单自动提交的HTML                   |
| `page`   | 渲染页面       | `page` — 页面文件名，`data` — 传递给页面的变量 | 渲染 `includes/pages/{page}.php` 页面 |
| `qrcode` | 扫码支付       | `url` — 二维码内容，`page` — 展示页面      | 显示二维码支付页面                         |
| `scheme` | URL Scheme | `url` — scheme地址，`page` — 展示页面   | 微信小程序 scheme 跳转                   |
| `return` | 同步回调       | `url` — 回调地址                     | 直接执行同步回调跳转                        |
| `error`  | 错误提示       | `msg` — 错误信息                     | 显示错误提示页面                          |

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
4. 调用 `processReturn($order, $api_trade_no, $buyer)` 完成同步回调处理（该方法会自动跳转到商户的 return\_url）

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

**`$device`** **和** **`$mdevice`** **说明**：

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

| 参数        | 类型             | 说明                        |
| --------- | -------------- | ------------------------- |
| `$type`   | string         | 支付方式名称，如 `wxpay`、`alipay` |
| `$money`  | float          | 支付金额（元）                   |
| `$name`   | string         | 商品名称                      |
| `$openid` | string \| null | 用户的 OpenID（微信支付时必填）       |

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

| 参数       | 类型    | 说明              |
| -------- | ----- | --------------- |
| `$order` | array | 订单信息数组，包含以下关键字段 |

**`$order`** **关键字段**：

| 字段             | 说明      |
| -------------- | ------- |
| `trade_no`     | 系统订单号   |
| `api_trade_no` | 支付网关交易号 |
| `realmoney`    | 实际支付金额  |
| `refundmoney`  | 退款金额    |

**返回值格式**：

```php
// 退款成功
return ['code'=>0, 'trade_no'=>'网关交易号', 'refund_fee'=>'退款金额(分)', 'refund_time'=>'退款时间'];

// 退款失败（业务级错误，如余额不足）
return ['code'=>-1, 'msg'=>'错误描述'];
```

**注意**：退款方法执行前，系统会定义 `IN_REFUND` 常量（而非 `IN_PLUGIN`），插件可通过此常量区分当前执行上下文。

***

### 8.1.3 插件可用全局变量

以下全局变量在插件方法执行前由系统自动注入，插件可直接通过 `global` 关键字访问：

| 变量名          | 类型     | 说明                                                                                                                       | 可用方法                                 |
| ------------ | ------ | ------------------------------------------------------------------------------------------------------------------------ | ------------------------------------ |
| `$siteurl`   | string | 当前站点URL，格式如 `https://pay.example.com/`                                                                                   | submit, mapi                         |
| `$channel`   | array  | 当前支付通道配置，包含 `appid`、`appkey`、`appsecret`、`appurl`、`appmchid`、`apptype`、`mode`、`appwxmp`、`appwxa` 等字段                     | 所有方法                                 |
| `$order`     | array  | 当前订单信息，包含 `trade_no`、`out_trade_no`、`uid`、`type`、`channel`、`name`、`money`、`realmoney`、`getmoney`、`typename`、`status` 等字段 | submit, notify, return, mapi, refund |
| `$ordername` | string | 订单显示名称（经过 `ordername_replace` 处理后的名称）                                                                                    | submit                               |
| `$conf`      | array  | 系统配置（`pre_config` 表的键值对），常用字段包括 `sitename`、`localurl`、`transfer_name`、`transfer_desc`、`ordername` 等                      | submit, mapi                         |
| `$clientip`  | string | 客户端IP地址                                                                                                                  | submit, mapi                         |
| `$sitename`  | string | 商户站点名称（即 `$conf['sitename']`）                                                                                            | submit                               |
| `$device`    | string | 客户端设备类型：`pc` 或 `mobile`                                                                                                  | mapi                                 |
| `$mdevice`   | string | 移动端内嵌环境：`wechat`、`alipay`、`qq` 或空                                                                                        | mapi                                 |

**`$channel`** **通道配置字段详解**：

| 字段          | 说明                                                                     |
| ----------- | ---------------------------------------------------------------------- |
| `id`        | 通道ID                                                                   |
| `plugin`    | 插件名称                                                                   |
| `type`      | 支付方式ID（对应 `pre_type.id`）                                               |
| `name`      | 通道显示名称                                                                 |
| `rate`      | 通道费率                                                                   |
| `appid`     | 应用ID/商户ID（由 `$info['inputs']` 定义）                                      |
| `appkey`    | 应用密钥（由 `$info['inputs']` 定义）                                           |
| `appsecret` | 应用密钥2（由 `$info['inputs']` 定义）                                          |
| `appurl`    | 接口地址/子商户号（由 `$info['inputs']` 定义）                                      |
| `appmchid`  | 商户号/授权token（由 `$info['inputs']` 定义）                                    |
| `apptype`   | 支付类型（逗号分隔的字符串，如 `"1,2,3"`，需用 `explode(',',$channel['apptype'])` 解析为数组） |
| `mode`      | 手续费扣除模式：`0`=余额扣费，`1`=订单加费                                              |
| `appwxmp`   | 绑定的微信公众号ID（对应 `pre_weixin.id`）                                         |
| `appwxa`    | 绑定的微信小程序ID（对应 `pre_weixin.id`）                                         |
| `appswitch` | 自定义开关字段（由 `$info['inputs']` 定义）                                        |

**`$channel`** **的** **`channelinfo`** **覆盖机制**：当商户在 `pre_user.channelinfo` 字段中配置了通道参数覆盖时，系统会自动替换通道配置中对应字段的值。覆盖规则：如果通道字段值以 `[` 开头并以 `]` 结尾（如 `[key1]`），则从 `channelinfo` JSON 中取对应键名的值替换。

***

### 8.1.4 插件可用常量

系统在加载插件时（`Plugin::loadClass()` 方法中）会定义以下常量：

| 常量名          | 类型     | 说明                                             | 定义位置                |
| ------------ | ------ | ---------------------------------------------- | ------------------- |
| `IN_PLUGIN`  | bool   | 标识当前运行在插件上下文中（值为 `true`）                       | Plugin::loadClass() |
| `PAY_PLUGIN` | string | 当前插件名称（如 `epay`、`wxpaysl`）                     | Plugin::loadClass() |
| `PAY_ROOT`   | string | 插件根目录的绝对路径（如 `/www/wwwroot/pay/plugins/epay/`） | Plugin::loadClass() |
| `TRADE_NO`   | string | 系统订单号（19位数字，格式 `YmdHis`+5位随机数）                 | Plugin::loadClass() |

**特殊常量**：

| 常量名           | 说明                                                       |
| ------------- | -------------------------------------------------------- |
| `IN_REFUND`   | 退款上下文中定义（值为 `true`），此时 `IN_PLUGIN` 不定义                   |
| `PLUGIN_ROOT` | 插件总目录（在 `common.php` 中定义，值为 `/www/wwwroot/pay/plugins/`） |
| `PLUGIN_PATH` | USDT插件自定义的插件路径常量                                         |

**使用示例**：

```php
// 引入插件内部文件
require(PAY_ROOT."inc/config.php");
require(PAY_ROOT."inc/PayApi.php");

// 构建回调URL
$notify_url = $conf['localurl'].'pay/notify/'.TRADE_NO.'/';
$return_url = $siteurl.'pay/return/'.TRADE_NO.'/';
```

***

### 8.1.5 插件可用函数

以下全局函数可在插件中直接调用：

#### `processNotify($order, $api_trade_no=null, $buyer=null)`

处理异步通知，完成订单状态更新、商户余额变动、回调通知等。

| 参数              | 类型             | 说明                      |
| --------------- | -------------- | ----------------------- |
| `$order`        | array          | 订单信息数组                  |
| `$api_trade_no` | string \| null | 支付网关交易号                 |
| `$buyer`        | string \| null | 买家标识（如支付宝买家ID、微信OpenID） |

**内部逻辑**：调用 `Payment::processOrder(true, $order, $api_trade_no, $buyer)`，该方法会：

1. 将订单状态从 `0`（未支付）更新为 `1`（已支付）
2. 记录支付完成时间和第三方交易号
3. 调用 `processOrder()` 执行后续业务（商户余额变动、异步通知商户等）

#### `processReturn($order, $api_trade_no=null, $buyer=null)`

处理同步回调，完成订单状态更新后跳转到商户的 `return_url`。

| 参数              | 类型             | 说明      |
| --------------- | -------------- | ------- |
| `$order`        | array          | 订单信息数组  |
| `$api_trade_no` | string \| null | 支付网关交易号 |
| `$buyer`        | string \| null | 买家标识    |

**内部逻辑**：调用 `Payment::processOrder(false, $order, $api_trade_no, $buyer)`，该方法会：

1. 执行与 `processNotify` 相同的订单更新逻辑
2. 额外执行同步跳转：将用户浏览器重定向到商户的 `return_url`
3. 如果支付完成超过5分钟，则跳转到 `/payok.html` 而非商户回调地址

#### `showerror($msg)`

在聚合收款码场景中显示错误页面并终止程序。

| 参数     | 类型     | 说明   |
| ------ | ------ | ---- |
| `$msg` | string | 错误信息 |

**注意**：此函数定义在 `paypage/inc.php` 中，仅在聚合收款码流程中可用。

#### `sysmsg($msg, $title='站点提示信息')`

显示系统级错误提示页面并终止程序。

| 参数       | 类型     | 说明           |
| -------- | ------ | ------------ |
| `$msg`   | string | 错误信息（支持HTML） |
| `$title` | string | 页面标题         |

#### `showerrorjson($msg)`

输出 JSON 格式的错误信息并终止程序。

| 参数     | 类型     | 说明   |
| ------ | ------ | ---- |
| `$msg` | string | 错误信息 |

**输出格式**：`{"code":-1,"msg":"错误信息"}`

#### 其他常用函数

| 函数名                                                | 说明                                                        |
| -------------------------------------------------- | --------------------------------------------------------- |
| `daddslashes($string)`                             | 递归对字符串进行 `addslashes` 转义，防止SQL注入                          |
| `checkmobile()`                                    | 检测当前是否为移动端访问，返回 `bool`                                    |
| `is_https()`                                       | 检测当前是否为HTTPS访问，返回 `bool`                                  |
| `get_curl($url, $post, ...)`                       | 通用HTTP请求函数，支持POST、Cookie、Header等参数                        |
| `curl_get($url)`                                   | 简单的GET请求函数，使用系统代理配置                                       |
| `checkBlockUser($openid, $trade_no)`               | 检查用户是否在黑名单中，返回 `false` 或 `['type'=>'error','msg'=>'...']` |
| `ordername_replace($name, $oldname, $uid, $order)` | 替换订单名称中的占位符：`[name]`、`[order]`、`[qq]`、`[time]`            |
| `real_ip($type=0)`                                 | 获取客户端真实IP地址                                               |

***

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

**`$info`** **属性各字段详解**：

| 字段         | 类型            | 必填 | 说明                                                                             |
| ---------- | ------------- | -- | ------------------------------------------------------------------------------ |
| `name`     | string        | 是  | 插件英文名称，必须与目录名一致，全局唯一                                                           |
| `showname` | string        | 是  | 插件显示名称，在管理后台展示                                                                 |
| `author`   | string        | 是  | 插件作者                                                                           |
| `link`     | string        | 否  | 作者链接                                                                           |
| `types`    | array         | 是  | 支持的支付方式列表，值对应 `pre_type.name`，如 `alipay`、`wxpay`、`qqpay`、`bank`、`jdpay`、`usdt` |
| `inputs`   | array         | 是  | 插件配置参数定义，键名对应通道表的字段名                                                           |
| `select`   | array \| null | 否  | 支付方式选择项，键为编号，值为显示名称。配置后通道管理页会显示多选框                                             |
| `note`     | string        | 否  | 配置页面的说明HTML，支持富文本                                                              |
| `bindwxmp` | bool          | 否  | 是否支持绑定微信公众号，默认 `false`                                                         |
| `bindwxa`  | bool          | 否  | 是否支持绑定微信小程序，默认 `false`                                                         |

**`$info['inputs']`** **配置参数定义**：

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

***

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

| 常量              | 说明                        | 示例值                                 |
| --------------- | ------------------------- | ----------------------------------- |
| `INDEX_ROOT`    | 当前模板目录的绝对路径               | `/www/wwwroot/pay/template/index1/` |
| `STATIC_ROOT`   | 当前模板静态资源URL路径             | `/template/index1/assets/`          |
| `TEMPLATE_ROOT` | 模板总目录（在 `common.php` 中定义） | `/www/wwwroot/pay/template/`        |

**模板检测**：`Template::exists($template)` 方法通过检查 `template/{name}/index.php` 是否存在来判断模板是否有效。

**模板列表**：`Template::getList()` 方法扫描 `template/` 目录下的所有子目录（排除含 `.` 的名称），返回可用模板列表。

### 8.2.3 可用变量与函数

模板文件中可直接使用以下变量和函数：

**全局变量**：

| 变量           | 类型     | 说明                                                                                                  |
| ------------ | ------ | --------------------------------------------------------------------------------------------------- |
| `$conf`      | array  | 系统配置，常用字段：`sitename`、`title`、`keywords`、`description`、`orgname`、`kfqq`、`email`、`footer`、`test_open` |
| `$cdnpublic` | string | 公共CDN地址前缀，如 `//cdn.staticfile.org/`                                                                 |

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

***

## 8.3 支付方式扩展指南

支付方式（Payment Type）是系统对支付渠道的抽象分类，如"支付宝"、"微信支付"、"QQ钱包"等。每种支付方式在 `pre_type` 表中有一条记录，通过 `id` 和 `name` 标识。

### 8.3.1 在 pre\_type 表新增支付方式

`pre_type` 表结构：

| 字段         | 类型          | 说明                                       |
| ---------- | ----------- | ---------------------------------------- |
| `id`       | int(11)     | 自增主键，支付方式ID                              |
| `name`     | varchar(30) | 支付方式英文名称（唯一标识，如 `alipay`、`wxpay`、`usdt`） |
| `device`   | int(1)      | 设备限制：`0`=全部、`1`=PC端、`2`=移动端              |
| `showname` | varchar(30) | 支付方式显示名称（如"支付宝"、"微信支付"）                  |
| `status`   | tinyint(1)  | 启用状态：`0`=禁用、`1`=启用                       |

**系统预置的支付方式**（来自 `install.sql`）：

| id | name   | showname | status |
| -- | ------ | -------- | ------ |
| 1  | alipay | 支付宝      | 1      |
| 2  | wxpay  | 微信支付     | 1      |
| 3  | qqpay  | QQ钱包     | 1      |
| 4  | bank   | 网银支付     | 0      |
| 5  | jdpay  | 京东支付     | 0      |
| 6  | paypal | PayPal   | 0      |

**新增支付方式的SQL示例**：

```sql
INSERT INTO `pre_type` (`name`, `device`, `showname`, `status`) VALUES ('usdt', 0, 'USDT支付', 1);
```

**注意事项**：

- `name` 字段必须与插件的 `$info['types']` 数组中的值一致
- `name` 字段有联合索引 `KEY name (name, device)`，确保同一设备类型下名称唯一
- 新增后需要在用户组配置中添加对应的通道和费率，否则商户无法使用

### 8.3.2 在插件的 $info\['types'] 属性中声明支持

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

***

## 8.4 用户组扩展指南

用户组（User Group）是聚合易支付中控制商户可用支付方式、通道分配和费率的核心机制。每个商户属于一个用户组，用户组决定了商户可以使用哪些支付方式、走哪个通道、以及费率是多少。

### 8.4.1 pre\_group 表配置说明

`pre_group` 表结构：

| 字段            | 类型            | 说明                     |
| ------------- | ------------- | ---------------------- |
| `gid`         | int(11)       | 用户组ID，`0` 为默认用户组       |
| `name`        | varchar(30)   | 用户组名称                  |
| `info`        | varchar(1024) | 通道与费率配置JSON            |
| `isbuy`       | tinyint(1)    | 是否允许购买：`0`=否、`1`=是     |
| `price`       | decimal(10,2) | 购买价格                   |
| `sort`        | int(10)       | 排序值                    |
| `expire`      | int(10)       | 有效期（天），`0`=永久          |
| `settle_open` | int(1)        | 结算开关覆盖：`0`=跟随系统、`1`=开启 |
| `settle_type` | int(1)        | 结算方式覆盖                 |
| `settings`    | text          | 其他设置JSON               |

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

| 字段        | 说明                                            |
| --------- | --------------------------------------------- |
| `type`    | 通道类型：空字符串=普通通道、`roll`=轮询组                     |
| `channel` | 通道分配：`-1`=随机可用通道、`0`=关闭该支付方式、正整数=指定通道ID或轮询组ID |
| `rate`    | 费率（百分比）：空字符串=使用通道默认费率、具体数值如 `98.00` 表示商户拿到98% |

**通道选择逻辑**（由 `Channel::getSubmitInfo()` 实现）：

1. **`channel = 0`**：该支付方式对当前用户组关闭，返回 `false`
2. **`channel = -1`**：随机选择一个该支付方式下状态为开启的通道，支持金额限额过滤（`paymin`/`paymax`）
3. **`channel = 正整数`**：
   - 如果 `type = "roll"`：从轮询组中按规则选择通道
   - 否则：使用指定ID的通道
4. **`rate`** **为空**：使用通道自身的默认费率（`pre_channel.rate`）
5. **`rate`** **有值**：使用用户组配置的费率覆盖通道默认费率

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

**轮询组** **`info`** **格式示例**：

```
5:3,8:2,12:5
```

表示包含3个通道：通道5权重3、通道8权重2、通道12权重5。

### 8.4.3 用户组购买功能配置

用户组支持购买功能，商户可以通过支付购买升级到更高级的用户组：

| 字段       | 说明             |
| -------- | -------------- |
| `isbuy`  | 设置为 `1` 允许购买   |
| `price`  | 购买价格（元）        |
| `expire` | 有效期天数，`0` 表示永久 |

**购买流程**：

1. 商户在前端选择要购买的用户组
2. 系统创建一笔 `tid=4`（购买用户组）的订单
3. 商户完成支付后，`processOrder()` 函数检测到 `tid=4`，执行用户组变更
4. 调用 `changeUserGroup($uid, $gid, $endtime)` 更新商户的用户组

**用户组购买页面**：`user/groupbuy.php`，展示各用户组的通道和费率信息。

***

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

| 字段             | 类型          | 说明                                         |
| -------------- | ----------- | ------------------------------------------ |
| `cert`         | tinyint(4)  | 认证状态：`0`=未认证、`1`=已认证                       |
| `certtype`     | tinyint(4)  | 认证类型：`0`=个人、`1`=企业                         |
| `certmethod`   | tinyint(4)  | 认证方式：`0`=支付宝快捷、`1`=微信快捷、`2`=手机三要素、`3`=人工审核 |
| `certno`       | varchar(18) | 身份证号码                                      |
| `certname`     | varchar(32) | 真实姓名                                       |
| `certtime`     | datetime    | 认证时间                                       |
| `certtoken`    | varchar(64) | 认证令牌（支付宝认证时的 verify\_id）                   |
| `certcorpno`   | varchar(30) | 营业执照号码（企业认证）                               |
| `certcorpname` | varchar(80) | 公司名称（企业认证）                                 |

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

***

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

| 参数                 | 类型     | 说明                                   |
| ------------------ | ------ | ------------------------------------ |
| `$type`            | string | 转账类型：`alipay`、`wxpay`、`qqpay`、`bank` |
| `$channel`         | array  | 支付通道配置数组（来自 `Channel::get()`）        |
| `$out_trade_no`    | string | 商户转账订单号                              |
| `$payee_account`   | string | 收款人账号（支付宝账号/用户ID、微信OpenID、QQ号、银行卡号）  |
| `$payee_real_name` | string | 收款人真实姓名（可为空，为空时不校验姓名）                |
| `$money`           | float  | 转账金额（元）                              |

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

**`code`** **字段含义**：

| 值    | 含义                           |
| ---- | ---------------------------- |
| `0`  | 接口调用成功，需进一步检查 `ret` 判断转账是否成功 |
| `-1` | 接口调用失败或系统异常，可重试              |

### 8.6.3 新增转账通道的步骤

要新增一种转账通道（如新增银联转账），需要以下步骤：

**步骤一：在** **`transfer_do()`** **函数中添加新的类型分支**

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

***

# 九、常见问题解决方案

## 9.1 支付相关问题

### 9.1.1 签名验证失败

**症状**：提交支付时页面提示"签名校验失败，请返回重试！"

**原因分析**：

签名验证失败是接入过程中最常见的问题，其核心逻辑位于 `submit.php` 第32-37行。系统首先对请求参数执行三步预处理（过滤空值和sign/sign\_type参数 → 按key的ASCII升序排序 → 拼接为key=value&格式字符串），然后将拼接字符串与商户密钥拼接后做MD5运算，将结果与请求中的sign参数进行比对。任何一个环节不一致都会导致验证失败。

1. **密钥(key)配置错误**：商户在请求中使用的pid对应的密钥与数据库 `pre_user` 表中存储的key不一致，可能是复制时多了空格、遗漏字符或使用了旧密钥。
2. **参数编码问题**：对参数值做了URL编码后再参与签名计算。签名时应使用原始值，URL编码仅在最终拼接到回调URL时使用（参见 `PayUtils::createLinkstringUrlencode` 方法）。
3. **参数排序不正确**：未按参数名的ASCII码升序排列。系统使用 `ksort()` 函数排序，必须严格按字典序。
4. **空值未过滤**：签名前未移除空值参数和sign、sign\_type参数。`PayUtils::paraFilter()` 方法会过滤掉 `key=="sign"`、`key=="sign_type"` 以及 `val==""` 的参数。
5. **参数名称拼写错误**：如将 `out_trade_no` 写成 `out_trade_no` 以外的其他形式，或pid/type/money等参数名写错，导致签名串不一致。

**解决方案**：

1. **核对商户密钥**：登录管理后台，确认商户ID（pid）对应的密钥（key）与请求中使用的完全一致，注意去除首尾空格。
2. **确保参数值未做URL编码**：签名时使用参数的原始值，不要对值进行 `urlencode()` 处理。系统在回调通知中会使用 `createLinkstringUrlencode()` 单独对URL参数编码，签名计算与URL编码是独立的。
3. **确保参数按key的ASCII升序排序**：使用 `ksort()` 函数或等效的字典序排序算法。例如参数 `pid=1001&money=1.00&type=alipay` 排序后应为 `money=1.00&pid=1001&type=alipay`。
4. **签名前过滤空值和sign/sign\_type参数**：移除值为空的参数，移除参数名为 `sign` 和 `sign_type` 的参数，然后再排序和拼接。
5. **签名调试方法**：按以下步骤手动验证签名：
   ```
   步骤1：准备所有参数（排除sign和sign_type，排除空值）
   步骤2：按参数名ASCII升序排序
   步骤3：用&拼接为 key1=value1&key2=value2 格式
   步骤4：在拼接串末尾直接追加商户密钥（无分隔符）
   步骤5：对整个字符串做MD5运算，得到32位小写签名
   ```
   示例：参数 `pid=1001&money=1.00&type=alipay&out_trade_no=TEST001`，密钥为 `abc123`，则签名字符串为 `money=1.00&out_trade_no=TEST001&pid=1001&type=alipayabc123`，对此字符串做MD5即为sign值。

***

### 9.1.2 回调通知未收到

**症状**：订单已支付成功，但商户系统始终未收到异步通知（notify\_url未被调用）

**原因分析**：

回调通知的发送逻辑位于 `functions.php` 的 `creat_callback()` 和 `do_notify()` 函数。系统在订单支付成功后，通过 `curl_get()` 函数以GET方式请求商户的 `notify_url`，并期望商户返回包含"success"（不区分大小写）的字符串。如果商户未返回"success"，系统会按照1分钟、3分钟、20分钟、1小时、2小时的间隔进行最多5次重试。

1. **notify\_url无法从公网访问**：商户填写的通知地址是内网地址（如 `http://localhost/`、`http://192.168.x.x/`）或域名未正确解析，导致支付平台服务器无法发起请求。
2. **商户服务器未返回"success"**：`do_notify()` 函数检查响应中是否包含 `success`、`SUCCESS` 或 `Success` 字符串。如果商户回调页面返回的是其他内容（如"ok"、"1"、JSON数据等），系统会认为通知失败并触发重试。
3. **HTTPS证书问题**：如果notify\_url使用HTTPS协议，但证书过期、自签名或域名不匹配，`curl_get()` 虽然设置了 `CURLOPT_SSL_VERIFYPEER=false`，但某些中间网络设备可能仍会拦截。
4. **防火墙/安全组拦截**：商户服务器防火墙或云服务商安全组规则未放行来自支付平台服务器的IP地址。
5. **curl请求超时**：`curl_get()` 设置了5秒超时（`CURLOPT_TIMEOUT=5`），如果商户回调处理逻辑耗时过长，会导致请求超时。

**解决方案**：

1. **检查notify\_url公网可达性**：在支付平台服务器上执行 `curl -v "商户notify_url"` 验证是否能正常访问。确保使用公网域名或IP，而非localhost或内网地址。
2. **确保回调处理返回"success"**：商户回调页面在处理完业务逻辑后，必须输出纯文本 `success`（不区分大小写），不要输出其他任何内容（包括HTML标签、空格、换行等）。
3. **检查HTTPS证书有效性**：使用 `curl -v "https://商户notify_url"` 测试，确保证书有效且未过期。如有问题，可临时使用HTTP协议或更换证书。
4. **检查服务器防火墙设置**：确认服务器80/443端口对支付平台服务器IP开放，检查云服务商安全组规则。
5. **手动触发通知重试**：访问 `http://支付平台域名/cron.php?do=notify&key=监控密钥` 手动触发通知重试。系统会查找 `notify>0` 且 `notifytime<当前时间` 的订单进行重新通知。
6. **重试已放弃的通知**：对于已重试5次后标记为 `notify=-1` 的订单，可访问 `cron.php?do=notify2&key=监控密钥` 再次尝试通知。
7. **检查回调处理耗时**：建议商户回调页面先输出"success"再执行业务逻辑，或使用异步处理，确保在5秒内响应。

***

### 9.1.3 通道不可用

**症状**：提交支付时提示"当前支付方式无法使用"或跳转到收银台页面显示无可用支付方式

**原因分析**：

通道分配逻辑位于 `Channel.php` 的 `submit()` 和 `getSubmitInfo()` 方法。系统根据商户用户组（gid）配置的通道映射关系，从 `pre_channel` 表中选择可用的支付通道。当所有条件均不满足时，`getSubmitInfo()` 返回 `false`，`submit.php` 中会将用户跳转到收银台并标记 `other=1`。

1. **通道status=0（已关闭）**：`pre_channel` 表中该支付方式对应的通道 `status` 字段为0，表示管理员手动关闭了该通道。系统查询时条件为 `status=1`，关闭的通道不会被选中。
2. **通道daystatus=1（日限额已满）**：当通道的 `daytop`（日限额）配置大于0时，系统会在订单完成后累计当日交易金额（见 `processOrder()` 函数第567-576行），当累计金额达到 `daytop` 时自动将 `daystatus` 设为1。查询条件包含 `daystatus=0`，因此日限额已满的通道不会被选中。
3. **用户组配置channel=0（已关闭）**：`pre_group` 表的 `info` 字段为JSON格式，包含各支付方式的通道配置。当某支付方式的 `channel=0` 时，表示该用户组关闭了此支付方式，`getSubmitInfo()` 直接返回 `false`。
4. **金额超出通道限额**：通道的 `paymin`（单笔最小金额）和 `paymax`（单笔最大金额）限制了可接受的支付金额。系统会过滤掉金额不在范围内的通道。
5. **无可用通道**：`pre_channel` 表中不存在 `type` 匹配且 `status=1 AND daystatus=0` 的记录，或所有匹配通道的限额都不满足。

**解决方案**：

1. **检查通道状态**：登录管理后台，查看对应支付方式的通道列表，确认至少有一个通道的 `status=1`。也可直接查询数据库：
   ```sql
   SELECT id, name, status, daystatus, paymin, paymax FROM pre_channel WHERE type=支付方式ID;
   ```
2. **检查日限额状态**：如果 `daystatus=1`，说明当日交易额已达到 `daytop` 限额。可等待次日自动重置（`cron.php?do=order` 任务会将所有通道 `daystatus` 重置为0），或临时调高 `daytop` 值。
3. **检查用户组配置**：查看商户所属用户组的通道配置，确认对应支付方式的 `channel` 不为0：
   ```sql
   SELECT info FROM pre_group WHERE gid=商户用户组ID;
   ```
   JSON中对应支付方式ID的 `channel` 值为0表示关闭，-1表示随机可用通道，正整数表示指定通道ID。
4. **检查通道限额**：确认支付金额在通道的 `paymin` 和 `paymax` 范围内。`submit.php` 第143-148行会单独检查限额并给出提示。
5. **确保至少有一个可用通道**：在 `pre_channel` 表中为该支付方式创建至少一个 `status=1` 的通道，并配置正确的支付插件（plugin）。

***

### 9.1.4 金额不匹配

**症状**：实际支付金额与订单金额不一致，或商户收到的结算金额与预期不符

**原因分析**：

金额计算逻辑位于 `submit.php` 第126-132行和第154-158行。系统根据商户的 `mode` 字段区分两种费率模式，并可能开启随机增减金额功能。

1. **订单加费模式（mode=1）**：当商户的 `mode=1` 时，采用订单加费模式。实际支付金额（realmoney）= 订单金额 × (200 - 费率) / 100，商户到账金额（getmoney）= 订单金额。例如订单1元、费率2%，则实际支付1.98元，商户到账1元。这与默认模式（mode=0）的计算方式不同：默认模式下实际支付金额=订单金额，商户到账=订单金额 × 费率 / 100。
2. **随机增减金额功能**：当系统配置 `pay_payaddstart`（起始金额）、`pay_payaddmin`（最小增减）、`pay_payaddmax`（最大增减）均不为0且实际支付金额达到 `pay_payaddstart` 时，系统会在实际支付金额上增加一个随机小数（精确到2位），用于区分不同订单。例如配置增减0.01-0.10元，1元的订单实际支付可能是1.05元。
3. **浮点数精度问题**：金额计算使用了 `round()` 函数保留2位小数，但在极端情况下浮点数运算可能产生精度偏差。

**解决方案**：

1. **了解两种费率模式的区别**：
   - **默认模式（mode=0）**：用户支付金额=订单金额，商户到账=订单金额×费率/100。例如1元订单、2%费率，用户支付1元，商户到账0.98元。
   - **加费模式（mode=1）**：用户支付金额=订单金额×(200-费率)/100，商户到账=订单金额。例如1元订单、2%费率，用户支付1.98元，商户到账1元。
2. **检查随机增减金额配置**：如不需要此功能，将 `pay_payaddstart`、`pay_payaddmin`、`pay_payaddmax` 配置为0。如需使用，注意回调通知中的 `money` 字段为原始订单金额（非增减后的金额），实际支付金额存储在 `realmoney` 字段中。
3. **回调通知中的金额字段**：`creat_callback()` 函数构造回调参数时，`money` 字段使用的是 `$data['money']`（原始订单金额），而非 `$data['realmoney']`（实际支付金额）。商户系统应以回调中的 `money` 字段为准进行订单核对。

***

### 9.1.5 订单重复支付

**症状**：提交支付时提示"该订单(xxx)已完成支付，请勿重复发起支付"或"该订单(xxx)支付参数有变化，请更换订单号重新发起支付"

**原因分析**：

订单重复提交检测逻辑位于 `submit.php` 第95-111行。系统根据商户ID（uid）和商户订单号（out\_trade\_no）查询已有订单，如果找到且在10天（864000秒）内，则进行以下判断：

1. **同一out\_trade\_no已支付成功**：如果旧订单的 `status>0`（已支付），系统直接拒绝并提示"该订单已完成支付，请勿重复发起支付"。
2. **24小时内同订单号参数变更**：如果旧订单未支付但参数（金额、商品名、通知地址、回调地址、附加参数）任一发生变化，系统拒绝并提示"支付参数有变化，请更换订单号重新发起支付"。

**解决方案**：

1. **商户系统应防止重复提交**：在用户支付完成后，商户系统应标记订单为已支付状态，避免用户再次点击支付按钮。前端可在支付跳转后禁用支付按钮，后端应在创建订单前检查本地订单状态。
2. **更换订单号重新发起**：如果确实需要重新发起支付（如参数有误），必须使用新的 `out_trade_no` 订单号。系统会为新订单号创建新的支付记录。
3. **注意订单号有效期**：同一商户ID+订单号的关联有效期为10天（864000秒），超过10天后同一订单号会被视为新订单。建议商户系统使用唯一且不重复的订单号生成策略。
4. **订单号格式要求**：`out_trade_no` 仅允许字母、数字、点号、下划线、连字符和竖线（正则：`/^[a-zA-Z0-9.\_\-|]+$/`），不符合格式会提示"订单号格式不正确"。

***

## 9.2 部署相关问题

### 9.2.1 URL重写不生效

**症状**：访问 `/pay/xxx` 或 `/xxx.html` 格式的URL返回404错误

**原因与解决方案**：

系统使用URL重写实现友好的URL格式，`.htaccess` 文件定义了两条重写规则：

- `^(.[a-zA-Z0-9\-\_]+).html$` → `index.php?mod=$1`（页面路由）
- `^pay/(.*)$` → `pay.php?s=$1`（支付链接路由）

**Apache环境**：

1. **未启用mod\_rewrite模块**：编辑Apache配置文件（通常是 `httpd.conf`），取消 `LoadModule rewrite_module modules/mod_rewrite.so` 前的注释，重启Apache。
2. **AllowOverride设置不当**：确保虚拟主机配置中 `AllowOverride All` 或至少 `AllowOverride FileInfo`，否则 `.htaccess` 文件不会被解析。
3. **.htaccess文件权限问题**：确保 `.htaccess` 文件存在且Web服务器有读取权限（644权限即可）。

**Nginx环境**：

参考项目提供的 `nginx.txt` 配置，在server块中添加：

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

注意 `/plugins` 和 `/includes` 目录必须禁止外部访问，防止敏感文件泄露。

**IIS环境**：

1. 安装URL Rewrite模块（从微软官网下载）。
2. 在 `web.config` 中配置对应的重写规则，将 `.html` 请求映射到 `index.php?mod=xxx`，`/pay/` 请求映射到 `pay.php?s=xxx`。

***

### 9.2.2 数据库连接失败

**症状**：页面显示"链接数据库失败"或"你还没安装！"

**原因与解决方案**：

数据库配置位于 `config.php`，系统在 `common.php` 第53-67行进行数据库连接检测。

1. **config.php配置错误**：检查 `/config.php` 中的数据库配置项：
   ```php
   $dbconfig=array(
       'host' => 'localhost',   // 数据库服务器地址
       'port' => 3306,          // 数据库端口
       'user' => '用户名',       // 数据库用户名
       'pwd'  => '密码',        // 数据库密码
       'dbname' => '数据库名',   // 数据库名称
       'dbqz' => 'pay'          // 数据表前缀
   );
   ```
   确认各项值正确，特别注意 `host` 和 `port` 是否与实际MySQL服务一致。
2. **MySQL服务未启动**：执行 `systemctl status mysql` 或 `service mysql status` 检查MySQL服务状态，如未启动则执行 `systemctl start mysql`。
3. **数据库用户权限不足**：确认数据库用户拥有对指定数据库的SELECT、INSERT、UPDATE、DELETE、CREATE、ALTER等权限。可通过 `mysql -u用户名 -p -e "SHOW GRANTS"` 查看。
4. **端口配置错误**：默认MySQL端口为3306，如果MySQL使用了非标准端口，需在 `config.php` 中正确配置 `port` 值。
5. **数据库不存在**：确认 `dbname` 指定的数据库已创建。执行 `mysql -u用户名 -p -e "SHOW DATABASES"` 查看已有数据库列表。
6. **表前缀不匹配**：`dbqz` 配置决定了表前缀（如 `pay_`），系统会在表名前添加此前缀。如果迁移数据库后前缀变化，需同步修改 `config.php`。

***

### 9.2.3 计划任务未执行

**症状**：结算未自动生成、通知未自动重试、订单统计未更新、通道日限额未重置

**原因与解决方案**：

计划任务通过访问 `cron.php` 执行，支持以下任务：

- `cron.php?do=settle&key=xxx`：自动生成结算列表
- `cron.php?do=order&key=xxx`：订单统计与清理（含通道日限额重置）
- `cron.php?do=notify&key=xxx`：通知重试（1-5次）
- `cron.php?do=notify2&key=xxx`：已放弃通知的再次重试

1. **crontab未配置**：需在服务器上配置定时任务，建议配置如下：
   ```bash
   * * * * * curl -s "http://你的域名/cron.php?do=notify&key=你的监控密钥" > /dev/null 2>&1
   0 1 * * * curl -s "http://你的域名/cron.php?do=order&key=你的监控密钥" > /dev/null 2>&1
   0 2 * * * curl -s "http://你的域名/cron.php?do=settle&key=你的监控密钥" > /dev/null 2>&1
   ```
   通知重试建议每分钟执行一次，订单统计和结算建议每日执行一次。
2. **cronkey不匹配**：`cron.php` 第17-18行会验证 `key` 参数与系统配置的 `cronkey` 是否一致。登录管理后台确认监控密钥配置，确保URL中的 `key` 参数与之完全匹配。
3. **PHP CLI路径错误**：如果使用PHP CLI方式执行（如 `php /path/to/cron.php`），确保PHP路径正确。推荐使用curl方式通过HTTP访问，避免CLI模式下的环境差异。
4. **URL不可访问**：确保 `cron.php` 可以通过HTTP正常访问。注意 `cron.php` 第2行会屏蔽百度蜘蛛的访问（`preg_match('/Baiduspider/', $_SERVER['HTTP_USER_AGENT'])`），使用curl时User-Agent不受影响。
5. **也可使用外部监控服务**：如果服务器不支持crontab，可以使用第三方监控服务（如阿里云监控、百度站点监控）定时访问cron.php的URL。

***

### 9.2.4 安装后无法访问

**症状**：访问网站提示"请先完成网站升级"或检测到无install.lock文件

**原因与解决方案**：

1. **版本号不匹配**：`common.php` 第76-82行检查 `$conf['version']` 与 `DB_VERSION` 常量（定义为 `2024`）是否一致。如果数据库中的版本号低于代码版本号，系统会提示升级。解决方案：访问 `/install/update.php` 执行数据库升级脚本，升级完成后版本号会自动更新。
2. **install.lock文件缺失**：`common.php` 第87-89行检查 `/install/install.lock` 文件是否存在。如果不存在且安装程序仍在，系统会提示安全警告。解决方案：在 `/install/` 目录下创建空的 `install.lock` 文件：
   ```bash
   touch /www/wwwroot/pay/install/install.lock
   ```
3. **数据库未安装**：如果 `config.php` 中数据库用户名、密码或数据库名为空，系统会提示"你还没安装"。解决方案：访问 `/install/` 完成安装向导。

***

## 9.3 安全相关问题

### 9.3.1 CC攻击防护

**症状**：访问页面时频繁跳转到"正在加载中"页面，需等待后才能正常访问

**原因与解决方案**：

这是 `security.php` 中 `cc_defender()` 函数的正常防护行为。当 `$is_defend=true` 时（默认开启），系统会对每个访问者进行Cookie验证：

1. **正常行为说明**：首次访问时，系统会设置一个基于IP和日期的验证Cookie（`sec_defend`），然后通过JavaScript刷新页面。浏览器执行JS后设置Cookie并重新加载，系统验证Cookie通过后放行。这是防CC攻击的正常机制，正常用户只会看到一次短暂的"正在加载中"跳转。
2. **Cookie被禁用**：如果用户浏览器禁用了Cookie，系统无法设置验证Cookie，会导致反复跳转。当重试次数达到10次（`sec_defend_time>=10`）时，页面会显示"浏览器不支持COOKIE或者不正常访问！"。解决方案：告知用户启用浏览器Cookie功能。
3. **关闭CC防护（不推荐）**：如果确实需要关闭，可以在页面文件中将 `$is_defend = true` 改为 `$is_defend = false`。但强烈不建议在生产环境关闭此功能，否则系统将失去对CC攻击的基本防护能力。
4. **蜘蛛屏蔽机制**：`txprotect.php` 会屏蔽已知的恶意蜘蛛和异常浏览器（包括Baiduspider、360Spider、python脚本、SemrushBot、HeadlessChrome等），返回404状态码。如果正常访问被误拦截，检查浏览器User-Agent是否包含被屏蔽的关键词。

***

### 9.3.2 域名未授权

**症状**：提交支付时提示"该域名不可发起支付，原因：域名没过白，请前往支付平台授权支付域名"

**原因与解决方案**：

当系统配置 `pay_domain_forbid=1` 时，`submit.php` 第71-75行会检查商户通知地址的域名是否在白名单中。系统查询 `pre_domain` 表，匹配条件为 `uid=商户ID` 且 `status=1` 且域名等于精确域名或通配符域名。

1. **添加域名白名单**：登录管理后台，在域名管理中为商户添加授权域名。域名必须与 `notify_url` 中的域名完全一致。
2. **通配符域名支持**：系统支持通配符域名格式 `*.example.com`。添加此格式后，所有子域名（如 `a.example.com`、`b.example.com`）均可通过验证。系统通过 `get_main_host()` 函数提取主域名进行匹配。
3. **域名验证逻辑**：系统先尝试精确匹配（`domain=完整域名`），再尝试通配符匹配（`domain=*.主域名`）。例如 `notify_url` 为 `http://shop.example.com/notify`，系统会查询 `domain='shop.example.com'` 或 `domain='*.example.com'` 的记录。
4. **关闭域名限制**：将系统配置 `pay_domain_forbid` 设为0可关闭域名白名单验证，但这会降低安全性，不建议在生产环境使用。

***

### 9.3.3 风控拦截

**症状**：提交支付时提示"该商品禁止出售"或"系统异常无法完成付款"

**原因与解决方案**：

系统在 `submit.php` 中实现了三层风控拦截机制：

1. **商品名关键词拦截**（第77-85行）：当系统配置 `blockname` 不为空时，系统以 `|` 分隔关键词列表，逐一检查商品名（name参数）是否包含这些关键词。如果匹配，系统会将拦截记录写入 `pre_risk` 表，并提示 `blockalert` 配置的内容（默认为"该商品禁止出售"）。
   - **解决方案**：修改商品名避免包含敏感关键词，或联系管理员调整 `blockname` 配置。管理员可在后台查看 `pre_risk` 表中的风控记录。
2. **IP黑名单拦截**（第87-89行）：当系统配置 `blockips` 不为空时，系统以 `|` 分隔IP列表，检查买家IP是否在黑名单中。匹配时提示"系统异常无法完成付款"。
   - **解决方案**：联系管理员将买家IP从 `blockips` 配置中移除。管理员可在后台系统设置中修改IP黑名单。
3. **买家ID黑名单拦截**：`checkBlockUser()` 函数（`functions.php` 第495-503行）在支付回调时检查买家ID（openid）是否在 `blockusers` 配置的黑名单中。匹配时返回"系统异常无法完成付款"。
   - **解决方案**：联系管理员将买家ID从 `blockusers` 配置中移除。此检查在支付完成后、订单处理前执行，被拦截的订单不会入账。

***

## 9.4 兼容性问题

### 9.4.1 PHP版本兼容

**症状**：页面显示"require PHP >= 7.1 !"或出现语法错误

**解决方案**：

聚合易支付要求PHP版本不低于7.1。代码中使用了PHP 7.1+的特性，包括：

- 匿名类和闭包
- 命名空间（namespace）和use导入
- 类型声明
- 异常类层次结构

1. **检查当前PHP版本**：执行 `php -v` 查看版本号，确认 >= 7.1。
2. **升级PHP版本**：根据服务器环境选择合适的升级方式：
   - 宝塔面板：在"软件商店"中切换PHP版本
   - CentOS：通过Remi仓库安装 `yum install php74`
   - Ubuntu：通过ondrej PPA安装 `apt install php7.4`
3. **检查PHP扩展**：确保安装了必要的PHP扩展，包括 pdo\_mysql、curl、gd、mbstring、json、openssl 等。

***

### 9.4.2 HTTPS配置

**症状**：回调地址为HTTP而非HTTPS，或系统判断协议不正确

**原因与解决方案**：

`common.php` 第18-35行定义了 `is_https()` 函数，通过6种方式检测HTTPS：

1. `$_SERVER['SERVER_PORT'] == 443`：检测服务器端口是否为443
2. `$_SERVER['HTTPS'] == 'on' || $_SERVER['HTTPS'] == '1'`：检测Apache的HTTPS标志
3. `$_SERVER['HTTP_X_CLIENT_SCHEME'] == 'https'`：检测阿里云SLB的协议头
4. `$_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https'`：检测Nginx/CDN反向代理的协议头
5. `$_SERVER['REQUEST_SCHEME'] == 'https'`：检测Nginx的请求协议
6. `$_SERVER['HTTP_EWS_CUSTOME_SCHEME'] == 'https'`：检测企业微信的协议头

**反向代理配置**：

如果使用Nginx反向代理到Apache/PHP-FPM，需在Nginx配置中传递协议头：

```nginx
proxy_set_header X-Forwarded-Proto $scheme;
proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
proxy_set_header Host $host;
```

如果使用CDN（如CloudFlare、阿里云CDN），CDN通常会自动添加 `X-Forwarded-Proto` 头。如果CDN使用自定义头，需确保与 `is_https()` 函数检测的6种方式之一匹配。

**获取真实IP**：

`real_ip()` 函数（`functions.php` 第84-101行）按优先级检测真实客户端IP：

1. `HTTP_X_FORWARDED_FOR`：标准代理头
2. `HTTP_CLIENT_IP`：部分代理服务器使用
3. `HTTP_CF_CONNECTING_IP`：CloudFlare专用头
4. `HTTP_X_REAL_IP`：Nginx常用头

确保反向代理正确传递这些头信息，否则风控IP拦截和订单IP记录可能不准确。

***

### 9.4.3 代理配置

**症状**：curl请求失败（如支付通道API调用超时或连接失败）

**原因与解决方案**：

`curl_get()` 函数（`functions.php` 第2-37行）支持通过系统配置的代理进行HTTP请求。当服务器无法直接访问外部API时（如处于内网环境），需要配置代理。

1. **配置代理参数**：在管理后台系统设置中配置以下参数：
   - `proxy`：设为1启用代理
   - `proxy_server`：代理服务器地址
   - `proxy_port`：代理服务器端口
   - `proxy_user`：代理认证用户名
   - `proxy_pwd`：代理认证密码
   - `proxy_type`：代理类型，支持以下值：
     - `http`：HTTP代理（默认，CURLPROXY\_HTTP）
     - `https`：HTTPS代理（CURLPROXY\_HTTPS）
     - `sock4`：SOCKS4代理（CURLPROXY\_SOCKS4）
     - `sock5`：SOCKS5代理（CURLPROXY\_SOCKS5）
2. **验证代理连通性**：配置完成后，可通过测试支付功能验证代理是否正常工作。也可在服务器上手动测试：
   ```bash
   curl -x http://代理地址:端口 https://目标API地址
   ```
3. **常见问题排查**：
   - 代理认证失败：检查用户名和密码是否正确
   - 代理超时：检查代理服务器是否正常运行，网络是否通畅
   - SSL错误：系统已设置 `CURLOPT_SSL_VERIFYPEER=false`，如仍有SSL问题，检查代理是否支持HTTPS转发

***

### 9.4.4 CDN配置问题

**症状**：页面静态资源（JS、CSS、字体等）加载失败，或加载缓慢

**原因与解决方案**：

`common.php` 第91-99行根据 `cdnpublic` 配置决定静态资源的CDN源：

| cdnpublic值 | CDN源           | 地址                                             |
| ---------- | -------------- | ---------------------------------------------- |
| 1          | 宝塔CDN          | `//lib.baomitu.com/`                           |
| 2          | BootCDN        | `https://cdn.bootcdn.net/ajax/libs/`           |
| 4          | 字节跳动CDN        | `//lf26-cdn-tos.bytecdntp.com/cdn/expire-1-M/` |
| 其他（默认3）    | Staticfile CDN | `//cdn.staticfile.org/`                        |

1. **CDN源不可访问**：如果选择的CDN源在某些地区或网络环境下不可访问，会导致静态资源加载失败。解决方案：在管理后台切换到其他CDN源（修改 `cdnpublic` 配置值），或设为0使用默认的Staticfile CDN。
2. **协议头问题**：CDN地址使用 `//` 开头的协议相对路径，依赖页面协议自动选择HTTP或HTTPS。如果页面是HTTPS但CDN源不支持HTTPS，会导致资源加载失败。解决方案：选择支持HTTPS的CDN源（如BootCDN使用 `https://` 开头）。
3. **自定义CDN**：如果需要使用自建CDN或其他公共CDN，需修改 `common.php` 中的CDN配置逻辑，添加新的选项分支。
4. **CDN缓存问题**：更新系统后如果静态资源未刷新，可能是CDN缓存未过期。解决方案：清除CDN缓存，或在资源URL后添加版本号参数强制刷新。

