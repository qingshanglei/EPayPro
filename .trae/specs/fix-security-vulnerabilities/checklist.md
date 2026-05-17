* [x] 管理后台所有SQL注入漏洞已修复（30+处直接拼接改为参数绑定或白名单验证）

* [x] 管理后台XSS漏洞已修复（6处未转义输出改为htmlspecialchars转义，含set.php 31处）

* [x] 管理后台CSRF Token机制已实现（所有写操作接口验证csrf\_token）

* [x] 管理员密码和支付密码从明文存储改为bcrypt哈希存储

* [x] 商户密码哈希从双重MD5升级为bcrypt（兼容旧密码自动升级）

* [x] authcode硬编码密钥已替换为安装时随机生成的唯一密钥

* [x] 管理后台配置保存接口已添加键名白名单验证

* [x] install目录已限制访问，update.php已添加install.lock检查

* [x] 所有认证Cookie已设置HttpOnly、Secure、SameSite标志

* [x] IP获取函数已修复，默认使用REMOTE\_ADDR

* [x] cURL SSL证书验证已启用

* [x] 文件上传已添加MIME类型验证

* [x] 安全随机数生成已从mt\_rand改为random\_bytes/random\_int

* [x] 订单号随机性已增强（5位→8位数字）

* [x] 签名比较已从==改为hash\_equals

* [x] cron.php密钥验证已加强（hash\_equals + 支持POST）

* [x] api.php密钥比对已改用hash\_equals防止时序攻击

* [x] SQL错误信息不再泄露给前端

