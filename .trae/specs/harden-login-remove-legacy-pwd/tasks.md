# Tasks

- [x] Task 1: 改进管理后台登录防暴力破解机制
  - [x] SubTask 1.1: 在 admin688/login.php 中实现基于session的指数延迟锁定策略（3次→5分钟，5次→15分钟，8次→30分钟，10次+→1小时）
  - [x] SubTask 1.2: 锁定期间显示剩余时间提示，不验证密码
  - [x] SubTask 1.3: 登录成功后清除错误计数和锁定时间

- [x] Task 2: 移除旧版密码兼容代码
  - [x] SubTask 2.1: 移除 includes/functions.php 中的 getMd5Pwd() 函数
  - [x] SubTask 2.2: 简化 verifyPwd() 函数，移除MD5分支，仅保留 password_verify()
  - [x] SubTask 2.3: 移除 admin688/login.php 中的明文密码兼容判断（strlen < 60）和密码自动升级逻辑
  - [x] SubTask 2.4: 移除 admin688/set.php 中的明文密码兼容判断（strlen < 60）
  - [x] SubTask 2.5: 移除 admin688/ajax_settle.php 中的明文密码兼容判断和密码自动升级逻辑
  - [x] SubTask 2.6: 移除 admin688/ajax_order.php 中的明文密码兼容判断
  - [x] SubTask 2.7: 移除 admin688/transfer.php 中的明文密码兼容判断和密码自动升级逻辑
  - [x] SubTask 2.8: 简化 user/ajax.php 中的 verifyPwd 调用，移除MD5密码自动升级逻辑
  - [x] SubTask 2.9: 简化 user/ajax2.php 中的 verifyPwd 调用

- [x] Task 3: 检查并修正 install/install.sql
  - [x] SubTask 3.1: 将 version 值从 '2024' 改为 '0.01'
  - [x] SubTask 3.2: 确认 admin_pwd 和 admin_paypwd 使用bcrypt哈希
  - [x] SubTask 3.3: 确认 pre_user.pwd 字段为 varchar(255)

# Task Dependencies
- [Task 2] depends on [Task 1] (先改登录逻辑再清理密码代码，避免中间状态不可用)
