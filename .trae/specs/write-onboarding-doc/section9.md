# 九、常见问题解决方案

## 9.1 支付相关问题

### 9.1.1 签名验证失败

**症状**：提交支付时页面提示"签名校验失败，请返回重试！"

**原因分析**：

签名验证失败是接入过程中最常见的问题，其核心逻辑位于 `submit.php` 第32-37行。系统首先对请求参数执行三步预处理（过滤空值和sign/sign_type参数 → 按key的ASCII升序排序 → 拼接为key=value&格式字符串），然后将拼接字符串与商户密钥拼接后做MD5运算，将结果与请求中的sign参数进行比对。任何一个环节不一致都会导致验证失败。

1. **密钥(key)配置错误**：商户在请求中使用的pid对应的密钥与数据库 `pre_user` 表中存储的key不一致，可能是复制时多了空格、遗漏字符或使用了旧密钥。
2. **参数编码问题**：对参数值做了URL编码后再参与签名计算。签名时应使用原始值，URL编码仅在最终拼接到回调URL时使用（参见 `PayUtils::createLinkstringUrlencode` 方法）。
3. **参数排序不正确**：未按参数名的ASCII码升序排列。系统使用 `ksort()` 函数排序，必须严格按字典序。
4. **空值未过滤**：签名前未移除空值参数和sign、sign_type参数。`PayUtils::paraFilter()` 方法会过滤掉 `key=="sign"`、`key=="sign_type"` 以及 `val==""` 的参数。
5. **参数名称拼写错误**：如将 `out_trade_no` 写成 `out_trade_no` 以外的其他形式，或pid/type/money等参数名写错，导致签名串不一致。

**解决方案**：

1. **核对商户密钥**：登录管理后台，确认商户ID（pid）对应的密钥（key）与请求中使用的完全一致，注意去除首尾空格。
2. **确保参数值未做URL编码**：签名时使用参数的原始值，不要对值进行 `urlencode()` 处理。系统在回调通知中会使用 `createLinkstringUrlencode()` 单独对URL参数编码，签名计算与URL编码是独立的。
3. **确保参数按key的ASCII升序排序**：使用 `ksort()` 函数或等效的字典序排序算法。例如参数 `pid=1001&money=1.00&type=alipay` 排序后应为 `money=1.00&pid=1001&type=alipay`。
4. **签名前过滤空值和sign/sign_type参数**：移除值为空的参数，移除参数名为 `sign` 和 `sign_type` 的参数，然后再排序和拼接。
5. **签名调试方法**：按以下步骤手动验证签名：
   ```
   步骤1：准备所有参数（排除sign和sign_type，排除空值）
   步骤2：按参数名ASCII升序排序
   步骤3：用&拼接为 key1=value1&key2=value2 格式
   步骤4：在拼接串末尾直接追加商户密钥（无分隔符）
   步骤5：对整个字符串做MD5运算，得到32位小写签名
   ```
   示例：参数 `pid=1001&money=1.00&type=alipay&out_trade_no=TEST001`，密钥为 `abc123`，则签名字符串为 `money=1.00&out_trade_no=TEST001&pid=1001&type=alipayabc123`，对此字符串做MD5即为sign值。

---

### 9.1.2 回调通知未收到

**症状**：订单已支付成功，但商户系统始终未收到异步通知（notify_url未被调用）

**原因分析**：

回调通知的发送逻辑位于 `functions.php` 的 `creat_callback()` 和 `do_notify()` 函数。系统在订单支付成功后，通过 `curl_get()` 函数以GET方式请求商户的 `notify_url`，并期望商户返回包含"success"（不区分大小写）的字符串。如果商户未返回"success"，系统会按照1分钟、3分钟、20分钟、1小时、2小时的间隔进行最多5次重试。

1. **notify_url无法从公网访问**：商户填写的通知地址是内网地址（如 `http://localhost/`、`http://192.168.x.x/`）或域名未正确解析，导致支付平台服务器无法发起请求。
2. **商户服务器未返回"success"**：`do_notify()` 函数检查响应中是否包含 `success`、`SUCCESS` 或 `Success` 字符串。如果商户回调页面返回的是其他内容（如"ok"、"1"、JSON数据等），系统会认为通知失败并触发重试。
3. **HTTPS证书问题**：如果notify_url使用HTTPS协议，但证书过期、自签名或域名不匹配，`curl_get()` 虽然设置了 `CURLOPT_SSL_VERIFYPEER=false`，但某些中间网络设备可能仍会拦截。
4. **防火墙/安全组拦截**：商户服务器防火墙或云服务商安全组规则未放行来自支付平台服务器的IP地址。
5. **curl请求超时**：`curl_get()` 设置了5秒超时（`CURLOPT_TIMEOUT=5`），如果商户回调处理逻辑耗时过长，会导致请求超时。

**解决方案**：

