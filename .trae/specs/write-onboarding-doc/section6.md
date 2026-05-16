# 六、环境配置指南

## 6.1 服务器环境要求

### 软件版本要求

| 组件 | 最低版本 | 推荐版本 |
|------|---------|---------|
| PHP | >= 7.1 | 7.4 / 8.0 |
| MySQL | >= 5.5 | 5.7 / 8.0 |
| Web 服务器 | Apache / Nginx | Nginx + PHP-FPM |

### PHP 扩展要求

以下扩展为系统运行所必需，安装程序会自动检测：

| 扩展 | 说明 | 必需 |
|------|------|------|
| pdo_mysql | 数据库连接驱动（PDO 方式） | 是 |
| curl | HTTP 请求，用于支付接口通信 | 是 |
| gd | 图像处理，验证码生成 | 是 |
| mbstring | 多字节字符串处理 | 是 |
| json | JSON 编解码 | 是 |
| openssl | 加密与 HTTPS 支持 | 是 |

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

---

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

1. **环境检测** — 自动检测 PHP 版本（>=7.1）、PDO_MYSQL 组件、CURL 组件、目录写入权限
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

---

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

| 规则 | 说明 |
|------|------|
| `^(.[a-zA-Z0-9\-\_]+).html$` → `index.php?mod=$1` | 将静态化页面 URL 重写到 `index.php` 的 `mod` 参数 |
| `^/pay/(.*)$` → `pay.php?s=$1` | 将支付页面 URL 重写到 `pay.php` 的 `s` 参数 |
| `location ^~ /plugins` | 禁止直接访问插件目录，防止源码泄露 |
| `location ^~ /includes` | 禁止直接访问核心类库目录，防止源码泄露 |

---

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

| 规则 | 说明 |
|------|------|
| `Options +FollowSymlinks` | 允许跟随符号链接 |
| `RewriteEngine On` | 启用 URL 重写引擎 |
| `RewriteCond %{REQUEST_FILENAME} !-d` | 条件：请求的路径不是已存在的目录 |
| `RewriteCond %{REQUEST_FILENAME} !-f` | 条件：请求的路径不是已存在的文件 |
| `RewriteRule ^(.[a-zA-Z0-9\-\_]+).html$` | 将 `.html` 结尾的 URL 重写到 `index.php?mod=` 参数 |
| `RewriteRule ^pay/(.*)$` | 将 `/pay/` 开头的 URL 重写到 `pay.php?s=` 参数 |
| `[QSA]` | Query String Append，保留原始查询参数 |
| `[PT]` | Pass Through，将重写结果传递给下一个处理器 |
| `[L]` | Last，匹配后不再继续处理后续规则 |

### 启用 mod_rewrite

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

---

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

| 规则 | 说明 |
|------|------|
| `payrule1_rewrite` | 将 `.html` 结尾的 URL 重写到 `index.php?mod=`，仅在请求的文件/目录不存在时生效 |
| `payrule2_rewrite` | 将 `/pay/` 开头的 URL 重写到 `pay.php?s=`，仅在请求的文件/目录不存在时生效 |
| `stopProcessing="true"` | 匹配后不再处理后续规则 |
| `negate="true"` | 条件取反（即文件不存在且目录不存在时才重写） |

### 前置条件

- 安装 IIS URL Rewrite 2.0 模块：可通过 Microsoft Web Platform Installer 安装
- 确保已安装 PHP 并正确配置 IIS 与 PHP 的集成（通过 FastCGI 方式）

---

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

   | 通知次数 | 距上次通知的间隔 |
   |---------|----------------|
   | 第 1 次 → 第 2 次 | 2 分钟 |
   | 第 2 次 → 第 3 次 | 16 分钟 |
   | 第 3 次 → 第 4 次 | 36 分钟 |
   | 第 4 次 → 第 5 次 | 1 小时 |
   | 超过 5 次 | 标记为失败（`notify=-1`） |

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

---

## 6.7 CDN 配置

系统支持 4 种公共 CDN 源，用于加载前端静态资源（如 Bootstrap、jQuery 等）。CDN 选项在后台"系统设置"中配置，对应 [common.php](file:///www/wwwroot/pay/includes/common.php#L91-L99) 中的 `cdnpublic` 参数。

### CDN 选项

| 选项值 | CDN 名称 | CDN 前缀地址 | 说明 |
|--------|---------|-------------|------|
| 0 | StaticFile CDN | `//cdn.staticfile.org/` | 默认选项，由国内七牛云提供，稳定性好 |
| 1 | 宝塔 CDN | `//lib.baomitu.com/` | 宝塔面板旗下 CDN，适合宝塔用户 |
| 2 | BootCDN | `https://cdn.bootcdn.net/ajax/libs/` | 国内老牌 CDN 服务，资源丰富 |
| 4 | 字节 CDN | `//lf26-cdn-tos.bytecdntp.com/cdn/expire-1-M/` | 字节跳动旗下 CDN，国内访问速度快 |

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
