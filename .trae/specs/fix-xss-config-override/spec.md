# 修复XSS漏洞与任意配置覆盖漏洞 Spec

## Why
支付系统后台存在多处XSS漏洞（用户输入/数据库字段未转义直接输出到HTML）和任意配置覆盖漏洞（未对配置键做白名单校验），可被攻击者利用执行恶意脚本或覆盖敏感配置项（如管理员密码、系统密钥）。

## What Changes
- 修复 `admin688/set.php` 中约31处 `$conf['xxx']` 输出到HTML属性/内容时未使用 `htmlspecialchars` 转义的XSS漏洞
- 修复 `admin688/ajax.php` 中 `set` case 的任意配置覆盖漏洞，添加配置键白名单校验

## Impact
- Affected code: `admin688/set.php`, `admin688/ajax.php`
- 已完成修复的文件（无需再改）：`login.php`, `uset.php`, `ajax_user.php`, `ajax_settle.php`, `ajax_pay.php`

## ADDED Requirements

### Requirement: set.php XSS输出转义
所有 `$conf['xxx']` 输出到HTML属性（value="", default=""）或HTML内容（textarea之间、URL中）时，必须使用 `htmlspecialchars($conf['xxx'], ENT_QUOTES, 'UTF-8')` 转义。

条件表达式（如 `$conf['reg_open']==0?'display:none;':null`）不需要转义，因为它们是内部逻辑控制，不输出用户可控数据。

#### Scenario: 恶意配置值包含脚本
- **WHEN** 数据库中某配置值被注入 `<script>alert(1)</script>`
- **THEN** 在set.php页面中该值被输出时，`htmlspecialchars` 将其转义为 `&lt;script&gt;alert(1)&lt;/script&gt;`，脚本不会执行

### Requirement: ajax.php 配置键白名单
`ajax.php` 的 `set` case 必须对 `$_POST` 的键进行白名单校验，只允许预定义的配置键通过 `saveSetting()` 保存。敏感键（admin_user, admin_pwd, admin_paypwd, syskey, cronkey）不得出现在白名单中。

#### Scenario: 攻击者尝试覆盖管理员密码
- **WHEN** 攻击者发送 POST 请求包含 `admin_pwd=attacker123`
- **THEN** 由于 `admin_pwd` 不在白名单中，该键值对被跳过，管理员密码不被覆盖

#### Scenario: 合法配置更新
- **WHEN** 管理员通过后台正常修改配置（如 `kfqq`, `description` 等白名单内的键）
- **THEN** 配置正常保存，功能不受影响
