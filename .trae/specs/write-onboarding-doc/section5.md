# 五、数据库结构设计

> **说明**：所有表名中的 `pre_` 为表前缀占位符，实际表前缀由 [config.php](file:///www/wwwroot/pay/config.php) 中的 `dbqz` 配置决定（默认为 `pay_`）。例如 `pre_config` 实际对应 `pay_config`，`pre_order` 实际对应 `pay_order`，以此类推。
>
> 所有表均使用 InnoDB 引擎，字符集为 utf8。

---

## 5.1 系统配置相关表

### 5.1.1 pre_config（系统配置表）

系统全局配置的键值对存储表，采用 KV 结构，所有配置项均以键值对形式存储。

| 字段名 | 类型 | 默认值 | 说明 |
|--------|------|--------|------|
| k | varchar(32) | - | 配置键名（主键），唯一标识一个配置项 |
| v | text | NULL | 配置值，可为空 |

**已知配置项详细说明（从 install.sql 及代码中提取）：**

#### 基础配置

| 配置键 | 默认值 | 说明 |
|--------|--------|------|
| version | 2024 | 系统版本号，用于版本判断和升级检测 |
| admin_user | admin | 管理后台登录用户名 |
| admin_pwd | 123456 | 管理后台登录密码（MD5 加密存储） |
| admin_paypwd | 123456 | 管理后台支付/操作密码，用于敏感操作二次验证（MD5 加密存储） |
| sitename | 聚合易支付 | 站点名称，显示在页面标题和品牌位置 |
| title | 聚合易支付 - 行业领先的免签约支付平台 | 站点标题（SEO title） |
| keywords | 聚合易支付,支付宝免签约即时到账,... | 站点关键词（SEO keywords） |
| description | 聚合易支付是XX公司旗下的免签约支付产品... | 站点描述（SEO description） |
| orgname | XX公司 | 运营主体公司名称，显示在页面底部和结算信息中 |
| kfqq | 123456789 | 客服QQ号，显示在页面联系信息中 |
| template | index11 | 前台模板目录名称，对应 template 目录下的子目录 |
| homepage | 0 | 首页显示模式，0=默认首页，1=自定义首页（frame 嵌入） |
| homepage_url | （空） | 自定义首页URL，当 homepage=1 时以 frame 方式显示该URL |
| localurl | （空） | 本站点URL地址，必须以 http:// 或 https:// 开头并以 / 结尾，填错会导致订单无法回调 |
| cdnpublic | （空） | 静态资源CDN地址，用于加速前端资源加载 |
| syskey | （空） | 系统密钥，用于加密签名等安全操作，在 common.php 中定义为常量 SYS_KEY |

#### 支付配置

| 配置键 | 默认值 | 说明 |
|--------|--------|------|
| pay_maxmoney | 1000 | 最大支付金额（元），0 表示不限制，超过此金额的订单将被拒绝 |
| pay_minmoney | （空） | 最小支付金额（元），低于此金额的订单将被拒绝 |
| pay_payaddstart | （空） | 订单金额随机增减起始阈值，订单满此金额后触发随机增减，留空不启用 |
| pay_payaddmin | （空） | 随机增减最小金额，负数表示减少 |
| pay_payaddmax | （空） | 随机增减最大金额，负数表示减少 |
| pay_domain_open | 0 | 域名白名单开关，0=关闭，1=开启（开启后仅白名单域名可发起支付） |
| pay_domain_forbid | 0 | 域名黑名单开关，0=关闭，1=开启（开启后黑名单域名禁止发起支付） |
| localurl_alipay | （空） | 支付宝专用跳转URL，适用于多域名场景下支付宝域名限制，留空使用当前网址 |
| localurl_wxpay | （空） | 微信支付专用跳转URL，适用于多域名场景下微信公众号域名授权限制，留空使用当前网址 |

#### 结算配置

| 配置键 | 默认值 | 说明 |
|--------|--------|------|
| settle_open | 1 | 结算功能开关，0=关闭，1=开启 |
| settle_type | 1 | 结算方式，1=自动结算（达到最低结算金额自动进入结算），0=手动结算 |
| settle_money | 30 | 最低结算金额（元），商户可用余额需达到此金额才能申请结算 |
| settle_rate | 0.5 | 结算手续费率（百分比），如 0.5 表示 0.5% |
| settle_fee_min | 0.1 | 单笔结算最低手续费（元） |
| settle_fee_max | 20 | 单笔结算最高手续费（元） |
| settle_alipay | 1 | 支持支付宝结算，0=不支持，1=支持 |
| settle_wxpay | 1 | 支持微信结算，0=不支持，1=支持 |
| settle_qqpay | 1 | 支持QQ钱包结算，0=不支持，1=支持 |
| settle_bank | 0 | 支持银行卡结算，0=不支持，1=支持 |

#### 转账配置

| 配置键 | 默认值 | 说明 |
|--------|--------|------|
| transfer_alipay | 0 | 支付宝自动转账开关，0=关闭，1=开启 |
| transfer_wxpay | 0 | 微信自动转账开关，0=关闭，1=开启 |
| transfer_qqpay | 0 | QQ钱包自动转账开关，0=关闭，1=开启 |
| transfer_name | 聚合易支付 | 自动转账付款方名称 |
| transfer_desc | 聚合易支付自动结算 | 自动转账备注描述 |

#### 登录配置

| 配置键 | 默认值 | 说明 |
|--------|--------|------|
| login_qq | 0 | QQ登录开关，0=关闭，1=开启 |
| login_alipay | 0 | 支付宝登录开关，0=关闭，1=开启 |
| login_wx | 0 | 微信登录开关，0=关闭，1=开启 |
| login_alipay_channel | 0 | 支付宝登录使用的支付通道ID，0=默认 |
| login_wx_channel | 0 | 微信登录使用的支付通道ID，0=默认 |

#### 注册配置

| 配置键 | 默认值 | 说明 |
|--------|--------|------|
| reg_open | 1 | 注册功能开关，0=关闭，1=开启 |
| reg_pay | 1 | 付费注册开关，0=免费注册，1=需支付费用后注册 |
| reg_pay_uid | 1000 | 付费注册收款商户UID，注册费用将打入该商户账户 |
| reg_pay_price | 5 | 付费注册价格（元） |

#### 验证配置

| 配置键 | 默认值 | 说明 |
|--------|--------|------|
| verifytype | 1 | 验证码类型，1=邮件验证 |
| captcha_open | 1 | 图形验证码开关，0=关闭，1=开启 |
| captcha_id | （空） | 验证码服务ID（如极验等第三方验证码平台） |
| captcha_key | （空） | 验证码服务密钥 |
| onecode | 1 | 登录二次验证开关，0=关闭，1=开启（登录时需输入邮件验证码） |

#### 邮件短信

| 配置键 | 默认值 | 说明 |
|--------|--------|------|
| mail_cloud | 0 | 云邮件服务开关，0=使用SMTP发送，1=使用云邮件服务 |
| mail_smtp | smtp.qq.com | SMTP邮件服务器地址 |
| mail_port | 465 | SMTP邮件服务器端口 |
| mail_name | （空） | SMTP登录用户名/邮箱地址 |
| mail_pwd | （空） | SMTP登录密码/授权码 |
| sms_api | 0 | 短信API开关，0=关闭，其他值为短信服务商标识 |

#### 实名认证

| 配置键 | 默认值 | 说明 |
|--------|--------|------|
| cert_open | 0 | 实名认证方式，0=关闭，1=支付宝身份验证，2=手机号三要素实名认证，3=支付宝实名信息验证，4=微信扫码实名认证，5=阿里云金融级实人认证 |
| cert_force | 0 | 强制实名认证开关，0=关闭，1=开启（开启后商户必须实名认证才能正常使用支付接口收款） |
| cert_appcode | （空） | 实名认证API授权码（用于手机号三要素等认证方式） |
| cert_appcode2 | （空） | 实名认证API授权码2（用于阿里云金融级实人认证等） |

#### 其他配置

| 配置键 | 默认值 | 说明 |
|--------|--------|------|
| blockname | 云盘\|网盘\|Q币 | 商品名称违禁词，多个用\|分隔，包含这些词的商品名将被拦截 |
| blockalert | 温馨提醒该商品禁止出售... | 违禁商品拦截提示语 |
| blockips | （空） | IP黑名单，多个用\|分隔，匹配的IP将禁止访问 |
| blockusers | （空） | 买家黑名单，多个用\|分隔，只支持微信公众号支付和支付宝JS支付 |
| recharge | 1 | 余额充值功能开关，0=关闭，1=开启 |
| user_review | 0 | 商户注册审核开关，0=关闭（自动通过），1=开启（需管理员审核） |
| close_keylogin | 0 | 密钥登录开关，0=开启（商户可使用密钥登录），1=关闭 |
| cronkey | （空） | 计划任务密钥，用于验证定时任务请求的合法性 |
| test_open | 1 | 测试支付开关，0=关闭，1=开启 |
| test_pay_uid | 1000 | 测试支付收款商户UID |
| pageordername | 1 | 页面显示订单名称开关，0=隐藏，1=显示 |
| notifyordername | 1 | 回调通知订单名称开关，0=隐藏，1=显示 |

---

### 5.1.2 pre_cache（缓存表）

系统缓存键值对存储表，支持过期时间机制。

| 字段名 | 类型 | 默认值 | 说明 |
|--------|------|--------|------|
| k | varchar(32) | - | 缓存键名（主键），唯一标识一个缓存项 |
| v | longtext | NULL | 缓存值，使用 longtext 类型可存储大体积数据（如序列化后的配置、列表等） |
| expire | int(11) | 0 | 过期时间戳（Unix 时间戳），0 表示永不过期 |

---

## 5.2 支付相关表

### 5.2.1 pre_type（支付方式表）

定义系统支持的所有支付方式，是支付路由的基础数据表。

| 字段名 | 类型 | 默认值 | 说明 |
|--------|------|--------|------|
| id | int(11) unsigned | AUTO_INCREMENT | 支付方式ID（主键），自增 |
| name | varchar(30) | - | 支付方式英文标识码，如 alipay、wxpay 等，用于代码中引用 |
| device | int(1) unsigned | 0 | 设备类型，0=通用/PC，其他值可区分不同设备端 |
| showname | varchar(30) | - | 支付方式中文显示名称，如"支付宝"、"微信支付"等 |
| status | tinyint(1) | 0 | 启用状态，0=禁用，1=启用 |

**索引：** PRIMARY(id), KEY name(name, device)

**初始数据：**

| id | name | device | showname | status |
|----|------|--------|----------|--------|
| 1 | alipay | 0 | 支付宝 | 1 |
| 2 | wxpay | 0 | 微信支付 | 1 |
| 3 | qqpay | 0 | QQ钱包 | 1 |
| 4 | bank | 0 | 网银支付 | 0 |
| 5 | jdpay | 0 | 京东支付 | 0 |
| 6 | paypal | 0 | PayPal | 0 |

> 注：id=1~3 为默认启用的支付方式，id=4~6 默认禁用，需管理员手动开启。

---

### 5.2.2 pre_plugin（支付插件表）

存储已安装的支付插件信息，每个插件对应一种第三方支付接口的对接实现。

| 字段名 | 类型 | 默认值 | 说明 |
|--------|------|--------|------|
| name | varchar(30) | - | 插件标识名（主键），如 alipay、wxpay 等，与插件目录名对应 |
| showname | varchar(60) | NULL | 插件显示名称，如"支付宝官方接口" |
| author | varchar(60) | NULL | 插件作者 |
| link | varchar(255) | NULL | 插件相关链接（如官网或文档地址） |
| types | varchar(50) | NULL | 插件支持的支付方式，逗号分隔的支付方式 name 值，如 "alipay,wxpay" |

> 注：update.sql 中还包含 `inputs`（text）和 `select`（text）两个字段，用于存储插件配置表单的输入项定义和选项定义，以序列化格式存储。这两个字段在代码中被使用（如 admin688/ajax_pay.php 中读取 `$plugin['inputs']` 和 `$plugin['select']`），但 install.sql 中未包含，属于增量更新新增字段。

---

### 5.2.3 pre_channel（支付通道表）

存储各个支付通道的详细配置，是支付路由的核心表。每个通道对应一个具体的支付接口实例（如某个支付宝商户号、某个微信商户号等）。

| 字段名 | 类型 | 默认值 | 说明 |
|--------|------|--------|------|
| id | int(11) unsigned | AUTO_INCREMENT | 通道ID（主键），自增 |
| mode | int(1) | 0 | 通道模式，0=普通模式，1=直清模式（直清模式下资金直接结算到商户账户） |
| type | int(11) unsigned | - | 支付方式ID，关联 pre_type.id |
| plugin | varchar(30) | - | 支付插件标识名，关联 pre_plugin.name |
| name | varchar(30) | - | 通道名称，如"支付宝官方-主通道" |
| rate | decimal(5,2) | 100.00 | 通道费率（百分比），如 100.00 表示 100%（即无折扣），95.00 表示 95% |
| status | tinyint(1) | 0 | 通道状态，0=禁用，1=启用 |
| appid | varchar(255) | NULL | 应用ID/商户AppID，不同插件的含义可能不同 |
| appkey | text | NULL | 应用密钥/API Key |
| appsecret | text | NULL | 应用密钥/App Secret |
| appurl | varchar(255) | NULL | 应用接口URL，用于自定义网关地址 |
| appmchid | varchar(255) | NULL | 商户号/MCH ID |
| apptype | varchar(50) | NULL | 支付类型代码，逗号分隔，如 "1,2" 表示支持支付方式ID为1和2的类型 |
| daytop | int(10) | 0 | 每日交易限额（分），0=不限制，超过后自动禁用该通道 |
| daystatus | int(1) | 0 | 每日限额状态，0=正常，1=已达限额（系统自动标记，次日重置） |
| paymin | varchar(10) | NULL | 单笔最小支付金额（元），低于此金额不路由到该通道 |
| paymax | varchar(10) | NULL | 单笔最大支付金额（元），高于此金额不路由到该通道 |
| appwxmp | int(11) | NULL | 关联微信公众号ID，关联 pre_weixin.id，用于微信公众号支付获取 openid |
| appwxa | int(11) | NULL | 关联微信小程序ID，关联 pre_weixin.id，用于微信小程序支付 |
| appswitch | tinyint(4) | NULL | 支付方式切换配置，用于控制该通道在不同场景下的支付方式切换行为 |

**索引：** PRIMARY(id), KEY type(type)

**mode 字段详细说明：**

| 值 | 含义 | 说明 |
|----|------|------|
| 0 | 普通模式 | 资金先进入平台账户，再通过结算流程转给商户 |
| 1 | 直清模式 | 资金直接结算到商户账户，平台仅收取手续费 |

---

### 5.2.4 pre_roll（通道轮询组表）

存储支付通道轮询组配置，实现多通道负载均衡和故障转移。

| 字段名 | 类型 | 默认值 | 说明 |
|--------|------|--------|------|
| id | int(11) unsigned | AUTO_INCREMENT | 轮询组ID（主键），自增（起始值101） |
| type | int(11) unsigned | - | 支付方式ID，关联 pre_type.id，表示该轮询组对应的支付方式 |
| name | varchar(30) | - | 轮询组名称，如"支付宝轮询组A" |
| kind | int(1) unsigned | 0 | 轮询策略，0=顺序轮询，1=加权随机轮询 |
| info | text | NULL | 轮询通道配置信息，JSON 格式 |
| status | tinyint(1) | 0 | 轮询组状态，0=禁用，1=启用 |
| index | int(11) | 0 | 当前轮询索引（用于顺序轮询时记录上次使用的通道位置） |

**索引：** PRIMARY(id)

**info 字段格式说明：**

info 字段存储 JSON 数组，每个元素代表一个通道及其权重配置：

```json
[
  {"channel": 1, "weight": 10},
  {"channel": 2, "weight": 5},
  {"channel": 3, "weight": 1}
]
```

- `channel`：通道ID，关联 pre_channel.id
- `weight`：权重值，在加权随机模式下，权重越大的通道被选中的概率越高

在顺序轮询模式（kind=0）下，系统按数组顺序依次选择通道，遇到不可用通道则跳过；在加权随机模式（kind=1）下，系统根据权重随机选择通道。

---

### 5.2.5 pre_weixin（微信公众号/小程序表）

存储微信公众号和小程序的配置信息，用于微信支付中的 openid 获取和公众号授权。

| 字段名 | 类型 | 默认值 | 说明 |
|--------|------|--------|------|
| id | int(11) unsigned | AUTO_INCREMENT | 记录ID（主键），自增 |
| type | tinyint(4) unsigned | 0 | 类型，0=微信公众号，1=微信小程序 |
| name | varchar(30) | - | 名称，如"主公众号"、"商城小程序" |
| status | tinyint(1) | 0 | 状态，0=禁用，1=启用 |
| appid | varchar(150) | NULL | 微信 AppID |
| appsecret | varchar(250) | NULL | 微信 AppSecret |

**索引：** PRIMARY(id)

**type 字段详细说明：**

| 值 | 含义 | 说明 |
|----|------|------|
| 0 | 微信公众号 | 用于微信公众号支付、获取用户 openid、微信登录等 |
| 1 | 微信小程序 | 用于微信小程序支付、小程序授权登录等 |

> 注：该表通过 pre_channel 的 appwxmp 和 appwxa 字段与支付通道关联，一个公众号/小程序可被多个通道引用。

---

## 5.3 订单相关表

### 5.3.1 pre_order（订单表）

存储所有支付订单信息，是系统最核心的业务数据表。

| 字段名 | 类型 | 默认值 | 说明 |
|--------|------|--------|------|
| trade_no | char(19) | - | 系统订单号（主键），19位定长字符串，格式为 YmdHis+5位随机数，如 2024010112000012345 |
| out_trade_no | varchar(150) | - | 商户订单号，由商户提交的唯一订单标识 |
| api_trade_no | varchar(150) | NULL | 第三方支付平台交易号，如支付宝交易号、微信支付交易号等 |
| uid | int(11) unsigned | - | 商户ID，关联 pre_user.uid |
| tid | tinyint(11) unsigned | 0 | 订单类型，详见下方说明 |
| type | int(10) unsigned | - | 支付方式ID，关联 pre_type.id |
| channel | int(10) unsigned | - | 支付通道ID，关联 pre_channel.id |
| name | varchar(64) | - | 商品名称 |
| money | decimal(10,2) | - | 订单金额（元），商户提交的原始金额 |
| realmoney | decimal(10,2) | NULL | 实际支付金额（元），可能与订单金额不同（如随机增减后） |
| getmoney | decimal(10,2) | NULL | 商户到账金额（元），扣除手续费后的金额 |
| notify_url | varchar(255) | NULL | 异步通知回调地址，支付成功后系统向该URL发送通知 |
| return_url | varchar(255) | NULL | 同步跳转地址，支付完成后浏览器跳转到该URL |
| param | varchar(255) | NULL | 商户自定义参数，原样返回给商户，可用于传递业务附加信息 |
| addtime | datetime | NULL | 订单创建时间 |
| endtime | datetime | NULL | 订单完成时间（支付成功或关闭的时间） |
| date | date | NULL | 订单日期，用于按日期查询和统计 |
| domain | varchar(64) | NULL | 发起支付的域名 |
| domain2 | varchar(64) | NULL | 实际跳转支付时的域名（多域名场景下可能与 domain 不同） |
| ip | varchar(20) | NULL | 发起支付的客户端IP地址 |
| buyer | varchar(30) | NULL | 买家标识（如支付宝买家账号等） |
| status | tinyint(1) | 0 | 订单状态，详见下方说明 |
| notify | int(5) | 0 | 通知状态，详见下方说明 |
| notifytime | datetime | NULL | 最后一次通知时间 |
| invite | int(11) unsigned | 0 | 邀请人商户ID，关联 pre_user.uid，0 表示无邀请人 |
| invitemoney | decimal(10,2) | NULL | 邀请返利金额（元），支付成功后邀请人获得的返利 |

**索引：**
- PRIMARY(trade_no) — 系统订单号主键索引
- KEY uid(uid) — 商户ID索引，用于查询商户订单
- KEY out_trade_no(out_trade_no, uid) — 商户订单号+商户ID联合索引，用于按商户订单号查询
- KEY api_trade_no(api_trade_no) — 第三方交易号索引，用于对账查询
- KEY invite(invite) — 邀请人索引，用于统计邀请返利
- KEY date(date) — 日期索引，用于按日期范围查询和统计

**tid（订单类型）详细说明：**

| 值 | 含义 | 说明 |
|----|------|------|
| 0 | 普通订单 | 商户通过API提交的标准支付订单 |
| 1 | 商户注册 | 付费注册商户时产生的支付订单 |
| 2 | 余额充值 | 商户充值账户余额时产生的支付订单 |
| 3 | 聚合收款码 | 通过聚合收款码产生的支付订单 |
| 4 | 购买用户组 | 商户购买/升级用户组时产生的支付订单 |

**status（订单状态）详细说明：**

| 值 | 含义 | 说明 |
|----|------|------|
| 0 | 未支付 | 订单已创建，等待买家付款 |
| 1 | 已支付 | 买家已完成付款，资金已入账 |
| 2 | 已关闭 | 订单超时未支付或被手动关闭 |

**notify（通知状态）详细说明：**

| 值 | 含义 | 说明 |
|----|------|------|
| 0 | 已通知 | 异步通知已成功发送并得到商户确认响应 |
| >0 | 待重试 | 异步通知发送失败，数值表示剩余重试次数 |
| -1 | 重试失败 | 异步通知重试次数已用尽，最终失败 |

> 注：通知重试机制采用递减计数方式，每次重试后 notify 值减1，减至0时标记为已通知成功，若重试次数耗尽仍未成功则标记为 -1。

---

## 5.4 用户相关表

### 5.4.1 pre_user（商户/用户表）

存储所有商户（用户）的完整信息，包括账户、认证、资金、权限等，是用户体系的核心表。自增ID从1000开始。

| 字段名 | 类型 | 默认值 | 说明 |
|--------|------|--------|------|
| uid | int(11) unsigned | AUTO_INCREMENT | 商户ID（主键），自增（起始值1000） |
| gid | int(11) unsigned | 0 | 用户组ID，关联 pre_group.gid，0=默认用户组 |
| upid | int(11) unsigned | 0 | 上级邀请人商户ID，关联 pre_user.uid，0=无上级 |
| key | varchar(32) | - | 商户密钥，用于API签名验证，注册时自动生成 |
| pwd | varchar(32) | NULL | 登录密码（MD5加密存储） |
| account | varchar(128) | NULL | 登录账号（邮箱或手机号） |
| username | varchar(128) | NULL | 商户显示名称/昵称 |
| codename | varchar(32) | NULL | 商户编码名称，用于特定场景标识 |
| settle_id | tinyint(4) | 1 | 默认结算方式，1=支付宝，2=微信，3=QQ钱包，4=银行卡 |
| alipay_uid | varchar(32) | NULL | 绑定的支付宝用户ID（用于支付宝登录和结算） |
| qq_uid | varchar(32) | NULL | 绑定的QQ OpenID（用于QQ登录） |
| wx_uid | varchar(32) | NULL | 绑定的微信OpenID（用于微信登录） |
| money | decimal(10,2) | - | 账户可用余额（元） |
| email | varchar(32) | NULL | 邮箱地址 |
| phone | varchar(20) | NULL | 手机号码 |
| qq | varchar(20) | NULL | QQ号码 |
| url | varchar(64) | NULL | 商户网站URL |
| cert | tinyint(4) | 0 | 实名认证状态，0=未认证，1=已认证 |
| certtype | tinyint(4) | 0 | 实名认证类型，0=个人认证，1=企业认证 |
| certmethod | tinyint(4) | 0 | 实名认证方式，对应 cert_open 配置的认证方式代码 |
| certno | varchar(18) | NULL | 身份证号码（个人认证）或统一社会信用代码（企业认证） |
| certname | varchar(32) | NULL | 真实姓名（个人认证）或企业名称（企业认证） |
| certtime | datetime | NULL | 实名认证通过时间 |
| certtoken | varchar(64) | NULL | 实名认证令牌，用于认证流程中的临时凭证 |
| certcorpno | varchar(30) | NULL | 企业营业执照号（企业认证时使用） |
| certcorpname | varchar(80) | NULL | 企业名称（企业认证时使用） |
| addtime | datetime | NULL | 注册时间 |
| lasttime | datetime | NULL | 最后登录时间 |
| endtime | datetime | NULL | 用户组到期时间（购买的用户组有过期时间） |
| level | tinyint(1) | 1 | 商户等级，1=普通商户 |
| pay | tinyint(1) | 1 | 支付权限，0=禁止，1=允许（禁止后商户无法发起支付） |
| settle | tinyint(1) | 1 | 结算权限，0=禁止，1=允许（禁止后商户无法申请结算） |
| keylogin | tinyint(1) | 1 | 密钥登录权限，0=禁止，1=允许 |
| apply | tinyint(1) | 0 | 审核状态，0=已通过/无需审核，1=待审核（配合 user_review 配置使用） |
| mode | tinyint(4) | 0 | 商户模式，0=普通商户，1=直清商户 |
| status | tinyint(4) | 0 | 账户状态，0=正常，1=禁用 |
| refund | tinyint(1) | 0 | 退款权限，0=禁止，1=允许 |
| channelinfo | text | NULL | 商户自定义通道配置，JSON 格式，可覆盖用户组默认通道配置 |
| ordername | varchar(255) | NULL | 商户自定义订单名称，用于覆盖原始订单名称显示 |

**索引：** PRIMARY(uid), KEY email(email), KEY phone(phone)

> 注：uid 自增起始值为 1000，即第一个注册的商户 ID 为 1000，避免与系统内部 ID 冲突。

---

### 5.4.2 pre_group（用户组表）

定义商户用户组及其权限配置，不同用户组可拥有不同的费率和通道权限。

| 字段名 | 类型 | 默认值 | 说明 |
|--------|------|--------|------|
| gid | int(11) unsigned | AUTO_INCREMENT | 用户组ID（主键），自增（gid=0 为默认用户组） |
| name | varchar(30) | - | 用户组名称，如"默认用户组"、"VIP用户组" |
| info | varchar(1024) | NULL | 用户组通道费率配置，JSON 格式 |
| isbuy | tinyint(1) | 0 | 是否可购买，0=不可购买，1=可购买 |
| price | decimal(10,2) | NULL | 购买价格（元），isbuy=1 时有效 |
| sort | int(10) | 0 | 排序权重，数值越大越靠前 |
| expire | int(10) | 0 | 有效期（天），0=永久有效 |
| settle_open | int(1) | 0 | 用户组结算开关，0=继承全局配置，1=开启 |
| settle_type | int(1) | 0 | 用户组结算方式，0=继承全局配置，1=自动结算 |
| settings | text | NULL | 用户组其他设置，JSON 格式，存储扩展配置 |

**索引：** PRIMARY(gid)

**info 字段 JSON 格式说明：**

info 字段存储各支付方式的通道和费率配置，以支付方式ID为键：

```json
{
  "1": {"type": "", "channel": "-1", "rate": ""},
  "2": {"type": "", "channel": "-1", "rate": ""},
  "3": {"type": "", "channel": "-1", "rate": ""}
}
```

- 键为支付方式ID（字符串），如 "1"=支付宝，"2"=微信支付，"3"=QQ钱包
- `type`：指定支付方式，空字符串表示使用默认
- `channel`：指定通道ID，"-1"表示使用轮询组，空字符串表示使用默认，正整数表示指定通道ID
- `rate`：自定义费率（百分比），空字符串表示使用全局默认费率

**初始数据：**

| gid | name | info |
|-----|------|------|
| 0 | 默认用户组 | {"1":{"type":"","channel":"-1","rate":""},"2":{"type":"","channel":"-1","rate":""},"3":{"type":"","channel":"-1","rate":""}} |

> 注：默认用户组的 gid=0 是通过 INSERT 后 UPDATE 强制设置的，不是自增值。

---

### 5.4.3 pre_domain（授权域名表）

存储商户的授权域名信息，配合支付域名白名单/黑名单功能使用。

| 字段名 | 类型 | 默认值 | 说明 |
|--------|------|--------|------|
| id | int(11) unsigned | AUTO_INCREMENT | 记录ID（主键），自增 |
| uid | int(11) | 0 | 商户ID，关联 pre_user.uid |
| domain | varchar(128) | - | 授权域名，如 example.com |
| status | tinyint(1) | 0 | 审核状态，0=待审核，1=已通过 |
| addtime | datetime | NULL | 添加时间 |
| endtime | datetime | NULL | 审核通过时间 |

**索引：** PRIMARY(id), KEY domain(domain, uid)

> 注：当系统开启域名白名单（pay_domain_open=1）时，只有通过审核的域名才能发起支付请求。

---

## 5.5 资金相关表

### 5.5.1 pre_settle（结算记录表）

存储商户结算申请记录，记录每笔结算的详细信息。

| 字段名 | 类型 | 默认值 | 说明 |
|--------|------|--------|------|
| id | int(11) | AUTO_INCREMENT | 结算记录ID（主键），自增 |
| uid | int(11) | - | 商户ID，关联 pre_user.uid |
| batch | varchar(20) | NULL | 批量转账批次号，关联 pre_batch.batch |
| auto | int(1) | 1 | 结算方式，0=手动结算，1=自动结算 |
| type | int(1) | 1 | 结算类型，1=支付宝，2=微信，3=QQ钱包，4=银行卡 |
| account | varchar(128) | - | 收款账号（支付宝账号、微信号、银行卡号等） |
| username | varchar(128) | - | 收款人姓名 |
| money | decimal(10,2) | - | 结算金额（元），商户申请的结算金额 |
| realmoney | decimal(10,2) | - | 实际到账金额（元），扣除手续费后的金额 |
| addtime | datetime | NULL | 结算申请时间 |
| endtime | datetime | NULL | 结算完成时间 |
| status | int(1) | 0 | 结算状态，0=待审核，1=已审核待转账，2=已完成，3=已驳回 |
| transfer_status | int(1) | 0 | 转账状态，0=未转账，1=转账中，2=转账成功，3=转账失败 |
| transfer_result | varchar(64) | NULL | 转账结果描述 |
| transfer_date | datetime | NULL | 转账完成时间 |
| result | varchar(64) | NULL | 审核结果/备注信息 |

**索引：** PRIMARY(id), KEY uid(uid), KEY batch(batch)

---

### 5.5.2 pre_record（资金明细表）

记录商户账户的每一笔资金变动，用于资金流水查询和对账。

| 字段名 | 类型 | 默认值 | 说明 |
|--------|------|--------|------|
| id | int(11) | AUTO_INCREMENT | 记录ID（主键），自增 |
| uid | int(11) | - | 商户ID，关联 pre_user.uid |
| action | int(1) | 0 | 资金变动方向，1=收入，2=支出 |
| money | decimal(10,2) | - | 变动金额（元），始终为正数 |
| oldmoney | decimal(10,2) | - | 变动前余额（元） |
| newmoney | decimal(10,2) | - | 变动后余额（元） |
| type | varchar(20) | NULL | 变动类型描述，如 "订单收入"、"结算支出"、"充值" 等 |
| trade_no | varchar(64) | NULL | 关联订单号，关联 pre_order.trade_no |
| date | datetime | - | 变动时间 |

**索引：** PRIMARY(id), KEY uid(uid), KEY trade_no(trade_no)

**action 字段详细说明：**

| 值 | 含义 | 典型场景 |
|----|------|----------|
| 1 | 收入 | 订单支付成功到账、充值到账、邀请返利到账等 |
| 2 | 支出 | 结算扣款、退款扣款等 |

> 注：通过 oldmoney 和 newmoney 字段可以追溯每次变动前后的余额，确保资金变动的可审计性。

---

### 5.5.3 pre_batch（批量转账表）

存储批量转账的汇总信息，与 pre_settle 通过 batch 字段关联。

| 字段名 | 类型 | 默认值 | 说明 |
|--------|------|--------|------|
| batch | varchar(20) | - | 批次号（主键），唯一标识一次批量转账 |
| allmoney | decimal(10,2) | - | 批次总金额（元），该批次所有结算金额之和 |
| count | int(11) | 0 | 批次结算笔数 |
| time | datetime | NULL | 批次创建时间 |
| status | int(1) | 0 | 批次状态，0=待处理，1=处理中，2=已完成，3=部分失败 |

**索引：** PRIMARY(batch)

> 注：一个批次可包含多笔结算记录，通过 pre_settle.batch 字段关联。管理员可一次性审核并转账一个批次中的所有结算。

---

## 5.6 安全相关表

### 5.6.1 pre_risk（风控记录表）

记录系统风控拦截的详细信息，用于风险分析和审计。

| 字段名 | 类型 | 默认值 | 说明 |
|--------|------|--------|------|
| id | int(11) | AUTO_INCREMENT | 记录ID（主键），自增 |
| uid | int(11) | 0 | 商户ID，关联 pre_user.uid，0 表示非商户相关风控 |
| type | int(1) | 0 | 风控类型，0=违禁商品，1=黑名单IP，2=黑名单买家，3=域名限制等 |
| url | varchar(64) | NULL | 触发风控的请求URL |
| content | varchar(64) | NULL | 风控内容描述，如触发的违禁词、被拦截的IP等 |
| date | datetime | NULL | 风控触发时间 |
| status | int(1) | 0 | 处理状态，0=未处理，1=已处理 |

**索引：** PRIMARY(id), KEY uid(uid)

---

### 5.6.2 pre_alipayrisk（支付宝风控表）

存储来自支付宝风控系统的风险预警信息，专门用于对接支付宝风控通知。

| 字段名 | 类型 | 默认值 | 说明 |
|--------|------|--------|------|
| id | int(11) | AUTO_INCREMENT | 记录ID（主键），自增 |
| channel | int(10) unsigned | - | 支付通道ID，关联 pre_channel.id |
| pid | varchar(40) | - | 支付宝合作商户ID（Partner ID） |
| smid | varchar(40) | NULL | 支付宝二级商户ID（Sub Merchant ID） |
| tradeNos | varchar(40) | NULL | 关联的支付宝交易号 |
| risktype | varchar(40) | NULL | 风险类型代码 |
| risklevel | varchar(60) | NULL | 风险等级，如 HIGH、MEDIUM、LOW |
| riskDesc | varchar(500) | NULL | 风险描述信息 |
| complainTime | varchar(128) | NULL | 投诉时间 |
| complainText | varchar(500) | NULL | 投诉内容 |
| date | datetime | NULL | 记录创建时间 |
| status | tinyint(1) | 0 | 处理状态，0=未处理，1=已处理 |
| process_code | varchar(2) | NULL | 处理结果代码 |

**索引：** PRIMARY(id)

> 注：该表由 update2.sql 增量更新新增，用于接收和处理支付宝风控系统的回调通知，帮助平台及时识别和处理风险交易。

---

### 5.6.3 pre_log（登录日志表）

记录用户和管理员的登录日志，用于安全审计和异常检测。

| 字段名 | 类型 | 默认值 | 说明 |
|--------|------|--------|------|
| id | int(11) | AUTO_INCREMENT | 日志ID（主键），自增 |
| uid | int(11) | 0 | 用户ID，0 表示管理员登录 |
| type | varchar(20) | NULL | 登录类型，如 "login"（登录）、"keylogin"（密钥登录）等 |
| date | datetime | - | 登录时间 |
| ip | varchar(20) | NULL | 登录IP地址 |
| city | varchar(20) | NULL | IP归属地（城市） |
| data | text | NULL | 附加数据，如 User-Agent 等信息 |

**索引：** PRIMARY(id)

---

### 5.6.4 pre_regcode（注册验证码表）

存储注册和找回密码时发送的验证码信息。

| 字段名 | 类型 | 默认值 | 说明 |
|--------|------|--------|------|
| id | int(11) | AUTO_INCREMENT | 记录ID（主键），自增 |
| uid | int(11) | 0 | 关联用户ID，0 表示未关联 |
| type | int(1) | 0 | 验证码类型，0=注册验证码，1=找回密码验证码 |
| code | varchar(32) | - | 验证码内容 |
| to | varchar(32) | NULL | 接收验证码的目标地址（邮箱或手机号） |
| time | int(11) | - | 验证码创建时间（Unix 时间戳） |
| ip | varchar(20) | NULL | 请求验证码的IP地址 |
| status | int(1) | 0 | 使用状态，0=未使用，1=已使用 |
| errcount | int(11) | 0 | 验证错误次数，超过限制后验证码失效 |

**索引：** PRIMARY(id), KEY code(to, type)

> 注：errcount 字段由 update2.sql 增量更新新增，用于防止验证码暴力破解，错误次数达到上限后该验证码自动失效。

---

## 5.7 其他表

### 5.7.1 pre_anounce（公告表）

存储系统公告信息，在前台页面滚动显示。

| 字段名 | 类型 | 默认值 | 说明 |
|--------|------|--------|------|
| id | int(11) unsigned | AUTO_INCREMENT | 公告ID（主键），自增 |
| content | text | NULL | 公告内容 |
| color | varchar(10) | NULL | 公告文字颜色，如 "#FF0000"、"red" 等 |
| sort | int(11) | 1 | 排序权重，数值越小越靠前 |
| addtime | datetime | NULL | 发布时间 |
| status | tinyint(1) | 1 | 状态，0=隐藏，1=显示 |

**索引：** PRIMARY(id)

---

## 5.8 表间关联关系

以下是系统各主要表之间的关联关系，理解这些关联对于把握系统数据流至关重要。

### 5.8.1 核心业务关联

```
pre_order.uid ──────→ pre_user.uid          （订单所属商户）
pre_order.type ─────→ pre_type.id           （订单支付方式）
pre_order.channel ──→ pre_channel.id        （订单使用的支付通道）
pre_order.invite ───→ pre_user.uid          （订单邀请人）
```

### 5.8.2 支付通道关联

```
pre_channel.type ───→ pre_type.id           （通道支持的支付方式）
pre_channel.plugin ─→ pre_plugin.name       （通道使用的支付插件）
pre_channel.appwxmp→ pre_weixin.id          （通道关联的微信公众号）
pre_channel.appwxa ─→ pre_weixin.id         （通道关联的微信小程序）
```

### 5.8.3 用户体系关联

```
pre_user.gid ───────→ pre_group.gid         （商户所属用户组）
pre_user.upid ──────→ pre_user.uid          （商户的上级邀请人，自关联）
```

### 5.8.4 资金关联

```
pre_settle.uid ─────→ pre_user.uid          （结算记录所属商户）
pre_settle.batch ───→ pre_batch.batch       （结算记录所属批次）
pre_record.uid ─────→ pre_user.uid          （资金明细所属商户）
pre_record.trade_no → pre_order.trade_no    （资金明细关联订单）
```

### 5.8.5 域名与风控关联

```
pre_domain.uid ─────→ pre_user.uid          （授权域名所属商户）
pre_risk.uid ───────→ pre_user.uid          （风控记录关联商户）
pre_alipayrisk.channel→ pre_channel.id      （支付宝风控关联通道）
```

### 5.8.6 轮询组关联

```
pre_roll.type ──────→ pre_type.id           （轮询组对应的支付方式）
pre_roll.info中的channel → pre_channel.id   （轮询组包含的通道）
```

### 5.8.7 关联关系总览图

```
┌──────────┐     ┌──────────┐     ┌──────────┐
│ pre_type │←────│pre_channel│←────│ pre_roll │
│ 支付方式  │     │ 支付通道   │     │ 轮询组   │
└──────────┘     └────┬─────┘     └──────────┘
                      │                ↑
                      │                │
                 ┌────┴────┐     ┌────┴────┐
                 │pre_plugin│     │ info中  │
                 │ 支付插件  │     │ 通道ID  │
                 └─────────┘     └─────────┘
                      │
                 ┌────┴────┐
                 │pre_weixin│
                 │公众号/小程序│
                 └─────────┘

┌──────────┐     ┌──────────┐     ┌──────────┐
│ pre_user │←────│pre_order │     │pre_settle│
│  商户    │     │  订单     │     │  结算    │
│          │←────│          │     │          │←─── pre_batch
│          │←────│(invite)  │     │          │     批量转账
│          │     └──────────┘     └──────────┘
│          │←────┌──────────┐     ┌──────────┐
│          │     │pre_record│     │pre_domain│
│          │←────│ 资金明细  │     │ 授权域名  │
└────┬─────┘     └──────────┘     └──────────┘
     │
     ↓
┌──────────┐
│pre_group │
│ 用户组    │
└──────────┘

┌──────────┐     ┌──────────────┐
│ pre_risk │     │pre_alipayrisk│
│ 风控记录  │     │ 支付宝风控    │
└──────────┘     └──────────────┘

┌──────────┐     ┌──────────┐
│pre_log   │     │pre_regcode│
│ 登录日志  │     │ 注册验证码  │
└──────────┘     └──────────┘

┌──────────┐     ┌──────────┐
│pre_config│     │pre_cache │
│ 系统配置  │     │  缓存     │
└──────────┘     └──────────┘

┌──────────┐
│pre_anounce│
│  公告     │
└──────────┘
```

---

## 5.9 数据库设计总结

### 5.9.1 表清单汇总

| 序号 | 表名 | 说明 | 记录类型 |
|------|------|------|----------|
| 1 | pre_config | 系统配置表 | KV键值对 |
| 2 | pre_cache | 缓存表 | KV键值对+过期时间 |
| 3 | pre_type | 支付方式表 | 基础数据 |
| 4 | pre_plugin | 支付插件表 | 基础数据 |
| 5 | pre_channel | 支付通道表 | 业务配置 |
| 6 | pre_roll | 通道轮询组表 | 业务配置 |
| 7 | pre_weixin | 微信公众号/小程序表 | 业务配置 |
| 8 | pre_order | 订单表 | 交易数据 |
| 9 | pre_user | 商户/用户表 | 用户数据 |
| 10 | pre_group | 用户组表 | 业务配置 |
| 11 | pre_domain | 授权域名表 | 业务配置 |
| 12 | pre_settle | 结算记录表 | 资金数据 |
| 13 | pre_record | 资金明细表 | 资金数据 |
| 14 | pre_batch | 批量转账表 | 资金数据 |
| 15 | pre_risk | 风控记录表 | 安全数据 |
| 16 | pre_alipayrisk | 支付宝风控表 | 安全数据 |
| 17 | pre_log | 登录日志表 | 安全数据 |
| 18 | pre_regcode | 注册验证码表 | 安全数据 |
| 19 | pre_anounce | 公告表 | 内容数据 |

### 5.9.2 设计特点

1. **KV 配置模式**：系统配置（pre_config）和缓存（pre_cache）采用键值对存储，灵活可扩展，新增配置项无需修改表结构。

2. **通道-插件分离**：支付通道（pre_channel）与支付插件（pre_plugin）分离设计，一个插件可被多个通道复用，通道配置独立管理。

3. **轮询组机制**：通过 pre_roll 表实现多通道负载均衡，支持顺序轮询和加权随机两种策略，提高支付可用性。

4. **用户组权限体系**：通过 pre_group 表实现差异化的费率和通道权限管理，支持用户组购买和有效期控制。

5. **资金可审计**：pre_record 表记录每笔资金变动的变动前后余额，确保资金流水的完整性和可审计性。

6. **批量结算**：pre_batch 与 pre_settle 通过 batch 字段关联，支持批量审核和批量转账，提高运营效率。

7. **多维度风控**：pre_risk 记录通用风控事件，pre_alipayrisk 专门对接支付宝风控，形成多层次风险防控体系。

8. **实名认证体系**：pre_user 表包含完整的实名认证字段（个人认证和企业认证），支持多种认证方式。
