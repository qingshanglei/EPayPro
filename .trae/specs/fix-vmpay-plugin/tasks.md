# Tasks

- [ ] Task 1: 重命名插件目录和文件（vmpPro → vmqPro）
  - [ ] SubTask 1.1: 创建 `/www/wwwroot/pay/plugins/vmqPro/` 目录
  - [ ] SubTask 1.2: 创建 `vmqPro_plugin.php` 文件（基于vmpPro_plugin.php修改）
  - [ ] SubTask 1.3: 删除 `/www/wwwroot/pay/plugins/vmpPro/` 目录

- [ ] Task 2: 修改插件代码（vmqPro_plugin.php）
  - [ ] SubTask 2.1: 修改 `$info` 配置：name/showname 改为 `vmqPro`，author 改为 `青衫`
  - [ ] SubTask 2.2: 修改类名从 `vmpPro_plugin` 改为 `vmqPro_plugin`
  - [ ] SubTask 2.3: 修复 `submit()` 方法：price参数使用 `sprintf("%.2f", $order['realmoney'])` 格式化
  - [ ] SubTask 2.4: 修复 `notify()` 方法：在返回前添加 `ob_clean()` 清理输出缓冲区

- [ ] Task 3: 验证修复结果
  - [ ] SubTask 3.1: PHP语法检查通过
  - [ ] SubTask 3.2: 签名算法与v免签服务端一致
  - [ ] SubTask 3.3: 插件信息配置正确

# Task Dependencies
- Task 2 depends on Task 1
- Task 3 depends on Task 2
