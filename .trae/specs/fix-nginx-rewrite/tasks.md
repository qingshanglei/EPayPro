# Tasks

- [ ] Task 1: 修改Nginx伪静态规则
  - [ ] SubTask 1.1: 将 `/www/server/panel/vhost/rewrite/115.175.13.168_688.conf` 内容替换为易支付官方规则
  - [ ] SubTask 1.2: 验证Nginx配置语法正确（nginx -t）
  - [ ] SubTask 1.3: 重载Nginx使规则生效（nginx -s reload）

- [ ] Task 2: 验证修复结果
  - [ ] SubTask 2.1: curl测试 `/pay/notify/` 路径返回纯文本（非HTML页面）
  - [ ] SubTask 2.2: curl测试 `/pay/return/` 路径返回纯文本（非HTML页面）
  - [ ] SubTask 2.3: curl测试 `/plugins/` 路径返回403

# Task Dependencies
- Task 2 depends on Task 1
