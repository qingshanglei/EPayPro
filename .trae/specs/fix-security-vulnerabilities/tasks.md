# Tasks

- [x] Task 1: 修复管理后台SQL注入漏洞（紧急）
  - [x] SubTask 1.1: 修复 ajax_order.php 中 $_POST['column']/$_POST['value'] 直接拼接SQL，添加列名白名单和参数绑定
  - [x] SubTask 1.2: 修复 ajax_order.php 中 trade_no 直接拼接SQL（约15处），改用参数绑定
  - [x] SubTask 1.3: 修复 ajax_user.php 中 $_POST['column']/$_POST['value'] 直接拼接SQL，添加列名白名单和参数绑定
  - [x] SubTask 1.4: 修复 ajax_user.php 中 $_POST['dstatus'] 直接拼接SQL，添加白名单验证
  - [x] SubTask 1.5: 修复 ajax_settle.php 中 batch/result/checkbox 直接拼接SQL，改用参数绑定
  - [x] SubTask 1.6: 修复 ajax_pay.php 中 name/plugin/showname 等直接拼接SQL，改用参数绑定
  - [x] SubTask 1.7: 修复 uset.php 中 uid 直接拼接SQL，添加intval转换
  - [x] SubTask 1.8: 修复 user/ajax2.php 中 phone/email/account 等拼接SQL，改用参数绑定
  - [x] SubTask 1.9: 修复 submit.php 中 realmoney/getmoney 拼接SQL，改用参数绑定

- [x] Task 2: 修复XSS漏洞
  - [x] SubTask 2.1: 修复 admin688/login.php $_POST['user'] 未转义输出，添加htmlspecialchars
  - [x] SubTask 2.2: 修复 admin688/uset.php 数据库字段直接输出到HTML属性，添加htmlspecialchars
  - [x] SubTask 2.3: 修复 admin688/ajax_user.php 结算信息未转义输出
  - [x] SubTask 2.4: 修复 admin688/ajax_settle.php 结算信息未转义输出
  - [x] SubTask 2.5: 修复 admin688/ajax_pay.php 通道配置未转义输出
  - [x] SubTask 2.6: 修复 admin688/set.php 系统配置值未转义输出（31处）

- [x] Task 3: 修复CSRF漏洞 - 管理后台添加CSRF Token机制
  - [x] SubTask 3.1: 在 admin688/head.php 中生成并输出csrf_token到页面
  - [x] SubTask 3.2: 在 admin688/ajax.php 中添加csrf_token验证
  - [x] SubTask 3.3: 在 admin688/ajax_order.php 中添加csrf_token验证
  - [x] SubTask 3.4: 在 admin688/ajax_user.php 中添加csrf_token验证
  - [x] SubTask 3.5: 在 admin688/ajax_settle.php 中添加csrf_token验证
  - [x] SubTask 3.6: 在 admin688/ajax_pay.php 中添加csrf_token验证
  - [x] SubTask 3.7: 在 admin688/set.php 非AJAX表单中添加csrf_token
  - [x] SubTask 3.8: 在 admin688/transfer.php 中添加csrf_token
  - [x] SubTask 3.9: 在 admin688/uset.php 非AJAX表单中添加csrf_token
  - [x] SubTask 3.10: 在 admin688/clean.php 中添加csrf_token验证

- [x] Task 4: 修复密码安全存储
  - [x] SubTask 4.1: 修改 admin688/login.php 管理员登录验证，使用password_verify替代明文比对，兼容已有明文密码自动升级
  - [x] SubTask 4.2: 修改 admin688/set.php 管理员密码修改，使用password_hash存储
  - [x] SubTask 4.3: 修改 admin688/ajax_settle.php 和 ajax_order.php 支付密码验证，使用password_verify
  - [x] SubTask 4.4: 修改 includes/functions.php 新增verifyPwd函数，兼容已有MD5密码自动升级
  - [x] SubTask 4.5: 修改 user/ajax.php 商户注册和密码修改，使用password_hash
  - [x] SubTask 4.6: 修改 install/install.sql 默认管理员密码为bcrypt哈希

- [x] Task 5: 修复authcode硬编码密钥
  - [x] SubTask 5.1: 修改 includes/authcode.php，删除硬编码密钥
  - [x] SubTask 5.2: 修改 includes/common.php，从数据库配置读取syskey，若不存在则自动生成
  - [x] SubTask 5.3: 确认 member.php Token生成使用SYS_KEY常量

- [x] Task 6: 修复任意配置覆盖漏洞
  - [x] SubTask 6.1: 在 admin688/ajax.php 的 set case 中添加配置键白名单（约80个合法键名）
  - [x] SubTask 6.2: admin_user/admin_pwd/admin_paypwd/syskey/cronkey 已排除在白名单外

- [x] Task 7: 修复install目录安全
  - [x] SubTask 7.1: 在 install/update.php 中添加install.lock文件检查
  - [x] SubTask 7.2: 在 .htaccess 中添加 install 目录访问限制规则

- [x] Task 8: 修复Cookie安全标志
  - [x] SubTask 8.1: 修改 admin688/login.php setcookie添加 HttpOnly, Secure, SameSite=Strict
  - [x] SubTask 8.2: 修改 user/ajax.php setcookie添加 HttpOnly, Secure, SameSite=Strict
  - [x] SubTask 8.3: 修改 admin688/sso.php setcookie添加 HttpOnly, Secure, SameSite=Strict

- [x] Task 9: 修复IP伪造漏洞
  - [x] SubTask 9.1: 修改 includes/functions.php real_ip()函数，默认使用REMOTE_ADDR
  - [x] SubTask 9.2: 更新管理后台IP获取方式配置选项

- [x] Task 10: 修复SSL证书验证
  - [x] SubTask 10.1: 修改 includes/functions.php curl_get/get_curl函数，启用SSL证书验证
  - [x] SubTask 10.2: 添加CA证书包路径自动检测

- [x] Task 11: 修复文件上传安全
  - [x] SubTask 11.1: 修改 admin688/set.php 文件上传逻辑，添加MIME类型验证

- [x] Task 12: 修复弱随机数和订单号安全
  - [x] SubTask 12.1: 修改 includes/functions.php random()函数，使用random_bytes/random_int
  - [x] SubTask 12.2: 修改 submit.php 订单号生成逻辑，随机部分从5位改为8位
  - [x] SubTask 12.3: 修改 PayUtils.php 签名比较使用hash_equals替代==

- [x] Task 13: 修复其他安全问题
  - [x] SubTask 13.1: 修复 cron.php 密钥验证，使用hash_equals，支持GET和POST
  - [x] SubTask 13.2: 修复 api.php 密钥比对，使用hash_equals防止时序攻击
  - [x] SubTask 13.3: 修复 user/ajax2.php file_put_contents使用绝对路径
  - [x] SubTask 13.4: 修复多处 $DB->error() 信息泄露
  - [x] SubTask 13.5: 修复 user/ajax.php 注册密码明文缓存问题

# Task Dependencies
- [Task 4] depends on [Task 5] (密码存储修改需先确定密钥来源)
- [Task 3] depends on [Task 12] (CSRF Token依赖安全随机数)
- [Task 8] depends on [Task 5] (Cookie安全依赖authcode密钥修复)
