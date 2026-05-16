<?php

define('CURR_PATH', dirname(__DIR__));
require CURR_PATH . '/../includes/common.php';
require CURR_PATH . '/usdt/usdt_plugin.php';
if (function_exists("set_time_limit")) {
    @set_time_limit(0);
}

    $channels = $DB->getAll('select * from pre_channel where plugin = ? and status = 1', ['usdt']);
    if (!$channels) {
        exit("错误：没有找到任何USDT支付通道\n");
    }

    foreach ($channels as $channel) {
        usdt_plugin::cron($channel);

}

?>
