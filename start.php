<?php
/**
 * @script   start.php
 * @brief    
 * @author   blogdaren<blogdaren@163.com>
 * @version  1.0.0
 * @modify   2018-10-28
 */

use Workerman\Worker;

//标记为全局启动
define('GLOBAL_START', 1);

//autoload
require_once __DIR__ . '/vendor/autoload.php';

//一次性加载 /path/to/Applications/*/start_* 多个服务
foreach(glob(__DIR__.'/Applications/*/start_{web,pusher}.php', GLOB_BRACE) as $start_file)
{
    require_once $start_file;
}

//运行所有worker实例
Worker::runAll();
