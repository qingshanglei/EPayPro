# Tasks

- [ ] Task 1: 修复 admin688/set.php 中剩余约31处XSS漏洞
  - [ ] 1.1: 修复 value 属性中的 `$conf` 输出（约20处，如 description, kfqq, reg_pay_price, localurl_alipay, reg_pay_uid, settle_money, login_qq_appid, login_qq_appkey, login_appid, mail_name, mail_pwd, mail_name2, mail_recv, sms_sign, sms_tpl_reg, proxy_server, proxy_port, proxy_user 等）
  - [ ] 1.2: 修复 default 属性中的 `$conf` 输出（约5处，如 user_review, settle_wxpay, transfer_wxpay, login_alipay, mail_cloud, sms_api, proxy）
  - [ ] 1.3: 修复 textarea 内容中的 `$conf` 输出（zhuce, footer）
  - [ ] 1.4: 修复 URL/内容中的 `$conf` 输出（cronkey 在3处URL中, template 在img src中）
  - [ ] 1.5: 验证所有 `echo $conf[` 均已处理（排除条件表达式）

- [ ] Task 2: 修复 admin688/ajax.php 任意配置覆盖漏洞
  - [ ] 2.1: 在 `set` case 的 `foreach` 循环前添加 `$allowed_keys` 白名单数组
  - [ ] 2.2: 在 `foreach` 循环内添加 `if(!in_array($k, $allowed_keys)) continue;` 校验
  - [ ] 2.3: 验证白名单不包含敏感键（admin_user, admin_pwd, admin_paypwd, syskey, cronkey）

# Task Dependencies
- Task 1 和 Task 2 无依赖关系，可并行执行
