# 聚合易支付项目对接文档 Spec

## Why

新接手项目需要一份全面的对接文档，以支持后续二次开发工作。当前项目缺乏系统性文档，开发人员难以快速理解项目全貌、定位代码位置和开展功能扩展。

## What Changes

* 新增项目架构概述文档，描述整体系统架构与模块划分

* 新增技术栈说明文档，列出所有依赖与框架

* 新增核心功能模块解析文档，详解支付流程、用户系统、通道管理等核心逻辑

* 新增API接口规范文档，涵盖支付提交、回调通知、订单查询等接口

* 新增数据库结构设计文档，包含全部数据表及字段说明

* 新增环境配置指南文档，涵盖部署、Nginx/Apache配置、计划任务等

* 新增开发规范与最佳实践文档

* 新增功能模块扩展点说明文档

* 新增常见问题解决方案文档

## Impact

* Affected specs: 无现有spec受影响（首次创建文档）

* Affected code: 无代码变更，纯文档输出

## ADDED Requirements

### Requirement: 项目架构概述

系统 SHALL 提供完整的项目架构概述，包含以下内容：

* 系统整体架构图（文字描述）

* 目录结构说明

* 请求生命周期（从入口到响应的完整流程）

* URL路由机制（Apache/Nginx/IIS三种配置）

#### Scenario: 开发者查看架构概述

* **WHEN** 开发者阅读架构概述章节

* **THEN** 能够理解项目的整体结构、各目录职责、请求处理流程和URL路由规则

### Requirement: 技术栈说明

系统 SHALL 提供完整的技术栈说明，包含：

* 服务端：PHP >= 7.1, MySQL (PDO), Apache/Nginx

* 前端：jQuery 1.x/2.x, Bootstrap 3, 自定义CSS

* 第三方SDK：支付宝SDK、微信支付SDK、QQ钱包SDK

* 缓存：基于数据库的文件缓存

* 验证码：极验(GeeTest)验证码

* 邮件：PHPMailer / SendCloud / 阿里云邮件

* 短信：腾讯云 / 阿里云 / 顶想云

* 实名认证：阿里云API / 腾讯云人脸核身 / 支付宝快捷认证

#### Scenario: 开发者查看技术栈

* **WHEN** 开发者阅读技术栈章节

* **THEN** 能够了解项目使用的所有技术组件及其版本要求

### Requirement: 核心功能模块解析

系统 SHALL 提供核心功能模块的详细解析，包含：

1. **支付流程模块**

   * submit.php：API支付提交入口（签名验证→订单创建→通道分配→插件调用）

   * submit2.php：收银台支付提交（从收银台选择支付方式后调用）

   * cashier.php：收银台页面（展示可用支付方式）

   * pay.php：支付回调路由（处理notify/return/支付方式路由）

   * getshop.php：订单状态查询

2. **通道管理模块**

   * Channel类：通道分配逻辑（用户组→通道→轮询组→插件）

   * 轮询组机制：顺序轮询/加权随机

   * 用户组通道配置：JSON格式配置

3. **插件系统模块**

   * Plugin类：插件加载与调用机制

   * 插件开发规范：$info属性、submit/notify/return/mapi/jsapi/refund方法

   * 已有插件清单与说明

4. **订单处理模块**

   * Payment类：订单状态更新、回调处理

   * processOrder函数：订单完成后资金变动逻辑

   * 通知重试机制：1分钟→3分钟→20分钟→1小时→2小时

5. **用户系统模块**

   * 商户注册/登录/认证

   * 用户组与权限

   * 余额管理与资金明细

   * 结算与提现

6. **管理后台模块**

   * 管理员认证机制

   * 订单/商户/通道/结算管理

   * 系统配置

7. **聚合收款码模块**

   * paypage目录：移动端收款页面

   * OpenId获取（微信/支付宝）

   * JSAPI支付

#### Scenario: 开发者理解支付流程

* **WHEN** 开发者阅读核心功能模块解析

* **THEN** 能够理解从支付提交到回调完成的完整流程，以及各模块的职责和交互方式

### Requirement: API接口规范

系统 SHALL 提供完整的API接口规范，包含：

1. **支付提交接口**

   * 请求方式：GET/POST

   * 请求参数：pid, type, out\_trade\_no, notify\_url, return\_url, name, money, sign, sign\_type, param

   * 签名算法：MD5签名（参数排序→拼接→追加密钥→MD5）

   * 响应：跳转支付页面

2. **MAPI接口**

   * 请求方式：POST (JSON)

   * 返回格式：JSON (code, trade\_no, payurl/qrcode/urlscheme)

3. **异步通知接口**

   * 通知URL：商户提供的notify\_url

   * 通知参数：pid, trade\_no, out\_trade\_no, type, name, money, trade\_status, sign, sign\_type

   * 验证方式：MD5签名验证

   * 响应要求：返回"success"

4. **同步回调接口**

   * 回调URL：商户提供的return\_url

   * 回调参数：同异步通知

5. **订单查询接口**

   * 请求URL：getshop.php?trade\_no={trade\_no}

   * 返回格式：JSON

6. **签名规则**

   * 过滤空值和sign/sign\_type参数

   * 参数按ASCII升序排序

   * 拼接为key=value&格式

   * 追加商户密钥后MD5

#### Scenario: 商户对接支付接口

