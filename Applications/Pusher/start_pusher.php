<?php 
/**
 * @script   start_pusher.php
 * @brief    
 * @author   blogdaren<blogdaren@163.com>
 * @link     http://www.phpcreeper.com
 * @create   2018-10-28
 */

use \Workerman\Worker;
use \Workerman\Lib\Timer;

//autoload
require_once dirname(dirname(__DIR__)) . '/vendor/autoload.php';

class Pusher 
{
    /**
     *  single instance
     *  @var object 
     */
    static protected $_instance;

    /**
     *  pusher
     *  @var object
     */
    protected $_pusher;

    /**
     *  uid mapping to connection 
     *  @var array
     */
    public $uidConnections = array();

    /**
     *  configuration
     *  @var array
     */
    private $_config = array();

    /**
     *  ping interval
     *  @var int
     */
    public $pingInterval = 0;

    /**
     *  send ping data to client
     *  @var string
     */
    public $pingData = '';

    /**
     *  whether force client to ping server or not
     *  @var boolean
     */
    public $isForceClientToPingServer = false;

    /**
     *  whether received client ping or not 
     *  @var boolean
     */
    public $isReceivedClientPing = false;

    /**
     *  all timer
     *  @var array
     */
    public $_timers = array();

    /**
     * @brief   get single instance 
     *
     * @return  object
     */
    static public function getInstance()
    {
        if(!self::$_instance instanceof self)
        {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    /**
     * @brief    start entry
     *
     * @return   null
     */
    public function start()
    {
        //config: 后续考虑封装起来
        $this->_config = include(__DIR__ . "/Config/Main.php");
        $pusher_socket = $this->_config['socket']['listen']['pusher'];
        //config: 后续考虑封装起来
        
        //pusher
        $this->_pusher = new Worker($pusher_socket);

        //pusher name
        $this->_pusher->name = 'pusherGateway';

        //must be set to 1 here to ensure that all connections stay in the same one process 
        $this->_pusher->count = 1;

        //uid mapping to connection
        $this->_pusher->uidConnections = array();

        //set callback
        $this->_pusher->onWorkerStart   = array($this, 'onPusherStart');
        $this->_pusher->onWorkerStop    = array($this, 'onPusherStop');
        $this->_pusher->onConnect       = array($this, 'onClientConnect');
        $this->_pusher->onClose         = array($this, 'onClientClose');
        $this->_pusher->onMessage       = array($this, 'onClientMessage');

        //heartbeat
        $this->_config['ping']['interval'] > 0 && $this->pingInterval = $this->_config['ping']['interval'];
        !empty($this->_config['ping']['data']) && $this->pingData = $this->_config['ping']['data'];
        $this->isForceClientToPingServer = $this->_config['ping']['is_force_client_to_ping_server'];
    }

    /**
     * @brief    onPusherStart  
     *
     * @param    object $worker
     *
     * @return   null
     */
    public function onPusherStart($worker)
    {
        //heartbeat
        if($this->pingInterval > 0)
        {
            $timer_id = Timer::add($this->pingInterval, array($this, 'ping')); 
            $this->_timers[] = $timer_id;
        }

        //inner push port !! attention !!
        $inner_socket = $this->_config['socket']['listen']['inner'];
        $inner_worker = new Worker($inner_socket);
        $inner_worker->name = 'innerWorker';
        $inner_worker->onMessage = function($connection, $data){
            $data = json_decode($data, true);
            @extract($data);

            if(empty($uid) || empty($msg)){ 
                $reply = 'invald data: should be like: `{"uid":1,"msg":"blogdaren"}`';
                $connection->send($reply);
                return;
            }

            $rs = $this->forwardMessageToClient($uid, $msg);
            $result = ($rs === true) 
                    ? "forward to client-{$uid} success 【+++++】" 
                    : "forward to client-{$uid} failed  【-----】";
            $connection->send($result);
        };

        //work in child process so need to call listen()
        $inner_worker->listen();
    }

    /**
     * @brief    onPusherStop
     *
     * @param    object $worker
     *
     * @return   null
     */
    public function onPusherStop($worker)
    {
    }

    /**
     * @brief    onClientConnect    
     *
     * @param    object $connection
     *
     * @return   null
     */
    public function onClientConnect($connection)
    {
        $connection->isReceivedClientPing = true;
    }

    /**
     * @brief    onClientMessage    
     *
     * @param    object $connection
     * @param    string $data
     *
     * @return   
     */
    public function onClientMessage($connection, $data)
    {
        $data = json_decode($data, true);

        if(isset($data['event']) && $data['event'] == 'ping') 
        {
            $connection->isReceivedClientPing = true;
        }

        if(isset($data['uid']))
        {
            $uid = $data['uid'];
            !isset($connection->uid) && $connection->uid = $uid;

            if(!isset($this->_pusher->uidConnections[$connection->uid]))
            {
                $this->_pusher->uidConnections[$connection->uid] = $connection;
            }
            else
            {
                if($uid == $connection->uid)
                {
                    //kick out if use the same uid to login
                    $con = $this->_pusher->uidConnections[$connection->uid];
                    $con->close();
                    unset($this->_pusher->uidConnections[$connection->uid]);

                    //new user join in 
                    $this->_pusher->uidConnections[$connection->uid] = $connection;
                }
            }

            $this->_config['debug'] && pprint(array_keys($this->_pusher->uidConnections));
        }
    }

    /**
     * @brief    onClientClose  
     *
     * @param    object $connection
     *
     * @return   null
     */
    public function onClientClose($connection)
    {
        if(!isset($connection->uid) || empty($connection->uid)) return;

        $this->_config['debug'] && pprint($connection->uid . " say bye bye ...");

        if(isset($this->_pusher->uidConnections[$connection->uid]))
        {
            unset($this->_pusher->uidConnections[$connection->uid]);
        }

        $this->_config['debug'] && pprint(array_keys($this->_pusher->uidConnections));
    }

    /**
     * @brief    forwardMessageToClient     
     *
     * @param    string  $uid
     * @param    string  $msg
     *
     * @return   boolean
     */
    public function forwardMessageToClient($uid = '', $msg = '')
    {
        if(empty($uid) || empty($msg)) return false;

        if(array_key_exists($uid, $this->_pusher->uidConnections))
        {
            $this->_config['debug'] && pprint('client-'.$uid.' is online  【+++++】');
            $connection = $this->_pusher->uidConnections[$uid];
            $connection->send($msg);
            return true;
        }

        $this->_config['debug'] && pprint('client-'.$uid.' is offline 【-----】');

        return false;
    }

    /**
     * @brief    ping   
     *
     * @return   null 
     */
    public function ping()
    {
        foreach($this->_pusher->uidConnections as $connection)
        {
            if($this->isForceClientToPingServer && false === $connection->isReceivedClientPing)
            {
                $connection->close();
                continue;
            }

            $connection->isReceivedClientPing = false;
            $this->pingData && $connection->send((string)$this->pingData);
        }
    }

    /**
     * @brief    removeAllTimer     
     *
     * @return   null
     */
    public function removeAllTimer()
    {
        foreach($this->_timers as $timer_id)
        {
            Timer::del($timer_id);
        }
    }
}

//start pusher
Pusher::getInstance()->start();

//run all worker instance
!defined('GLOBAL_START') && Worker::runAll();