1. **检查notify_url公网可达性**：在支付平台服务器上执行 `curl -v "商户notify_url"` 验证是否能正常访问。确保使用公网域名或IP，而非localhost或内网地址。
2. **确保回调处理返回"success"**：商户回调页面在处理完业务逻辑后，必须输出纯文本 `success`（不区分大小写），不要输出其他任何内容（包括HTML标签、空格、换行等）。
3. **检查HTTPS证书有效性**：使用 `curl -v "https://商户notify_url"` 测试，确保证书有效且未过期。如有问题，可临时使用HTTP协议或更换证书。
4. **检查服务器防火墙设置**：确认服务器80/443端口对支付平台服务器IP开放，检查云服务商安全组规则。
5. **手动触发通知重试**：访问 `http://支付平台域名/cron.php?do=notify&key=监控密钥` 手动触发通知重试。系统会查找 `notify>0` 且 `notifytime<当前时间` 的订单进行重新通知。
6. **重试已放弃的通知**：对于已重试5次后标记为 `notify=-1` 的订单，可访问 `cron.php?do=notify2&key=监控密钥` 再次尝试通知。
7. **检查回调处理耗时**：建议商户回调页面先输出"success"再执行业务逻辑，或使用异步处理，确保在5秒内响应。

---

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

---

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

---

### 9.1.5 订单重复支付

**症状**：提交支付时提示"该订单(xxx)已完成支付，请勿重复发起支付"或"该订单(xxx)支付参数有变化，请更换订单号重新发起支付"

**原因分析**：

订单重复提交检测逻辑位于 `submit.php` 第95-111行。系统根据商户ID（uid）和商户订单号（out_trade_no）查询已有订单，如果找到且在10天（864000秒）内，则进行以下判断：

1. **同一out_trade_no已支付成功**：如果旧订单的 `status>0`（已支付），系统直接拒绝并提示"该订单已完成支付，请勿重复发起支付"。
2. **24小时内同订单号参数变更**：如果旧订单未支付但参数（金额、商品名、通知地址、回调地址、附加参数）任一发生变化，系统拒绝并提示"支付参数有变化，请更换订单号重新发起支付"。

**解决方案**：

1. **商户系统应防止重复提交**：在用户支付完成后，商户系统应标记订单为已支付状态，避免用户再次点击支付按钮。前端可在支付跳转后禁用支付按钮，后端应在创建订单前检查本地订单状态。
2. **更换订单号重新发起**：如果确实需要重新发起支付（如参数有误），必须使用新的 `out_trade_no` 订单号。系统会为新订单号创建新的支付记录。
3. **注意订单号有效期**：同一商户ID+订单号的关联有效期为10天（864000秒），超过10天后同一订单号会被视为新订单。建议商户系统使用唯一且不重复的订单号生成策略。
4. **订单号格式要求**：`out_trade_no` 仅允许字母、数字、点号、下划线、连字符和竖线（正则：`/^[a-zA-Z0-9.\_\-|]+$/`），不符合格式会提示"订单号格式不正确"。

---

## 9.2 部署相关问题

### 9.2.1 URL重写不生效

**症状**：访问 `/pay/xxx` 或 `/xxx.html` 格式的URL返回404错误

**原因与解决方案**：

系统使用URL重写实现友好的URL格式，`.htaccess` 文件定义了两条重写规则：
- `^(.[a-zA-Z0-9\-\_]+).html$` → `index.php?mod=$1`（页面路由）
- `^pay/(.*)$` → `pay.php?s=$1`（支付链接路由）

**Apache环境**：

1. **未启用mod_rewrite模块**：编辑Apache配置文件（通常是 `httpd.conf`），取消 `LoadModule rewrite_module modules/mod_rewrite.so` 前的注释，重启Apache。
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

---

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

---

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

---

### 9.2.4 安装后无法访问

**症状**：访问网站提示"请先完成网站升级"或检测到无install.lock文件

**原因与解决方案**：

1. **版本号不匹配**：`common.php` 第76-82行检查 `$conf['version']` 与 `DB_VERSION` 常量（定义为 `2024`）是否一致。如果数据库中的版本号低于代码版本号，系统会提示升级。解决方案：访问 `/install/update.php` 执行数据库升级脚本，升级完成后版本号会自动更新。
2. **install.lock文件缺失**：`common.php` 第87-89行检查 `/install/install.lock` 文件是否存在。如果不存在且安装程序仍在，系统会提示安全警告。解决方案：在 `/install/` 目录下创建空的 `install.lock` 文件：
   ```bash
   touch /www/wwwroot/pay/install/install.lock
   ```
3. **数据库未安装**：如果 `config.php` 中数据库用户名、密码或数据库名为空，系统会提示"你还没安装"。解决方案：访问 `/install/` 完成安装向导。

---

## 9.3 安全相关问题

### 9.3.1 CC攻击防护

**症状**：访问页面时频繁跳转到"正在加载中"页面，需等待后才能正常访问

**原因与解决方案**：

这是 `security.php` 中 `cc_defender()` 函数的正常防护行为。当 `$is_defend=true` 时（默认开启），系统会对每个访问者进行Cookie验证：

