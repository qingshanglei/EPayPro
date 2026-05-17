# 登录防暴力破解与移除旧密码兼容代码 Spec

## Why
当前登录错误5次后直接重置计数器允许再次尝试5次，攻击者可以无限循环暴力破解密码。同时，用户已全部使用bcrypt新密码，旧版MD5/明文密码兼容代码可以移除以简化代码并消除安全隐患。

## What Changes
- 改进管理后台登录防暴力破解机制：从"5次错误后重置"改为"指数延迟锁定"，错误越多等待越久
- 移除所有旧版密码兼容代码（明文密码 `strlen < 60` 判断、`getMd5Pwd()` 函数、`verifyPwd()` 中的MD5分支）
- 简化 `verifyPwd()` 为纯 `password_verify()`，或直接内联
- 检查并修正 `install/install.sql` 中的版本号和密码默认值

## Impact
- Affected specs: 安全加固
- Affected code: `admin688/login.php`, `admin688/set.php`, `admin688/ajax_settle.php`, `admin688/ajax_order.php`, `admin688/transfer.php`, `includes/functions.php`, `user/ajax.php`, `user/ajax2.php`, `install/install.sql`

## ADDED Requirements

### Requirement: 登录防暴力破解机制
系统 SHALL 在管理后台登录时实施指数延迟锁定策略，防止暴力破解密码。

#### Scenario: 首次登录失败
- **WHEN** 用户输入错误密码
- **THEN** 系统记录错误次数，提示"用户名或密码不正确！您还可以尝试4次"

#### Scenario: 连续多次登录失败
- **WHEN** 用户连续输入错误密码达到3次
- **THEN** 系统锁定登录5分钟，提示"登录错误次数过多，请5分钟后再试"
- **WHEN** 用户连续输入错误密码达到5次
- **THEN** 系统锁定登录15分钟
- **WHEN** 用户连续输入错误密码达到8次
- **THEN** 系统锁定登录30分钟
- **WHEN** 用户连续输入错误密码达到10次以上
- **THEN** 系统锁定登录1小时

#### Scenario: 锁定期间尝试登录
- **WHEN** 用户在锁定期间尝试登录
- **THEN** 系统提示"登录已锁定，请X分钟X秒后再试"，不验证密码

#### Scenario: 登录成功
- **WHEN** 用户在非锁定期间输入正确密码
- **THEN** 系统清除错误计数，正常登录

## MODIFIED Requirements

### Requirement: 密码验证
密码验证 SHALL 仅使用 `password_verify()` 进行bcrypt哈希验证，不再兼容旧版明文密码和MD5密码。

- 移除 `getMd5Pwd()` 函数
- 简化 `verifyPwd()` 函数，移除MD5分支，仅保留 `password_verify()`
- 移除所有 `strlen($hash) < 60` 的明文密码兼容判断
- 移除所有密码自动升级逻辑（`saveSetting` + `$CACHE->update` 在登录时的密码升级代码）

### Requirement: install.sql 默认数据
`install/install.sql` SHALL 使用正确的版本号和bcrypt哈希默认密码。

- `version` 值从 `'2024'` 改为 `'0.01'`
- `admin_pwd` 和 `admin_paypwd` 使用bcrypt哈希（已有）
- `pre_user.pwd` 字段使用 `varchar(255)`（已有）