* **WHEN** 商户按照API接口规范发起支付请求

* **THEN** 能够正确签名、提交订单、接收回调通知并验证签名

### Requirement: 数据库结构设计

系统 SHALL 提供完整的数据库结构文档，包含所有数据表及字段说明：

1. pre\_config - 系统配置表
2. pre\_cache - 缓存表
3. pre\_anounce - 公告表
4. pre\_type - 支付方式表
5. pre\_plugin - 支付插件表
6. pre\_channel - 支付通道表
7. pre\_roll - 通道轮询组表
8. pre\_weixin - 微信公众号/小程序表
9. pre\_order - 订单表
10. pre\_group - 用户组表
11. pre\_user - 商户/用户表
12. pre\_settle - 结算记录表
13. pre\_log - 登录日志表
14. pre\_record - 资金明细表
15. pre\_batch - 批量转账表
16. pre\_regcode - 注册验证码表
17. pre\_risk - 风控记录表
18. pre\_alipayrisk - 支付宝风控表
19. pre\_domain - 授权域名表

每张表需包含：表名、表说明、字段列表（字段名、类型、默认值、说明）、索引说明、关联关系。

#### Scenario: 开发者查询数据库结构

* **WHEN** 开发者阅读数据库结构文档

* **THEN** 能够理解每张表的用途、字段含义和表间关联关系

### Requirement: 环境配置指南

系统 SHALL 提供完整的环境配置指南，包含：

1. **服务器要求**

   * PHP >= 7.1

   * MySQL 5.5+

   * Apache/Nginx Web服务器

   * PHP扩展：pdo\_mysql, curl, gd, mbstring

2. **安装流程**

   * 配置config.php

   * 访问/install/进行安装

   * 安装后删除或锁定install目录

3. **Nginx配置**

   * URL重写规则

   * 目录访问限制（plugins, includes）

4. **Apache配置**

   * .htaccess重写规则

5. **计划任务配置**

   * cron.php?do=settle\&key=xxx - 自动结算

   * cron.php?do=order\&key=xxx - 订单统计与清理

   * cron.php?do=notify\&key=xxx - 通知重试

   * cron.php?do=notify2\&key=xxx - 失败通知重试

6. **CDN配置**

   * 支持宝塔CDN、BootCDN、字节CDN、StaticFile CDN

#### Scenario: 运维人员部署项目

* **WHEN** 运维人员按照环境配置指南操作

* **THEN** 能够成功部署项目并配置好Web服务器和计划任务

### Requirement: 开发规范与最佳实践

系统 SHALL 提供开发规范与最佳实践文档，包含：

1. **代码风格规范**

   * PHP代码风格（类PSR-2但非严格遵循）

   * 命名规范（函数/变量/类）

   * 数据库操作规范（PdoHelper使用）

2. **安全规范**

   * SQL注入防护（预处理语句）

   * XSS防护（htmlspecialchars）

   * CSRF防护（Token验证）

   * 签名验证机制

   * CC防护机制

3. **插件开发规范**

   * 插件目录结构

   * $info属性定义

   * 必须实现的方法

   * 返回值格式

4. **模板开发规范**

   * 模板目录结构

   * 模板加载机制

   * 可用全局变量

#### Scenario: 开发者遵循规范开发

* **WHEN** 开发者按照开发规范进行二次开发

* **THEN** 代码风格一致、安全合规、与现有系统兼容

### Requirement: 功能模块扩展点说明

系统 SHALL 提供功能模块扩展点说明，包含：

1. **支付插件扩展**

   * 新增支付插件的步骤

   * 插件接口定义（submit, notify, return, mapi, jsapi, refund）

   * 插件配置参数（inputs, select）

   * 插件返回值格式（type: jump/html/page/qrcode/scheme/return/error/json）

2. **首页模板扩展**

   * 模板目录结构

   * 模板加载机制

   * 可用变量与函数

3. **支付方式扩展**

   * pre\_type表新增支付方式

   * 插件types属性声明

4. **用户组扩展**

   * pre\_group表配置

   * 通道与费率配置JSON格式

5. **实名认证扩展**

   * 认证方式（支付宝快捷/微信快捷/手机三要素/人工审核）

   * 认证回调处理

6. **转账通道扩展**

   * 支付宝转账/微信企业付款/微信商家转账/QQ钱包付款/银行卡转账

   * Jeepay聚合转账

#### Scenario: 开发者扩展支付插件

* **WHEN** 开发者需要新增一种支付通道

* **THEN** 按照扩展点说明创建插件目录、实现接口方法、配置参数，即可完成新通道接入

### Requirement: 常见问题解决方案

系统 SHALL 提供常见问题解决方案文档，包含：

1. **支付相关问题**

   * 签名验证失败

   * 回调通知未收到

   * 通道不可用

   * 金额不匹配

2. **部署相关问题**

   * URL重写不生效

   * 数据库连接失败

   * 计划任务未执行

3. **安全相关问题**

   * CC攻击防护

   * 域名未授权

   * 风控拦截

4. **兼容性问题**

   * PHP版本兼容

   * HTTPS配置

   * 代理配置

#### Scenario: 开发者遇到签名验证失败

* **WHEN** 开发者遇到签名验证失败问题

* **THEN** 参考常见问题文档排查签名算法、密钥配置、参数编码等问题