1. **正常行为说明**：首次访问时，系统会设置一个基于IP和日期的验证Cookie（`sec_defend`），然后通过JavaScript刷新页面。浏览器执行JS后设置Cookie并重新加载，系统验证Cookie通过后放行。这是防CC攻击的正常机制，正常用户只会看到一次短暂的"正在加载中"跳转。
2. **Cookie被禁用**：如果用户浏览器禁用了Cookie，系统无法设置验证Cookie，会导致反复跳转。当重试次数达到10次（`sec_defend_time>=10`）时，页面会显示"浏览器不支持COOKIE或者不正常访问！"。解决方案：告知用户启用浏览器Cookie功能。
3. **关闭CC防护（不推荐）**：如果确实需要关闭，可以在页面文件中将 `$is_defend = true` 改为 `$is_defend = false`。但强烈不建议在生产环境关闭此功能，否则系统将失去对CC攻击的基本防护能力。
4. **蜘蛛屏蔽机制**：`txprotect.php` 会屏蔽已知的恶意蜘蛛和异常浏览器（包括Baiduspider、360Spider、python脚本、SemrushBot、HeadlessChrome等），返回404状态码。如果正常访问被误拦截，检查浏览器User-Agent是否包含被屏蔽的关键词。

---

### 9.3.2 域名未授权

**症状**：提交支付时提示"该域名不可发起支付，原因：域名没过白，请前往支付平台授权支付域名"

**原因与解决方案**：

当系统配置 `pay_domain_forbid=1` 时，`submit.php` 第71-75行会检查商户通知地址的域名是否在白名单中。系统查询 `pre_domain` 表，匹配条件为 `uid=商户ID` 且 `status=1` 且域名等于精确域名或通配符域名。

1. **添加域名白名单**：登录管理后台，在域名管理中为商户添加授权域名。域名必须与 `notify_url` 中的域名完全一致。
2. **通配符域名支持**：系统支持通配符域名格式 `*.example.com`。添加此格式后，所有子域名（如 `a.example.com`、`b.example.com`）均可通过验证。系统通过 `get_main_host()` 函数提取主域名进行匹配。
3. **域名验证逻辑**：系统先尝试精确匹配（`domain=完整域名`），再尝试通配符匹配（`domain=*.主域名`）。例如 `notify_url` 为 `http://shop.example.com/notify`，系统会查询 `domain='shop.example.com'` 或 `domain='*.example.com'` 的记录。
4. **关闭域名限制**：将系统配置 `pay_domain_forbid` 设为0可关闭域名白名单验证，但这会降低安全性，不建议在生产环境使用。

---

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

---

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
3. **检查PHP扩展**：确保安装了必要的PHP扩展，包括 pdo_mysql、curl、gd、mbstring、json、openssl 等。

---

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

---

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
     - `http`：HTTP代理（默认，CURLPROXY_HTTP）
     - `https`：HTTPS代理（CURLPROXY_HTTPS）
     - `sock4`：SOCKS4代理（CURLPROXY_SOCKS4）
     - `sock5`：SOCKS5代理（CURLPROXY_SOCKS5）

2. **验证代理连通性**：配置完成后，可通过测试支付功能验证代理是否正常工作。也可在服务器上手动测试：
   ```bash
   curl -x http://代理地址:端口 https://目标API地址
   ```

3. **常见问题排查**：
   - 代理认证失败：检查用户名和密码是否正确
   - 代理超时：检查代理服务器是否正常运行，网络是否通畅
   - SSL错误：系统已设置 `CURLOPT_SSL_VERIFYPEER=false`，如仍有SSL问题，检查代理是否支持HTTPS转发

---

### 9.4.4 CDN配置问题

**症状**：页面静态资源（JS、CSS、字体等）加载失败，或加载缓慢

**原因与解决方案**：

`common.php` 第91-99行根据 `cdnpublic` 配置决定静态资源的CDN源：

| cdnpublic值 | CDN源 | 地址 |
|---|---|---|
| 1 | 宝塔CDN | `//lib.baomitu.com/` |
| 2 | BootCDN | `https://cdn.bootcdn.net/ajax/libs/` |
| 4 | 字节跳动CDN | `//lf26-cdn-tos.bytecdntp.com/cdn/expire-1-M/` |
| 其他（默认3） | Staticfile CDN | `//cdn.staticfile.org/` |

1. **CDN源不可访问**：如果选择的CDN源在某些地区或网络环境下不可访问，会导致静态资源加载失败。解决方案：在管理后台切换到其他CDN源（修改 `cdnpublic` 配置值），或设为0使用默认的Staticfile CDN。
2. **协议头问题**：CDN地址使用 `//` 开头的协议相对路径，依赖页面协议自动选择HTTP或HTTPS。如果页面是HTTPS但CDN源不支持HTTPS，会导致资源加载失败。解决方案：选择支持HTTPS的CDN源（如BootCDN使用 `https://` 开头）。
3. **自定义CDN**：如果需要使用自建CDN或其他公共CDN，需修改 `common.php` 中的CDN配置逻辑，添加新的选项分支。
4. **CDN缓存问题**：更新系统后如果静态资源未刷新，可能是CDN缓存未过期。解决方案：清除CDN缓存，或在资源URL后添加版本号参数强制刷新。
