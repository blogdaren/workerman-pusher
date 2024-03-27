<?php
/**
 * @script   Main.php
 * @brief    主配置文件
 * @author   blogdaren<blogdaren@163.com>
 * @link     http://www.phpcreeper.com
 * @create   2018-10-29
 */

/****************************基础设置************************************************/
date_default_timezone_set("Asia/Shanghai");


/****************************常量配置区域, 请根据情况自行配置************************/

//根目录
!defined('ROOT_DIR') && define("ROOT_DIR", dirname(dirname(__FILE__)));


/***************************非常量配置区域, 请根据情况自行配置**********************/

/**
 * 应用程序配置信息
 */
return array(
    //调试
    'debug' => true,

    //默认测试域名: 记得配置 /etc/hosts !!!
    'domain' => 'www.pusher.com',

    //模拟uid(也可以是订单id | 也可以是任务id | ....)
    'uids' => array('1', '2', '3', '4', '5', '6'),

    //超时: 秒
    'timeout' => array(
        'reconnect' => 2,
    ),

    //间隔: 秒
    'interval' => array(
        //页面弹窗通知间隔时间
        'notice' => 2,
        //客户端发送心跳间隔时间
        'client_heart' => 5,
    ),

    //socket
    'socket' => array(
        //监听服务
        'listen' => array(
            'web'       => 'http://0.0.0.0:7777',
            'pusher'    => 'websocket://0.0.0.0:3000',
            'inner'     => 'text://0.0.0.0:4000',
        ),
        //连接哪个内部推送地址
        'connect' => array(
            'inner'  => 'text://127.0.0.1:4000',
        ),
    ),
    //ping - heartbeat - 秒
    'ping' => array(
        'interval' => 10,
        'data'     => '',
        'is_force_client_to_ping_server'    => true,
    ),
);





