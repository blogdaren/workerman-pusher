<?php
//config: 后续考虑封装起来
$config = include(dirname(__DIR__) . "/Config/Main.php");
$uids = json_encode($config['uids']); 
list($min_uid, $max_uid) = array(min($config['uids']), max($config['uids']));
$interval_notice = $config['interval']['notice'] * 1000;
$interval_client_heart  = $config['interval']['client_heart'] * 1000;
$pusher_socket = $config['socket']['listen']['pusher'];
$tmp = explode(':', $pusher_socket);
$pusher_port = array_pop($tmp);
//config: 后续考虑封装起来
?>

<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<title>Workerman-Pusher-Demo</title>

<link crossorigin="anonymous" media="all" rel="stylesheet" href="/css/main.css">
<script type="text/javascript" src="/js/jquery.min.js"></script>
<script type="text/javascript" src="/js/reconnecting-websocket.js"></script>
<script type="text/javascript" src="/layer-v3.1.1/layer/layer.js"></script>

<script type="text/javascript">
var ws, uid;
var interval_notice = <?php echo $interval_notice; ?>;
var interval_client_heart = <?php echo $interval_client_heart; ?>;
var uids = <?php echo $uids; ?>;
var min_uid = <?php echo $min_uid; ?>;
var max_uid = <?php echo $max_uid; ?>;
var pusher_port = <?php echo $pusher_port; ?>;

//connect server
function connect() {
    //websocket instance
    var url = "ws://" + document.domain + ":" + pusher_port;
    ws = new ReconnectingWebSocket(url);

    //debug 
    ws.debug = true;

    //the number of milliseconds to delay before attempting to reconnect
    ws.reconnectInterval = 1000;

    //onopen
    ws.onopen = onopen;

    //onmessage
    ws.onmessage = onmessage; 

    //onClose
    ws.onclose = function(error_code, error_msg) {
        var log_msg = "--> Connection is closed by server, trying to reconnect after " + ws.reconnectInterval/1000 + " seconds later<br>";
        console.log(log_msg);
        $("#tips").append(log_msg);
        timer_id2 && clearInterval(timer_id2);
    };

    //onError
    ws.onerror = onerror;
}

function onopen()
{
    var index = layer.prompt({
        title:'测试昵称只能是' + min_uid + '到' + max_uid + '之间任一整数',
        closeBtn: 0,
    },function(val, index){
        layer.close(index);
        uid = val;

        //if invalid then reload 
        if($.inArray(uid, uids) < 0){
            location.href = "/";
            return false;
        }

        var tips_title = '<span style="color:white;font-weight:bold;">## WEB后台单向推送数据到客户端展示 ##</span><br>';
        $('#tipsTitle').css('background-color', '#19A89F');
        $('#tipsTitle').css('border', '8px solid #19A89F');
        $('#tipsTitle').css('border-bottom', 'none');
        $("#tipsTitle").empty().html(tips_title);

        $('#tips').css('border', '8px solid #19A89F');
        var log_msg = '--> User【' + uid  + '】connected to server ok<br>';
        $("#tips").empty().append(log_msg);
        var data = {'uid':uid};
        ws.send(JSON.stringify(data));

        //客户端主动发送心跳
        var timer_id2 = setInterval(function(){
            var log_msg = "--> Send heartbeat to server at intervals<br>";
            console.log(log_msg);
            $("#tips").append(log_msg);
            var tipsDiv = document.getElementById("tips");
            tipsDiv.scrollTop = tipsDiv.scrollHeight;
            ws.send('{"event":"ping"}');
        }, interval_client_heart);
    });

    layer.style(index, {
      top: '200px',
    });  
}

function onmessage(event)
{
    //var data = JSON.parse(event.data);
    console.log(event.data);
    $("#tips").append("<span style='color:white;'><-- " + event.data + "</span><br>");
    var content = '尊敬的用户' + uid + ':<br /><br/>';
    content += "<div style='margin-left:30px;font-size:15px;letter-spacing:0.05em;'>你好, 以下红色字体内容是WEB后台单向为您推送的通知:<br><br>";
    content += "<span style='color:red;'> ******* " + event.data + ' *******</span><br><br>';
    content += "【1】Bloger:&nbsp;&nbsp;<a href='http://www.blogdaren.com' target='_blank'>http://www.blogdaren.com</a>" + '<br><br>';
    content += "【2】Github:&nbsp;&nbsp;<a href='https://github.com/blogdaren' target='_blank'>https://github.com/blogdaren</a>" + '<br><br>';
    content += "【3】Notice:&nbsp;&nbsp;" + interval_notice / 1000 + '秒后本窗口将自动关闭以重载接收最新通知<div><br>';

    var index = layer.alert(content, {
        title: '模拟WEB后台单向异步推送业务通知',
        skin: 'layui-layer-molv',
        area: ['550px', '450px'], 
        closeBtn: 1,
        shade: false,
        //time: 5000,
    });

    layer.style(index, {
      top: '8px',
      left: '415px',
    });  
}

function onerror(){
}

</script>
</head>


<body onload="connect();" class="layui-bg-green">

<div id="tipsTitle"></div>
<div id="tips"></div>

</body>


</html>






