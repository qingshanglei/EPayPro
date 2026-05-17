# 聚合易支付安全漏洞修复 Spec

## Why

项目购买后经安全审计发现存在26+个安全漏洞，包括SQL注入、XSS、CSRF、密码明文存储、硬编码密钥等严重问题，需立即修复以保障系统安全。

## What Changes

* 修复管理后台SQL注入漏洞（30+处直接拼接用户输入的SQL语句）

* 修复XSS漏洞（6处，含反射型和存储型）

* 修复CSRF漏洞（管理后台全部AJAX接口缺少Token验证）

* 将管理员密码和支付密码从明文存储改为bcrypt哈希存储

* 更换硬编码的authcode密钥为随机唯一密钥

* 修复任意配置覆盖漏洞（添加白名单）

* 删除或限制install目录访问（install目录访问不用管，我部署项目回把他给删除了）

* 修复update.php无认证问题

* 修复Cookie安全标志缺失

* 修复IP伪造漏洞

* 启用cURL SSL证书验证

* 修复文件上传无类型验证

* 修复弱随机数生成器

* 修复订单号可预测问题

## Impact

* Affected specs: 无现有spec受影响

* Affected code: admin688/ajax\_order.php, admin688/ajax\_user.php, admin688/ajax\_settle.php, admin688/ajax\_pay.php, admin688/ajax.php, admin688/login.php, admin688/set.php, admin688/uset.php, admin688/sso.php, admin688/clean.php, includes/authcode.php, includes/common.php, includes/functions.php, includes/member.php, includes/lib/PayUtils.php, includes/lib/PdoHelper.php, user/ajax.php, user/ajax2.php, install/update.php, cron.php, submit.php, pay.php, config.php

## ADDED Requirements

### Requirement: SQL注入修复

系统 SHALL 修复所有SQL注入漏洞，确保所有数据库查询使用参数化查询或严格的输入验证。

#### Scenario: 管理后台动态列名查询

* **WHEN** 管理员通过后台搜索订单或用户

* **THEN** 动态列名必须通过白名单验证，动态值必须使用参数绑定

#### Scenario: 管理后台订单操作

* **WHEN** 管理员对订单执行删除/修改/退款操作

* **THEN** trade\_no等参数必须使用参数绑定，禁止直接拼接

### Requirement: XSS修复

系统 SHALL 修复所有XSS漏洞，确保所有输出到HTML的变量经过htmlspecialchars转义。

#### Scenario: 管理后台登录页面

* **WHEN** 用户提交登录表单失败后重新显示

* **THEN** 用户名输入框的值必须经过htmlspecialchars转义

#### Scenario: 管理后台数据展示

* **WHEN** 管理员查看商户信息、结算信息、通道配置

* **THEN** 所有数据库字段输出到HTML时必须经过htmlspecialchars转义

### Requirement: CSRF修复

系统 SHALL 为管理后台所有写操作接口添加CSRF Token验证机制。

#### Scenario: 管理后台AJAX操作

* **WHEN** 管理员执行任何写操作（修改配置、操作订单、管理用户等）

* **THEN** 必须验证请求中的csrf\_token与Session中的token一致

### Requirement: 密码安全存储

系统 SHALL 将管理员密码和支付密码从明文存储改为bcrypt哈希存储。

#### Scenario: 管理员登录

* **WHEN** 管理员提交登录表单

* **THEN** 系统使用password\_verify()验证密码哈希

#### Scenario: 管理员修改密码

* **WHEN** 管理员修改密码

* **THEN** 系统使用password\_hash()存储新密码

### Requirement: authcode密钥安全

系统 SHALL 将硬编码的authcode密钥替换为安装时随机生成的唯一密钥。

#### Scenario: 系统安装

* **WHEN** 系统首次安装

* **THEN** 自动生成随机authcode密钥并保存到数据库配置中

### Requirement: 配置写入白名单

系统 SHALL 为管理后台的配置保存接口添加键名白名单验证。

#### Scenario: 管理员保存系统配置

* **WHEN** 管理员提交系统配置表单

* **THEN** 仅允许白名单中的配置键被保存，admin\_user/admin\_pwd/syskey等敏感配置需单独验证

### Requirement: install目录安全

系统 SHALL 确保install目录在安装完成后不可被访问。

#### Scenario: 安装完成后访问install目录

* **WHEN** 安装完成后用户尝试访问/install/路径

* **THEN** 返回403或404，update.php必须添加认证检查

### Requirement: Cookie安全标志

系统 SHALL 为所有认证Cookie设置HttpOnly、Secure、SameSite标志。

#### Scenario: 设置认证Cookie

* **WHEN** 用户登录成功后设置Cookie

* **THEN** Cookie必须设置HttpOnly=true, Secure=true(HTTPS), SameSite=Strict

### Requirement: IP获取安全

系统 SHALL 修复IP伪造漏洞，默认使用REMOTE\_ADDR，仅在显式配置反向代理时才信任X-Forwarded-For。

#### Scenario: 获取客户端IP

* **WHEN** 系统需要获取客户端真实IP

* **THEN** 默认使用$\_SERVER\['REMOTE\_ADDR']，仅当配置了可信代理时才解析X-Forwarded-For

### Requirement: SSL证书验证

系统 SHALL 启用cURL的SSL证书验证。

#### Scenario: 发起外部HTTPS请求

* **WHEN** 系统通过cURL发起支付回调等HTTPS请求

* **THEN** CURLOPT\_SSL\_VERIFYPEER=true, CURLOPT\_SSL\_VERIFYHOST=2

### Requirement: 文件上传安全

系统 SHALL 为文件上传添加类型验证。

#### Scenario: 管理员上传Logo

* **WHEN** 管理员上传网站Logo

* **THEN** 系统验证文件MIME类型为图片格式，拒绝非图片文件

### Requirement: 弱随机数修复

系统 SHALL 将安全敏感场景的随机数生成从mt\_rand改为random\_bytes。

#### Scenario: 生成商户密钥

* **WHEN** 系统生成商户密钥或CSRF Token

* **THEN** 使用random\_bytes()生成密码学安全的随机数

### Requirement: 订单号安全

系统 SHALL 增强订单号的随机性。

#### Scenario: 生成订单号

* **WHEN** 系统创建新订单

* **THEN** 订单号中的随机部分使用random\_bytes生成，至少包含8位随机字符

## MODIFIED Requirements

### Requirement: 管理员认证

管理员密码验证从明文比对改为password\_verify()哈希验证。登录时需兼容已有明文密码，首次验证成功后自动升级为哈希存储。

### Requirement: 商户认证

商户密码哈希从双重MD5升级为bcrypt。登录时需兼容已有MD5密码，首次验证成功后自动升级为bcrypt存储。

## REMOVED Requirements

### Requirement: 硬编码authcode密钥

**Reason**: 所有安装共享同一密钥是严重安全隐患，攻击者可伪造任意用户Cookie
**Migration**: 安装时自动生成随机密钥保存到数据库，从数据库读取密钥而非硬编码常量
