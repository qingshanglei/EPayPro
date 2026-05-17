* [ ] 插件目录已重命名为 vmqPro，文件已重命名为 vmqPro\_plugin.php

* [ ] 插件 $info 配置中 name 为 vmqPro，showname 为 vmqPro，author 为 青衫

* [ ] 插件类名已改为 vmqPro\_plugin

* [ ] submit() 方法中 price 参数使用 sprintf("%.2f", ...) 格式化

* [ ] submit() 方法签名算法使用格式化后的 price 值

* [ ] notify() 方法在返回前清理输出缓冲区（ob\_clean）

* [ ] PHP语法检查通过（php -l 无错误）

* [ ] 原有注释保留完整

