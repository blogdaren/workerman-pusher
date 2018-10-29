<?php 
/**
 * @script   start_web.php
 * @brief    
 * @author   blogdaren<blogdaren@163.com>
 * @version  1.0.0
 * @modify   2018-10-28
 */

use \Workerman\Worker;
use \Workerman\WebServer;

//autoload
require_once dirname(dirname(__DIR__)) . '/vendor/autoload.php';

//config: 后续考虑封装起来
$config = include(__DIR__ . "/Config/Main.php");
$socket = $config['socket']['listen']['web'];
$domain = $config['domain'];
//config: 后续考虑封装起来

//WebServer
$web = new WebServer($socket);

//WebServer进程数量
$web->count = 4;

//设置站点根目录
$web->addRoot($domain, __DIR__ . '/Webroot');

//如果不是在根目录启动，则运行runAll方法
!defined('GLOBAL_START') && Worker::runAll();

