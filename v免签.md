# vmqPro 对接文档（基于 vmqphp 二次开发）

| 项目信息 | 内容 |
|---------|------|
| 项目名称 | vmqPro（基于 V免签/vPay 二次开发） |
| 原版项目 | https://github.com/szvone/vmqphp |
| 当前版本 | 0.03（2026-05-15） |
| 原版版本 | 1.12（2020-01-30，已停更） |
| 文档版本 | v2.3 |
| 文档日期 | 2026-05-15 |
| 文档受众 | 二次开发工程师、运维工程师、对接商户 |

---

## 目录

- [1. 项目架构概述](#1-项目架构概述)
- [2. 技术栈说明](#2-技术栈说明)
- [3. 核心功能模块解析](#3-核心功能模块解析)
- [4. API接口规范](#4-api接口规范)
- [5. 数据库结构设计](#5-数据库结构设计)
- [6. 环境配置指南](#6-环境配置指南)
- [7. 开发规范与最佳实践](#7-开发规范与最佳实践)
- [8. 现有功能模块的扩展点说明](#8-现有功能模块的扩展点说明)
- [9. 常见问题解决方案](#9-常见问题解决方案)

---

## 1. 项目架构概述

### 1.1 项目定位

V免签（vPay）是一个**免签约支付接入系统**，通过安卓监控App捕获微信/支付宝的收款通知，实现个人收款码接入支付的能力，无需商户签约即可完成支付对接。

**重要提示：**

- 本系统原理为监控收款后手机的通知栏推送消息，请保持微信/支付宝V免签监控端后台正常运行，且添加到内存清理白名单。
- V免签通过监控手机收款通知栏推送信息工作，因此不适合商用多用户场景。
- V免签监控端并不适配所有手机，如遇无法正常使用，请更换手机或使用模拟器挂机。

### 1.2 整体架构图

    ┌──────────────┐     ┌──────────────────────┐     ┌──────────────────┐
    │   商户系统    │────>│    vPay 服务端        │<────│  安卓监控 App    │
    │ (对接方)     │     │  (ThinkPHP 5.1)      │     │  (v.apk)        │
    └──────────────┘     └──────────────────────┘     └──────────────────┘
           │                      │                           │
           │  createOrder         │  appHeart / appPush       │
           │  checkOrder          │                           │ 捕获收款通知
           │  getOrder            │  MySQL (vpay)             │
           │                      │                           │
           │<─ notifyUrl ─────────│                           │
           │<─ returnUrl ─────────│                           │
           │                      │                           │
    ┌──────────────┐     ┌──────────────────────┐     ┌──────────────────┐
    │   用户端     │────>│   支付页面            │     │  微信/支付宝     │
    │ (扫码支付)   │     │  payPage/pay.html    │     │  收款通知        │
    └──────────────┘     └──────────────────────┘     └──────────────────┘

### 1.3 目录结构详解

    vPay/
    ├── application/                  # 应用核心代码（ThinkPHP应用目录）
    │   ├── admin/controller/         # 后台模块控制器
    │   │   └── Index.php             # 后台管理控制器
    │   ├── index/controller/         # 前台模块控制器
    │   │   └── Index.php             # 前台核心API控制器
    │   ├── service/                  # 服务层
    │   │   ├── QrcodeServer.php      # 二维码生成服务
    │   │   ├── HttpService.php       # HTTP请求服务（cURL封装）
    │   │   ├── NotifyService.php     # 回调通知服务（URL构建+签名）
    │   │   ├── SignatureService.php  # 签名验证服务（MD5签名计算与比对）
    │   │   ├── SettingService.php    # 系统设置服务（批量读取+请求级缓存）
    │   │   └── PasswordService.php   # 密码服务（bcrypt哈希+验证+迁移）
    │   ├── command.php               # 命令定义（空）
    │   ├── common.php                # 公共函数（空）
    │   ├── provider.php              # 容器绑定（空）
    │   └── tags.php                  # 行为钩子定义
    ├── config/                       # 应用配置目录
    │   ├── app.php                   # 应用基本配置
    │   ├── database.php              # 数据库连接配置
    │   ├── cache.php                 # 缓存配置
    │   ├── session.php               # 会话配置
    │   ├── log.php                   # 日志配置
    │   ├── middleware.php             # 中间件配置
    │   ├── cookie.php                # Cookie配置
    │   ├── template.php              # 模板引擎配置
    │   ├── console.php               # 控制台配置
    │   └── trace.php                 # 调试追踪配置
    ├── public/                       # Web入口目录（网站根目录）
    │   ├── index.php                 # PHP入口文件
    │   ├── index.html                # 首页（登录页）
    │   ├── aaa.html                  # 后台管理主框架
    │   ├── main.html                 # 后台仪表板
    │   ├── admin/                    # 后台管理页面
    │   │   ├── setting.html          # 系统设置
    │   │   ├── jk.html               # 监控端状态
    │   │   ├── qrcodelist.html       # 二维码管理（合并后，含添加功能）
    │   │   └── orderlist.html        # 订单列表
    │   ├── payPage/                  # 支付页面
    │   │   ├── pay.html              # 扫码支付主页面（Vue.js）
    │   │   ├── go_alipay.html        # 支付宝跳转中间页
    │   │   └── pay.css               # 支付页样式
    │   ├── example/                  # 支付对接示例
    │   │   ├── index.html            # 测试支付页面
    │   │   ├── main.php              # 创建订单示例
    │   │   ├── notify.php            # 异步通知处理示例
    │   │   └── return.php            # 同步回调处理示例
    │   ├── api.html                  # API说明文档页面
    │   ├── layui/                    # Layui前端框架
    │   ├── js/                       # 后台JS
    │   │   ├── global.js             # 全局JS
    │   │   ├── common.js             # 公共工具函数（formatDate等）
    │   │   ├── canvasbg.js           # 登录页背景
    │   │   └── llqrcode.js           # 纯JS二维码解码库
    │   ├── qr-code/                  # 二维码识别处理
    │   │   ├── test.php              # 二维码识别入口
    │   │   └── lib/                  # QR Code解码库
    │   ├── css/                      # 后台CSS
    │   ├── assets/                   # 首页静态资源
    │   ├── image/                    # 图片资源
    │   └── v.apk                     # 监控端安卓App
    ├── route/
    │   └── route.php                 # 路由定义
    ├── thinkphp/                     # ThinkPHP 5.1 框架核心
    ├── vendor/                       # Composer依赖包
    ├── extend/                       # 扩展类库（空）
    ├── runtime/                      # 运行时缓存
    ├── composer.json                 # Composer依赖配置
    ├── think                         # 命令行入口
    └── ver                           # 版本号文件

### 1.4 请求生命周期

1. 用户请求到达 `public/index.php`
2. ThinkPHP加载框架基础文件 `thinkphp/base.php`
3. 容器初始化应用 `Container::get('app')->run()->send()`
4. 路由解析（`route/route.php` 定义了所有API路由）
5. 分发到对应模块/控制器/方法
6. 执行业务逻辑，访问数据库
7. 返回JSON响应

---

## 2. 技术栈说明

### 2.1 后端技术栈

| 技术 | 版本 | 用途 | 备注 |
|------|------|------|------|
| PHP | >= 5.6（我使用7.2版本部署） | 后端语言 | 需开启GD库、cURL扩展 |
| ThinkPHP | 5.1.* | MVC框架 | 多模块模式，默认模块index |
| MySQL | 5.x+ | 数据库 | 数据库名vpay，字符集utf8 |
| endroid/qr-code | ^2.5 | 二维码生成 | 后台二维码图片生成 |
| bacon/bacon-qr-code | - | 二维码编码 | endroid依赖 |
| khanamiryan/qrcode-detector-decoder | ^1.0 | 二维码识别解码 | qr-code/test.php使用 |
| myclabs/php-enum | - | 枚举类型支持 | endroid依赖 |
| symfony/* | - | Symfony组件 | options-resolver, property-access, polyfill-ctype |

### 2.2 前端技术栈

| 技术 | 版本 | 用途 |
|------|------|------|
| Layui | - | 后台管理UI框架 |
| Vue.js | 2.5.21 | 支付页面渲染（pay.html） |
| jQuery | 1.11.3 / 3.3.1 / 3.4.0 | DOM操作与AJAX |
| layer.js | - | 弹层组件 |
| skel.js | - | 首页响应式框架 |
| lib.baomitu.com | CDN | 前端资源加速 |
| fastly.jsdelivr.net | CDN | Vue.js等CDN加载 |

### 2.3 监控端

| 技术 | 说明 |
|------|------|
| Android App (v.apk) | 监控微信/支付宝收款通知，通过appHeart和appPush接口与服务端通讯 |

### 2.4 部署环境

| 技术 | 说明 |
|------|------|
| Nginx / Apache | Web服务器，需配置ThinkPHP伪静态 |
| 宝塔面板 | 推荐部署管理工具 |

---

## 3. 核心功能模块解析

### 3.1 支付订单模块（核心）

**控制器**: `application/index/controller/Index.php`  
**核心方法**: `createOrder()`, `getOrder()`, `checkOrder()`, `closeOrder()`

#### 3.1.1 支付流程时序

    商户系统                vPay服务端              用户端              监控App
       │                      │                      │                  │
       │── createOrder ──────>│                      │                  │
       │                      │── 清理过期订单        │                  │
       │                      │── 验证签名           │                  │
       │                      │── 金额排重微调        │                  │
       │                      │── 生成云端订单号      │                  │
       │                      │── 插入pay_order       │                  │
       │<── orderId,pay_url ──│                      │                  │
       │                      │                      │                  │
       │                      │<─── 扫码访问pay.html ─│                  │
       │                      │──── 二维码+倒计时 ───>│                  │
       │                      │                      │── 扫码支付 ──────>│
       │                      │                      │                  │
       │                      │                      │   微信/支付宝收款  │
       │                      │                      │                  │── 捕获通知
       │                      │<── appPush(type,price)────────────────── │
       │                      │── 匹配订单            │                  │
       │                      │── 更新state=1         │                  │
       │<── notifyUrl(payId...)│── 异步通知           │                  │
       │── "success" ────────>│                      │                  │
       │                      │                      │                  │
       │                      │<─── 轮询checkOrder ──│                  │
       │                      │──── 返回支付成功 ────>│                  │
       │                      │                      │── 跳转returnUrl ─>│

#### 3.1.2 创建订单逻辑详解（createOrder）

1. 调用 `closeEndOrder()` 清理所有过期订单
2. 验证必传参数: `payId`（商户订单号）、`type`（1=微信/2=支付宝）、`price`（金额）、`sign`（签名）
3. 签名验证: `md5(payId + param + type + price + key)` 与传入sign比对
4. 检查监控端状态 `jkstate` 必须为1（在线）
5. **金额微调排重**:
   - 将 `price × 100` 转为整数分
   - 尝试 `INSERT IGNORE INTO tmp_price` 插入价格标识（格式: `分*100-type`）
   - 如冲突（同金额+同支付方式已有待支付订单），根据 `payQf` 设置递增/递减1分
   - 最多重试10次，全部冲突则返回错误
6. 查找固定金额二维码（`pay_qrcode`表），找到则使用固定二维码（`isAuto=0`），否则使用后台设置的无金额二维码（`isAuto=1`）
7. 生成云端订单号: `日期Ymd + 时间Hms + 4位随机数`
8. 插入 `pay_order` 表
9. 根据 `isHtml` 参数决定返回JSON数据或直接跳转到支付页面

#### 3.1.3 监控App推送逻辑详解（appPush）

1. 验证签名: `md5(type + price + t + key)`
2. 更新 `lastpay` 时间
3. 在 `pay_order` 表中查找: `really_price = price AND state = 0 AND type = type`
4. **找到匹配订单**:
   - 更新订单状态为1（完成），记录支付时间
   - 删除 `tmp_price` 中的对应排重记录
   - GET请求发送异步通知到 `notifyUrl`
   - 通知成功（返回包含"success"）则完成，否则标记 `state=2`（通知失败）
5. **未找到匹配订单**: 记录为"无订单转账"日志

#### 3.1.4 订单状态机

    ┌────────────────┐
    │  0 = 待支付    │ ──── 支付成功 ────>  1 = 完成
    │                │ ──── 超时/关闭 ──> -1 = 过期
    └────────────────┘
    1 = 完成 ──── 异步通知失败 ────> 2 = 通知失败

### 3.2 监控端通讯模块

**核心方法**: `appHeart()`, `appPush()`, `getState()`

| 接口 | 方向 | 说明 |
|------|------|------|
| appHeart | App → 服务端 | 心跳上报，每60秒一次，更新lastheart和jkstate |
| appPush | App → 服务端 | 推送收款数据（type+price），触发订单匹配和通知 |
| getState | 商户 → 服务端 | 查询监控端在线状态 |

监控端状态值: `1`=在线, `0`=掉线, `-1`=未绑定

### 3.3 后台管理模块

**控制器**: `application/admin/controller/Index.php`

| 功能 | 方法 | 说明 |
|------|------|------|
| 仪表板 | getMain() | 今日/总订单数、金额、服务器信息 |
| 系统设置 | getSettings() / saveSetting() | 通讯密钥、通知地址、二维码、有效期等 |
| 订单管理 | getOrders() / delOrder() / setBd() / delGqOrder() / delLastOrder() | 订单列表、删除、补单、清理 |
| 二维码管理 | addPayQrcode() / getPayQrcodes() / delPayQrcode() / toggleQrcodeStatus() | 固定金额二维码增删查、启用/禁用状态切换 |
| 二维码生成 | enQrcode() | 生成QR码PNG图片 |
| 菜单配置 | getMenu() | 返回后台菜单项（含图标字段） |

**认证方式**: 所有后台方法通过 `Session::has("admin")` 检查登录状态

### 3.4 二维码服务模块

**服务类**: `application/service/QrcodeServer.php`

- 依赖: `Endroid\QrCode\QrCode`
- 配置: UTF-8编码, 180px默认大小, HIGH容错率
- 输出模式: `display`（直接输出浏览器） / `writefile`（写入文件）
- 调用方式: `new QrcodeServer(['generate'=>"display", 'size'=>200])`

### 3.5 前端支付页面模块

**支付主页面**: `public/payPage/pay.html`

- Vue.js 2.5.21 渲染
- 功能: 显示二维码、倒计时、金额提示
- **指数退避轮询** `checkOrder` 接口检测支付状态（1.5s→3s→5s→10s）
- 支付成功后自动跳转到 `returnUrl`
- 使用 `v-cloak` 防止模板闪烁

**支付宝跳转页**: `public/payPage/go_alipay.html`

- 检测微信/QQ环境并拦截提示
- 通过 `alipays://` 协议唤起支付宝App
- 支持Android Intent方式唤起

---

## 4. API接口规范

### 4.1 通用约定

| 项目 | 说明 |
|------|------|
| 协议 | HTTP/HTTPS |
| 方法 | GET/POST 均支持 |
| 编码 | UTF-8 |
| 响应格式 | JSON（Content-Type: application/json） |
| 签名算法 | MD5 |
| 通讯密钥 | .env文件中的 VPAY_KEY（优先）或后台设置的 key 值 |

### 4.2 商户端接口

#### 4.2.1 创建订单

    接口地址: /createOrder
    请求方式: GET/POST

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| payId | string | 是 | 商户订单号，需唯一 |
| type | int | 是 | 支付方式: 1=微信, 2=支付宝 |
| price | float | 是 | 订单金额（元），如 0.01 |
| sign | string | 是 | 签名（见签名机制说明） |
| param | string | 否 | 自定义透传参数，原样回调 |
| isHtml | int | 否 | 1=直接跳转支付页面, 0=返回JSON（默认0） |
| notifyUrl | string | 否 | 异步通知地址，不传则用后台默认 |
| returnUrl | string | 否 | 同步跳转地址，不传则用后台默认 |

**签名计算示例**（PHP）:

    $sign = md5($payId . $param . $type . $price . $key);

**成功响应（isHtml=0）**:

    {
        "code": 1,
        "msg": "下单成功！",
        "orderId": "202605101430528765",
        "payId": "M20260510001",
        "price": 0.01,
        "reallyPrice": 0.01,
        "type": 1,
        "payUrl": "wxp://xxxx",
        "isAuto": 1,
        "state": 0
    }

**失败响应**:

    {
        "code": -1,
        "msg": "签名错误"
    }

#### 4.2.2 查询订单

    接口地址: /getOrder
    请求方式: GET/POST

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| orderId | string | 是 | 云端订单号（createOrder返回的orderId） |

**成功响应**:

    {
        "code": 1,
        "msg": "查询成功！",
        "data": {
            "id": 1,
            "pay_id": "M20260510001",
            "order_id": "202605101430528765",
            "type": 1,
            "price": 0.01,
            "really_price": 0.01,
            "pay_url": "wxp://xxxx",
            "is_auto": 1,
            "state": 0,
            "param": "",
            "notify_url": "http://xxx/notify.php",
            "return_url": "http://xxx/return.php",
            "create_date": 1715312252,
            "pay_date": null,
            "close_date": null
        }
    }

#### 4.2.3 检查订单状态

    接口地址: /checkOrder
    请求方式: GET/POST

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| orderId | string | 是 | 云端订单号 |

**支付成功响应**:

    {
        "code": 1,
        "msg": "支付成功",
        "url": "http://returnUrl?payId=xxx&param=xxx&type=1&price=0.01&reallyPrice=0.01&sign=xxx"
    }

**未支付响应**:

    {
        "code": -1,
        "msg": "订单未支付"
    }

#### 4.2.4 关闭订单

    接口地址: /closeOrder
    请求方式: GET/POST

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| orderId | string | 是 | 云端订单号 |
| sign | string | 是 | 签名: md5(orderId + key) |

### 4.3 回调通知接口

#### 4.3.1 异步通知（服务端 → 商户）

当订单支付成功后，vPay服务端会以GET请求通知商户:

    通知地址: notifyUrl（创建订单时传入或后台默认）
    请求方式: GET

| 参数 | 类型 | 说明 |
|------|------|------|
| payId | string | 商户订单号 |
| param | string | 透传参数 |
| type | int | 支付方式: 1=微信, 2=支付宝 |
| price | float | 订单原始金额 |
| reallyPrice | float | 实际支付金额（可能因排重微调有差异） |
| sign | string | 回调签名: md5(payId + param + type + price + reallyPrice + key) |

**商户处理**:
1. 验证签名是否正确
2. 验证payId是否为自身订单
3. 验证reallyPrice是否在允许范围内
4. 处理业务逻辑（发货等）
5. **返回字符串 "success"**（必须精确匹配，表示接收成功）
6. 若未返回"success"，服务端将标记订单状态为2（通知失败）

#### 4.3.2 同步回调（用户端跳转）

用户支付成功后，前端跳转地址:

    跳转地址: returnUrl（创建订单时传入或后台默认）
    请求方式: GET（URL参数同异步通知）

**签名验证示例**（PHP）:

    $sign = md5($payId . $param . $type . $price . $reallyPrice . $key);
    if ($sign === $_GET['sign']) {
        // 签名验证通过
    }

**重要**: 同步回调仅用于用户端页面跳转展示，**不能**作为支付成功的最终依据，必须以异步通知为准。

### 4.4 监控端接口

#### 4.4.1 心跳上报

    接口地址: /appHeart
    请求方式: GET/POST

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| t | int | 是 | 当前时间戳 |
| sign | string | 是 | 签名: md5(t + key) |

#### 4.4.2 推送收款数据

    接口地址: /appPush
    请求方式: GET/POST

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| type | int | 是 | 支付方式: 1=微信, 2=支付宝 |
| price | float | 是 | 实际收款金额 |
| t | int | 是 | 当前时间戳 |
| sign | string | 是 | 签名: md5(type + price + t + key) |

#### 4.4.3 查询监控端状态

    接口地址: /getState
    请求方式: GET/POST

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| t | int | 是 | 当前时间戳 |
| sign | string | 是 | 签名: md5(t + key) |

**响应**:

    {
        "code": 1,
        "msg": "监控端在线",
        "data": {
            "jkstate": 1,
            "lastheart": 1715312252,
            "lastpay": 1715312000
        }
    }

### 4.5 后台管理接口

所有后台接口需先通过 `/login` 接口登录获取Session。

#### 4.5.1 登录

    接口地址: /login
    请求方式: POST

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| user | string | 是 | 管理员账号 |
| pass | string | 是 | 管理员密码 |

#### 4.5.2 其他后台接口概览

| 接口 | 方法 | 说明 |
|------|------|------|
| /getMenu | GET | 获取后台菜单 |
| /admin/index/getMain | GET | 仪表板数据 |
| /admin/index/getSettings | GET | 获取系统设置 |
| /admin/index/saveSetting | POST | 保存系统设置 |
| /admin/index/getOrders | GET | 订单列表（分页） |
| /admin/index/delOrder | POST | 删除订单 |
| /admin/index/setBd | POST | 补单 |
| /admin/index/delGqOrder | POST | 删除过期订单 |
| /admin/index/delLastOrder | POST | 删除7天前订单 |
| /admin/index/addPayQrcode | POST | 添加固定金额二维码 |
| /admin/index/getPayQrcodes | GET | 二维码列表（分页） |
| /admin/index/delPayQrcode | POST | 删除二维码 |
| /admin/index/toggleQrcodeStatus | POST | 切换二维码启用/禁用状态 |
| /enQrcode?url=xxx | GET | 生成二维码图片 |

---

## 5. 数据库结构设计

### 5.1 数据库基本信息

| 项目 | 值 |
|------|-----|
| 数据库名 | vpay（SQL文件中原始库名为vmq，导入时需选择vpay库） |
| 字符集 | utf8 |
| 表前缀 | 无（空字符串） |
| 表数量 | 4张核心表 |

### 5.2 表结构详解

#### 5.2.1 setting — 系统设置表

键值对结构存储系统配置，灵活扩展。

| 字段 | 类型 | 说明 | 示例 |
|------|------|------|------|
| id | int | 主键，自增 | 1 |
| vkey | varchar | 配置键名（唯一） | user |
| vvalue | varchar/text | 配置值 | admin |

**已知配置键清单**:

| vkey | 说明 | 默认/示例值 | 是否必配 |
|------|------|-------------|---------|
| user | 后台管理员账号 | - | 是 |
| pass | 后台管理员密码（bcrypt哈希存储，不再支持明文） | - | 是 |
| key | 通讯密钥 | md5随机字符串 | 是 |
| notifyUrl | 默认异步通知地址 | http://xxx/notify.php | 是 |
| returnUrl | 默认同步回调地址 | http://xxx/return.php | 是 |
| close | 订单有效期（分钟） | 5 | 是 |
| payQf | 价格区分方式 | 1=递增, 2=递减 | 是 |
| wxpay | 微信收款二维码内容 | wxp://... | 是 |
| zfbpay | 支付宝收款二维码内容 | HTTPS://QR.ALIPAY.COM/... | 是 |
| lastheart | 监控端最后心跳时间戳 | - | 系统维护 |
| lastpay | 最后收款时间戳 | - | 系统维护 |
| jkstate | 监控端状态 | 1=在线, 0=掉线, -1=未绑定 | 系统维护 |

#### 5.2.2 pay_order — 支付订单表

| 字段 | 类型 | 说明 | 索引建议 |
|------|------|------|---------|
| id | int | 主键，自增 | PRIMARY |
| pay_id | varchar | 商户订单号 | INDEX |
| order_id | varchar | 云端订单号 | UNIQUE |
| type | tinyint | 支付方式: 1=微信, 2=支付宝 | INDEX |
| price | decimal | 订单金额（元） | - |
| really_price | decimal | 实际需付金额（排重微调后） | INDEX |
| pay_url | varchar | 支付二维码内容 | - |
| is_auto | tinyint | 1=需手动输入金额, 0=扫码自动输入 | - |
| state | tinyint | 订单状态: -1=过期, 0=待支付, 1=完成, 2=通知失败 | INDEX |
| param | varchar | 自定义透传参数 | - |
| notify_url | varchar | 异步通知地址 | - |
| return_url | varchar | 同步跳转地址 | - |
| create_date | int | 创建时间戳 | INDEX |
| pay_date | int | 支付时间戳 | - |
| close_date | int | 关闭时间戳 | - |

**订单状态流转**:

    -1(过期) <── 0(待支付) ──> 1(完成) ──> 2(通知失败)

#### 5.2.3 pay_qrcode — 固定金额二维码表

| 字段 | 类型 | 说明 |
|------|------|------|
| id | int | 主键，自增 |
| type | tinyint | 支付方式: 1=微信, 2=支付宝 |
| pay_url | varchar | 二维码内容 |
| price | decimal | 固定金额（元） |
| status | int | 状态: 1=启用, 0=禁用（默认1） |
| remark | varchar | 备注信息（如"微信1号"、"支付宝主号"） |

用于存储预设好金额的收款二维码，避免金额微调排重。支持启用/禁用状态控制，禁用的二维码在创建订单时自动排除。

#### 5.2.4 tmp_price — 临时价格排重表

| 字段 | 类型 | 说明 |
|------|------|------|
| price | varchar | 价格标识（UNIQUE主键），格式: `分×100-类型`，如 `1001-1` |
| oid | varchar | 关联的云端订单号 |

**排重原理**: 使用 `INSERT IGNORE INTO` 写入，若同一 `price` 值已存在则忽略（说明已有同金额+同支付方式的待支付订单），从而触发金额微调逻辑。

### 5.3 建表SQL参考

    CREATE TABLE `setting` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `vkey` varchar(255) NOT NULL,
        `vvalue` text,
        PRIMARY KEY (`id`),
        UNIQUE KEY `vkey` (`vkey`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

    CREATE TABLE `pay_order` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `pay_id` varchar(255) NOT NULL,
        `order_id` varchar(255) NOT NULL,
        `type` tinyint(1) NOT NULL,
        `price` decimal(10,2) NOT NULL,
        `really_price` decimal(10,2) NOT NULL,
        `pay_url` varchar(500) DEFAULT NULL,
        `is_auto` tinyint(1) DEFAULT '0',
        `state` tinyint(1) DEFAULT '0',
        `param` varchar(255) DEFAULT NULL,
        `notify_url` varchar(500) DEFAULT NULL,
        `return_url` varchar(500) DEFAULT NULL,
        `create_date` int(11) DEFAULT NULL,
        `pay_date` int(11) DEFAULT NULL,
        `close_date` int(11) DEFAULT NULL,
        PRIMARY KEY (`id`),
        UNIQUE KEY `order_id` (`order_id`),
        KEY `idx_state` (`state`),
        KEY `idx_type_price` (`type`, `really_price`),
        KEY `idx_create_date` (`create_date`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

    CREATE TABLE `pay_qrcode` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `type` tinyint(1) NOT NULL,
        `pay_url` varchar(500) NOT NULL,
        `price` decimal(10,2) NOT NULL,
        `status` int(11) NOT NULL DEFAULT '1',
        `remark` varchar(255) DEFAULT NULL,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

    CREATE TABLE `tmp_price` (
        `price` varchar(255) NOT NULL,
        `oid` varchar(255) DEFAULT NULL,
        PRIMARY KEY (`price`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

---

## 6. 环境配置指南

### 6.1 系统要求

| 组件 | 最低版本 | 推荐版本 | 博主实测可用配置 |
|------|---------|---------|----------------|
| PHP | 5.6 | 7.4+ | **PHP 7.2.33** |
| MySQL | 5.5 | 5.7+ | **5.7.44** |
| Nginx / Apache | - | Nginx 1.18+ | **Nginx 1.22.1** |
| PHP 扩展 | GD, cURL, PDO, mbstring | 同左 | GD, cURL, PDO, mbstring |

### 6.2 宝塔面板部署步骤

#### 第一步: 创建网站

1. 宝塔面板 → 网站 → 添加站点
2. 域名: 填写支付域名
3. 根目录: `/www/wwwroot/vPay`
4. PHP版本: 选择PHP 7.2.33
5. 数据库: 创建MySQL数据库 `vpay`

#### 第二步: 配置运行目录

1. 网站 → 设置 → 网站目录
2. **运行目录**: 设置为 `/public`（关键！）
3. **关闭防跨站攻击**（open_basedir限制需调整）

#### 第三步: 配置默认文档

1. 网站 → 设置 → 默认文档
2. 将 `index.html` 排在第一位（必须在 index.php 之前）
3. 完整顺序: `index.html, index.php, index.htm, default.php, default.htm`

#### 第四步: 配置伪静态

1. 网站 → 设置 → 伪静态
2. 选择 `thinkphp` 模板，或手动添加:

    location / {
        if (!-e $request_filename) {
            rewrite ^(.*)$ /index.php?s=/$1 last;
        }
    }

#### 第五步: 导入数据库

1. 宝塔面板 → 数据库 → 找到对应MySQL数据库
2. 点击 **备份** → **导入** 按钮
3. 导入项目根目录下的 `vmq.sql` 文件（`/www/wwwroot/vPay/vmq.sql`）
4. 该SQL文件会自动创建4张数据表（pay_order、pay_qrcode、setting、tmp_price）并插入setting初始配置数据
5. 初始管理员账号: `admin`，初始密码: `admin`，**登录后请立即修改**

#### 第六步: 配置项目

1. 复制 `.env.example` 为 `.env`，修改数据库连接信息和通讯密钥:

        DB_HOST=127.0.0.1
        DB_NAME=vpay
        DB_USER=你的数据库用户名
        DB_PASS=你的数据库密码
        DB_PORT=3306
        VPAY_KEY=你的通讯密钥（至少32位随机字符串）

2. `.env` 文件使用 `;` 作为注释符（INI格式），**不要使用 `#`**
3. ThinkPHP 5.1 会自动加载项目根目录的 `.env` 文件
4. 通讯密钥优先从 `.env` 的 `VPAY_KEY` 读取，若未配置则回退到数据库 `setting` 表的 `key` 值
5. 访问 `http://域名/index.html` 进入后台
6. 在系统设置中配置: 通知地址、收款二维码等

#### 第七步: 安装监控App

1. 下载 `public/v.apk` 安装到安卓手机
2. 在App中配置: 服务器地址、通讯密钥
3. 开启微信/支付宝收款通知监控

### 6.3 Nginx完整配置参考

    server {
        listen 80;
        server_name pay.example.com;
        root /www/wwwroot/vPay/public;
        index index.html index.php;

        location / {
            if (!-e $request_filename) {
                rewrite ^(.*)$ /index.php?s=/$1 last;
            }
        }

        location ~ \.php$ {
            fastcgi_pass unix:/tmp/php-cgi-74.sock;
            fastcgi_index index.php;
            fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
            include fastcgi_params;
        }

        location ~ .*\.(gif|jpg|jpeg|png|bmp|swf|js|css)$ {
            expires 30d;
        }
    }

### 6.4 Apache伪静态配置

项目已内置 `public/.htaccess`:

    RewriteEngine On
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^(.*)$ index.php?s=$1 [QSA,PT,L]

### 6.5 PHP扩展检查

    php -m | grep -E "gd|curl|pdo|mbstring"

确保以上扩展均已安装并启用。

---

## 7. 开发规范与最佳实践

### 7.1 代码结构规范

| 规范 | 说明 |
|------|------|
| 模块划分 | 前台API在 `application/index/controller/`，后台管理在 `application/admin/controller/` |
| 服务层 | 可复用逻辑应抽取到 `application/service/` 目录 |
| 命名空间 | 遵循PSR-4，`app\index\controller\Index` 对应 `application/index/controller/Index.php` |
| 配置管理 | 所有配置项集中在 `config/` 目录，禁止硬编码 |
| 路由定义 | 新增接口必须在 `route/route.php` 中注册 |

### 7.2 安全规范

| 规范 | 说明 | 当前状态 |
|------|------|---------|
| 密码存储 | 使用bcrypt/password_hash，登录时自动迁移旧明文密码 | ✅ 已加固 |
| 签名验证 | MD5签名验证，防重放时间戳校验 | ✅ 已加固 |
| SQL注入 | 使用ThinkPHP查询构造器参数化查询 | ✅ 已加固 |
| SSL验证 | CURLOPT_SSL_VERIFYPEER=true，CA证书校验 | ✅ 已加固 |
| 环境变量 | 敏感配置(.env)隔离，通讯密钥/数据库密码不再硬编码 | ✅ 已加固 |
| 调试模式 | 生产环境关闭app_debug和database.debug | ✅ 已关闭 |
| XSS防护 | 输出转义，前端避免v-html | 需加强 |
| CSRF防护 | 后台表单应加CSRF Token | 未实现 |
| HTTPS | 生产环境必须启用HTTPS | 需配置 |

### 7.3 签名机制规范

系统使用MD5签名验证：

- **MD5签名**：sign为32位字符串，将所有参与签名的参数按文档指定顺序拼接，末尾追加通讯密钥 `key`，对拼接字符串计算MD5

#### MD5签名规则

1. 将所有参与签名的参数按**文档指定顺序**拼接
2. 末尾追加通讯密钥 `key`
3. 对拼接字符串计算MD5

**不同接口的签名参数顺序**（不可调换）:

| 接口 | MD5签名 |
|------|---------|
| createOrder | md5(payId + param + type + price + key) |
| 异步/同步回调 | md5(payId + param + type + price + reallyPrice + key) |
| closeOrder | md5(orderId + key) |
| appHeart / getState | md5(t + key) |
| appPush | md5(type + price + t + key) |

### 7.4 数据库操作规范

- 使用 `Db::name('表名')` 方式操作（不带前缀）
- 写操作使用 `Db::name()->insert()` / `Db::name()->update()`
- 查询使用 `Db::name()->where()->find()` / `Db::name()->where()->select()`
- 分页使用 `Db::name()->paginate()`
- 事务使用 `Db::transaction(function(){})`

### 7.5 前端开发规范

| 规范 | 说明 |
|------|------|
| 后台页面 | 使用Layui框架，参考 `public/admin/` 目录下已有页面 |
| 支付页面 | 使用Vue.js，参考 `public/payPage/pay.html` |
| 静态资源 | CSS放 `public/css/`，JS放 `public/js/`，图片放 `public/image/` |
| CDN引用 | 优先使用 `lib.baomitu.com` |
| API请求 | 使用jQuery $.ajax/$.post，后台使用Layui table模块内置请求 |

### 7.6 日志规范

- 框架日志: `runtime/log/` 目录，按日期分文件
- 业务日志: 建议在关键操作处增加 `Log::write()` 记录
- 异常日志: 使用 `try-catch` 捕获并记录

---

## 8. 现有功能模块的扩展点说明

### 8.1 支付方式扩展

**当前**: 支持微信（type=1）和支付宝（type=2）  
**扩展方式**:

1. 在 `pay_order` 和 `pay_qrcode` 表的 `type` 字段增加新值（如3=云闪付）
2. 在 `application/index/controller/Index.php` 的 `createOrder()` 方法中增加新支付方式的二维码获取逻辑
3. 在 `public/payPage/` 下新增对应支付方式的跳转页面
4. 监控App需同步支持新支付方式的通知捕获

### 8.2 通知机制扩展

**当前**: 仅支持GET请求异步通知  
**扩展方式**:

1. 在 `appPush()` 方法中，修改通知逻辑支持POST请求
2. 可在 `setting` 表增加 `notifyMethod` 配置项
3. 可扩展支持多种通知方式: Webhook、WebSocket、MQ等

### 8.3 订单有效期扩展

**当前**: 全局统一有效期（setting表的close值）  
**扩展方式**:

1. `createOrder` 接口增加可选参数 `close`（分钟）
2. `pay_order` 表增加 `close_date` 字段记录过期时间（已有但未用于单独控制）
3. `closeEndOrder()` 方法中按各订单自身的过期时间判断

### 8.4 固定金额二维码扩展

**当前**: 支持添加微信/支付宝的固定金额二维码  
**扩展方向**:

1. 支持二维码自动识别和导入（已有 `qr-code/test.php` 雏形）
2. 支持二维码健康检测（定时验证二维码是否仍有效）
3. 支持二维码轮询策略（多个同金额二维码负载均衡）

### 8.5 后台管理扩展

**扩展点**:

| 扩展点 | 文件位置 | 说明 |
|--------|---------|------|
| 新增菜单 | `public/aaa.html` 菜单定义 | 修改左侧菜单配置 |
| 新增页面 | `public/admin/` 目录 | 新增HTML页面，Layui框架 |
| 新增接口 | `application/admin/controller/Index.php` | 新增方法，加Session验证 |
| 权限控制 | 当前仅单管理员 | 可扩展为RBAC权限体系 |

### 8.6 行为钩子扩展

`application/tags.php` 定义了ThinkPHP标准钩子位（当前为空实现）:

| 钩子 | 触发时机 | 用途示例 |
|------|---------|---------|
| app_init | 应用初始化前 | 全局配置检查 |
| app_begin | 请求开始 | 限流、IP黑名单 |
| module_init | 模块初始化 | 模块级配置 |
| action_begin | 操作执行前 | 权限校验、参数预处理 |
| view_filter | 视图输出前 | 响应头设置、内容替换 |
| log_write | 日志写入后 | 日志外发 |
| app_end | 请求结束 | 请求耗时统计 |

### 8.7 服务层扩展

`application/service/` 目录用于存放可复用的业务服务:

- `QrcodeServer.php` — 二维码生成服务
- `HttpService.php` — HTTP请求服务（cURL封装，统一超时15s、启用SSL验证）
- `NotifyService.php` — 回调通知服务（URL构建+签名）
- `SignatureService.php` — 签名验证服务（MD5签名计算与比对）
- `SettingService.php` — 系统设置服务（批量读取+请求级缓存）
- `PasswordService.php` — 密码服务（bcrypt哈希+验证+旧明文自动迁移）

按PSR-4规范，命名空间为 `app\service`

### 8.8 中间件扩展

`config/middleware.php` 已定义命名空间但未使用，可添加:

- 签名验证中间件（替代控制器内的重复验证代码）
- 限流中间件
- CORS中间件
- 请求日志中间件

### 8.9 前端支付页面扩展

`public/payPage/pay.html` 可扩展:

- 支付方式切换UI
- 订单超时倒计时样式定制
- 支付成功后的自定义展示
- 多语言支持

---

## 9. 常见问题解决方案

### 9.1 部署问题

#### Q: 访问页面显示空白或目录列表

**A**: 运行目录未设置为 `public`。在宝塔面板 → 网站设置 → 网站目录，将运行目录改为 `/public`。

#### Q: 访问后台跳转到登录页而非管理页面

**A**: 默认文档顺序问题。确保 `index.html` 排在 `index.php` 之前。宝塔面板 → 网站设置 → 默认文档，调整顺序。

#### Q: 路由全部404

**A**: 伪静态未配置。Nginx选择thinkphp伪静态模板，Apache确保 `.htaccess` 生效且开启了 `mod_rewrite`。

#### Q: 数据库连接失败

**A**: 检查 `config/database.php` 中的连接参数，确认数据库已创建且用户有权限。宝塔面板中检查数据库用户授权。

#### Q: 二维码生成失败

**A**: 检查PHP GD库是否安装: `php -m | grep gd`。如未安装，宝塔面板 → 软件商店 → PHP → 安装扩展 → gd。

### 9.2 支付问题

#### Q: 创建订单返回"监控端不在线"

**A**: 
1. 确认监控App是否正常运行
2. 检查App中服务器地址和密钥配置是否正确
3. 查看 `setting` 表的 `jkstate` 值，1=在线
4. 检查手机网络连通性，App需能访问服务器地址

#### Q: 支付后订单状态不变

**A**: 
1. 确认监控App是否捕获到收款通知（App日志）
2. 检查 `appPush` 接口是否正常接收（服务端日志 `runtime/log/`）
3. 确认推送的 `price` 与订单 `really_price` 精确匹配（含小数位）
4. 检查 `tmp_price` 表是否存在脏数据导致匹配失败

#### Q: 金额微调后差异较大

**A**: 
1. `payQf` 设置为1（递增），最多微调10分（0.10元）
2. 若同金额订单过多，建议配置固定金额二维码
3. 检查是否有大量过期订单未清理（`tmp_price` 未释放）

#### Q: 异步通知未收到

**A**: 
1. 检查 `notifyUrl` 是否可从服务器访问（防火墙、网络）
2. 确认商户接口返回了精确的 "success" 字符串
3. 查看订单 `state` 是否为2（通知失败），可使用补单功能重新通知
4. 检查服务端 `getCurl()` 请求是否正常（cURL扩展、SSL证书）

### 9.3 监控端问题

#### Q: 监控App频繁掉线

**A**: 
1. 检查手机网络稳定性
2. 确认App后台运行权限（安卓省电策略可能杀后台）
3. 将App加入电池优化白名单
4. 心跳间隔默认60秒，超时则标记掉线

#### Q: 监控App未捕获收款通知

**A**: 
1. 确认App通知监听权限已开启
2. 安卓系统 → 设置 → 通知权限 → 授予App读取通知权限
3. 确认微信/支付宝收款通知是否正常弹出
4. 部分国产ROM需额外设置自启动权限

### 9.4 开发问题

#### Q: 新增接口后访问404

**A**: 
1. 路由必须在 `route/route.php` 中注册
2. 清除路由缓存: 删除 `runtime/` 目录下缓存文件
3. 确认控制器命名空间正确

#### Q: 修改代码后不生效

**A**: 
1. 删除 `runtime/cache/` 和 `runtime/temp/` 缓存
2. 若开启opcache，重启PHP-FPM: `service php-fpm restart`
3. 开发阶段建议开启 `app_debug`（生产环境必须关闭）

#### Q: Session丢失/登录失效

**A**: 
1. 检查 `config/session.php` 配置
2. 确认 `runtime/session/` 目录可写
3. 跨域场景下检查Cookie域名配置

#### Q: 临时价格表(tmp_price)数据堆积

**A**: 
1. `closeEndOrder()` 方法会清理过期订单对应的tmp_price记录
2. 若异常中断导致残留，可手动清理: `DELETE FROM tmp_price WHERE oid NOT IN (SELECT order_id FROM pay_order WHERE state=0)`
3. 建议增加定时任务定期清理

### 9.5 安全问题

#### Q: 通讯密钥泄露如何更换

**A**:
1. 后台 → 系统设置 → 重新生成key
2. 同步更新监控App中的密钥配置
3. 通知所有对接商户更新签名中的key值
4. 更换后旧签名将全部失效

#### Q: 如何限制接口访问频率

**A**: 当前项目未内置限流机制，建议:
1. Nginx层配置 `limit_req_module`
2. 或开发ThinkPHP中间件实现限流
3. 关键接口: createOrder（防刷单）、appPush（防伪造推送）

---

## 附录A: 对接示例代码（PHP）

### 创建订单

    <?php
    // main.php - 创建订单示例
    $serverUrl = 'http://pay.example.com';
    $key = 'your_comm_key';

    $payId = 'M' . date('YmdHis') . rand(1000, 9999); // 商户订单号
    $type = 1;      // 1=微信, 2=支付宝
    $price = 0.01;  // 金额（元）
    $param = '';    // 透传参数
    $notifyUrl = 'http://your-site.com/notify.php';
    $returnUrl = 'http://your-site.com/return.php';

    // MD5签名
    $sign = md5($payId . $param . $type . $price . $key);

    // 发起请求
    $url = $serverUrl . '/createOrder?' . http_build_query([
        'payId'      => $payId,
        'type'       => $type,
        'price'      => $price,
        'sign'       => $sign,
        'param'      => $param,
        'notifyUrl'  => $notifyUrl,
        'returnUrl'  => $returnUrl,
        'isHtml'     => 0
    ]);

    $result = json_decode(file_get_contents($url), true);
    if ($result['code'] == 1) {
        echo '下单成功，云端订单号: ' . $result['orderId'];
        echo '实际支付金额: ' . $result['reallyPrice'];
        // 将orderId存储，用于后续查询
    } else {
        echo '下单失败: ' . $result['msg'];
    }
    ?>

### 异步通知处理

    <?php
    // notify.php - 异步通知示例
    $key = 'your_comm_key';

    $payId = $_GET['payId'];
    $param = $_GET['param'];
    $type = $_GET['type'];
    $price = $_GET['price'];
    $reallyPrice = $_GET['reallyPrice'];
    $sign = $_GET['sign'];

    // 验证签名
    $expectedSign = md5($payId . $param . $type . $price . $reallyPrice . $key);
    if ($expectedSign !== $sign) {
        exit('sign error');
    }

    // 验证订单（查数据库确认payId是自己的订单且未处理）
    // ... 业务逻辑 ...

    // 处理成功后必须返回 "success"
    echo 'success';
    ?>

### 同步回调处理

    <?php
    // return.php - 同步回调示例
    $key = 'your_comm_key';

    $payId = $_GET['payId'];
    $param = $_GET['param'];
    $type = $_GET['type'];
    $price = $_GET['price'];
    $reallyPrice = $_GET['reallyPrice'];
    $sign = $_GET['sign'];

    // 验证签名
    $expectedSign = md5($payId . $param . $type . $price . $reallyPrice . $key);
    if ($expectedSign === $sign) {
        echo '支付成功！订单号: ' . $payId;
    } else {
        echo '签名验证失败';
    }
    ?>

---

## 附录B: 配置文件速查

| 文件路径 | 用途 |
|---------|------|
| `.env` | 环境变量（数据库、通讯密钥、版本号）**禁止提交到版本控制** |
| `.env.example` | 环境变量模板，可提交到版本控制 |
| `config/app.php` | 应用调试、时区、语言、URL模式 |
| `config/database.php` | 数据库连接信息 |
| `config/cache.php` | 缓存驱动配置 |
| `config/session.php` | Session配置 |
| `config/log.php` | 日志记录配置 |
| `config/middleware.php` | 中间件命名空间 |
| `config/template.php` | 模板引擎配置 |
| `route/route.php` | 路由定义 |
| `application/tags.php` | 行为钩子 |
| `composer.json` | Composer依赖 |
| `ver` | 版本号 |

---

## 附录C: 关键文件索引

| 文件 | 路径 | 说明 |
|------|------|------|
| 前台控制器 | `application/index/controller/Index.php` | 核心API逻辑 |
| 后台控制器 | `application/admin/controller/Index.php` | 后台管理逻辑 |
| 密码服务 | `application/service/PasswordService.php` | bcrypt加密/验证/迁移 |
| 签名服务 | `application/service/SignatureService.php` | MD5签名计算与比对 |
| 通知服务 | `application/service/NotifyService.php` | 回调URL构建+签名 |
| HTTP服务 | `application/service/HttpService.php` | cURL封装（统一超时/SSL） |
| 设置服务 | `application/service/SettingService.php` | 批量读取+请求级缓存 |
| 二维码服务 | `application/service/QrcodeServer.php` | QR码生成服务 |
| 路由定义 | `route/route.php` | 所有API路由 |
| Web入口 | `public/index.php` | PHP入口文件 |
| 支付页面 | `public/payPage/pay.html` | 扫码支付前端（Vue.js） |
| 登录页 | `public/index.html` | 后台登录页 |
| 后台框架 | `public/aaa.html` | 后台管理框架页 |
| 仪表板 | `public/main.html` | 后台仪表板 |
| 二维码管理 | `public/admin/qrcodelist.html` | 二维码管理页面（含添加模态框） |
| API文档 | `public/api.html` | 内置API说明页 |
| 对接示例 | `public/example/` | 商户对接示例代码 |
| 公共JS | `public/js/common.js` | 公共工具函数（formatDate等） |
| 监控App | `public/v.apk` | 安卓监控端安装包 |
