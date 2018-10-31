<?php
/**
 * @brief    模拟后端推送业务代码
 *
 * @script   start_client.php
 * @author   blogdaren<blogdaren@163.com>
 * @version  1.0.0
 * @modify   2018-10-29
 */

use \Workerman\Worker;
use \Workerman\Connection\AsyncTcpConnection;
use \Workerman\Lib\Timer;

//include
$config = include(__DIR__ . "/Config/Main.php");

//autoload
require_once dirname(dirname(ROOT_DIR)) . '/vendor/autoload.php';


//config: 后续考虑封装起来
$uids = $config['uids']; 
$interval_notice = $config['interval']['notice'];
$timeout_reconnect = $config['timeout']['reconnect'];
$inner_socket = $config['socket']['connect']['inner'];
$debug = $config['debug'];
//config: 后续考虑封装起来


$worker = new Worker();
$worker->name = "webAdminClientWorker";
$worker->onWorkerStart = function($worker)use($inner_socket){
    $connection = new AsyncTcpConnection($inner_socket);
    $connection->onConnect = function($connection){
        global $interval_notice;
        Timer::add($interval_notice,function()use($connection){
            global $uids;
            foreach($uids as $uid)
            {
                $now_time = date('Y-m-d H:i:s');
                $data = array(
                    'uid' => $uid,
                    'msg' => '后端推来时间：'. $now_time,
                );
                $data = json_encode($data);
                $connection->send($data);
            }
        }, array(), true);
    };  

    $connection->onClose = function($connection){
        global $timeout_reconnect, $debug;
        true === $debug && pprint("connection closed....will reconnect after {$timeout_reconnect} seconds");
        $connection->reconnect($timeout_reconnect);
    };  

    $connection->onMessage = function($connection, $data){
        global $debug;
        true === $debug && pprint($data);
    };

    $connection->connect();
};


//run all worker instance
!defined('GLOBAL_START') && Worker::runAll();


