<?php
/**
 * Created by PhpStorm.
 * User: lxh
 * Date: 15-6-12
 * Time: 上午11:30
 * Desc: 新采集   //185127
 * Mark: 继续借用老的采集库搜索采集
 */
ini_set('display_errors', 'ON');
error_reporting(E_ALL);
defined('ROOT_PATH') or define('ROOT_PATH', dirname(__FILE__));

include(ROOT_PATH . '/../lib/CurlMulti/Core.php');
include(ROOT_PATH . '/../lib/CurlMulti/Exception.php');
include(ROOT_PATH . '/../lib/medoo.php' );
include(ROOT_PATH . '/../lib/RabbitMQ.php' );
include(ROOT_PATH . '/../lib/Beanstalk.php' );


$env = isset($argv[1]) ? $argv[1] : 'dev';
if($env == 'dev') {
    include(ROOT_PATH . '/../config/config_local.php');
}elseif($env == 'test'){
    include(ROOT_PATH . '/../config/config_test.php');
} elseif($env == 'pro') {
    include(ROOT_PATH . '/../config/config.php');
}else{
    die('config');
}

$db = new medoo($dbConfig);

$rabbitmqObj = new RabbitMQ($rabbitmqConfig);

$bean = new Socket_Beanstalk($bean_config);
$bean->connect();
$bean->watch('add_comname');

$arr = array("北京" =>"BJ", "天津" =>"TJ", "河北" =>"HB", "山西" =>"SX", "内蒙古" =>"NMG", "辽宁" =>"LN", "吉林" =>"JL",
    "黑龙江" =>"HLJ", "上海" =>"SH", "江苏" =>"JS", "浙江" =>"ZJ", "安徽" =>"AH", "福建" =>"FJ", "江西" =>"JX", "山东" =>"SD", "广东" =>"GD",
    "广西" =>"GX", "海南" =>"HAIN", "河南" =>"HEN", "湖北" =>"HUB", "湖南" =>"HUN", "重庆" =>"CQ" , "四川" =>"SC", "贵州" =>"GZ", "云南" =>"YN",
    "西藏" =>"XZ", "陕西" =>"SAX", "甘肃" =>"GS", "青海" =>"QH", "宁夏" =>"NX", "新疆" =>"XJ");

$agent = array(
    "Dalvik/2.1.0 (Linux; U; Android 5.1.1; Nexus 7 Build/LMY47V)",
    "Dalvik/2.1.0 (Linux; U; Android 2.3.6; zh-cn; GT-S5660 Build/GINGERBREAD) AppleWebKit/533.1)",
    "Dalvik/2.1.0 (Linux; U; Android 2.4.5; zh-cn; GT-S5660 Build/GINGERBREAD) AppleWebKit/533.1 )",
    "Dalvik/2.1.0 (Linux; U; Android 2.5.7; zh-cn; GT-S5660 Build/GINGERBREAD) AppleWebKit/533.1)",
    "Dalvik/2.1.0 (Linux; U; Android 2.6.9; zh-cn; GT-S5660 Build/GINGERBREAD) AppleWebKit/533.1)",
    "Dalvik/2.1.0 (Linux; U; Android 2.7.3; zh-cn; GT-S5660 Build/GINGERBREAD) AppleWebKit/533.1)",
    "Dalvik/2.1.0 (Linux; U; Android 2.8.1; zh-cn; GT-S5660 Build/GINGERBREAD) AppleWebKit/533.1)",
    "Dalvik/2.1.0 (Linux; U; Android 2.9.6; zh-cn; GT-S5660 Build/GINGERBREAD) AppleWebKit/533.1)",
    "Dalvik/2.1.0 (Linux; U; Android 3.3.6; zh-cn; GT-S5660 Build/GINGERBREAD) AppleWebKit/533.1)",
    "Dalvik/2.1.0 (Linux; U; Android 4.3.8; zh-cn; GT-S5660 Build/GINGERBREAD) AppleWebKit/533.1)",
    "Dalvik/2.1.0 (Linux; U; Android 5.0.1; zh-cn; GT-S5660 Build/GINGERBREAD) AppleWebKit/533.1)",
    "Dalvik/2.1.0 (iPhone; CPU iPhone OS 5_1 like Mac OS X) AppleWebKit/534.46 (KHTML, like Gecko) Mobile/9B176 MicroMessenger/4.3.2)",
    "Dalvik/2.1.0 (iPhone; CPU iPhone OS 5_1 like Mac OS X) AppleWebKit/534.46 (KHTML, like Gecko) Mobile/9B176 MicroMessenger/4.1.2)",
    "Dalvik/2.1.0 (iPhone; CPU iPhone OS 5_1 like Mac OS X) AppleWebKit/534.46 (KHTML, like Gecko) Mobile/9B176 MicroMessenger/4.2.2)",
    "Dalvik/2.1.0 (iPhone; CPU iPhone OS 5_1 like Mac OS X) AppleWebKit/534.46 (KHTML, like Gecko) Mobile/9B176 MicroMessenger/4.0.2)",
    "Dalvik/2.1.0 (iPhone; CPU iPhone OS 5_1 like Mac OS X) AppleWebKit/534.46 (KHTML, like Gecko) Mobile/9B176 MicroMessenger/5.0.2)",
    "Dalvik/2.1.0 (iPhone; CPU iPhone OS 5_1 like Mac OS X) AppleWebKit/534.46 (KHTML, like Gecko) Mobile/9B176 MicroMessenger/5.1.2)",
    "Dalvik/2.1.0 (iPhone; CPU iPhone OS 5_1 like Mac OS X) AppleWebKit/534.46 (KHTML, like Gecko) Mobile/9B176 MicroMessenger/5.2.2)",
    "Dalvik/2.1.0 (iPhone; CPU iPhone OS 5_1 like Mac OS X) AppleWebKit/534.46 (KHTML, like Gecko) Mobile/9B176 MicroMessenger/5.3.2)",
    "Dalvik/2.1.0 (iPhone; CPU iPhone OS 5_1 like Mac OS X) AppleWebKit/534.46 (KHTML, like Gecko) Mobile/9B176 MicroMessenger/5.3.2)",
    "Dalvik/2.1.0 (iPhone; CPU iPhone OS 5_1 like Mac OS X) AppleWebKit/534.46 (KHTML, like Gecko) Mobile/9B176 MicroMessenger/5.5.2)",
);

//采集的基础url
$baseUrl = 'http://app.qichacha.com/enterprises/new/a1/cloudSearch?';
//初始化项目
$curl = new CurlMulti_Core ();
$curl->opt[CURLOPT_TIMEOUT] = 5;
$curl->maxThread = 1;
$curl->maxTry = 1;
$curl->cbTask = array('addCollectTask', array());
$curl->start();

//初始化采集任务，取队列构造
function addCollectTask()
{
    global $curl, $baseUrl, $db, $agent, $rabbitmqObj, $bean;

    $list = array();
    while (count($list)<=$curl->maxThread) {
        $job = $bean->reserve(30);
        $list[] = unserialize($job['body']);
        $bean->delete($job['id']);
    }
    if (!empty($list)) {
        foreach ($list as $v) {
//            echo $v['province'], ":", $v['comname'], "\r\n";
            $data = array(
                'key'     => $v,
                'token'   => md5($v.'2c2c401f52b79fbc9740168d134b8954'),
            );
            $ip = rand(1,255).'.'.rand(1,255).'.'.rand(1,255).'.'.rand(1,255);//echo $ip,"\r\n";
            $ag = array_rand($agent);
            $head = array(
                "CLIENT-IP:{$ip}", "X-FORWARDED-FOR:{$ip}",
                "Referer:http://qichacha.com/",
                "User-Agent:{$ag}",
            );
            $task = array(
                'url'  => $baseUrl . "key={$v}&province=&token={$data['token']}",
                'args' => array('data' => $data),
                'opt'  => array(
                    CURLOPT_POST       => 0,
//                    CURLOPT_POSTFIELDS => $post,
                    CURLOPT_HTTPHEADER => $head,
                    CURLOPT_TIMEOUT    => 5,
                ),
            );
            $curl->add($task, 'cbProcess', 'cbFail');
        }
    }
    $db->clear();
}

//回调采集成功后业务处理
function cbProcess($r, $args = array())
{
    global $curl, $bean_config, $db, $arr, $baseUrl, $agent, $rabbitmqObj;
    if ($r['info']['http_code'] == 200) {
        $res = json_decode($r['content'], true);
        if (!empty($res['data'])) {//有结果
            $arr = array();
            foreach ($res['data'] as $value) {
                //去掉第一个 提示更新的
                if ($value['Unique'] == 'f625a5b661058ba5082ca508f99ffe1b') {
                    continue;
                }
                $arr[] = array(
                    'Name'   => $value['Name'],
                    'Unique' => $value['Unique'],
                );
            }

            $rabbitmqObj->set('combusiness', 'unique', json_encode($arr), 'ssdb');

            //写入关键词表
            $insert = array(
                'keyword' => $args['data']['key'],
                'has_res' => 1,
                'result'  => json_encode($arr),
            );
            $db->insert('a_keyword_app', $insert);
        }

        $data = $args['data'];

        //写入关键词表
        $insert = array(
            'keyword' => $args['data']['key'],
            'has_res' => 0,
            'result' => '',
        );
        $db->insert('a_keyword_app', $insert);

        $type = rand(0,1);
        while(true) {
            $num = 2;
            if (mb_strlen($data['key'], 'utf-8') <= 3) {
                return;
            }
            if($type){
                $data['key'] = mb_substr($data['key'], 0, mb_strlen($data['key'], 'utf-8') - $num, 'utf-8');
            }else{
                $data['key'] = mb_substr($data['key'], 2, mb_strlen($data['key'], 'utf-8') - $num, 'utf-8');
            }

            if (isKeyExist($data['key']) == false) {
                break;
            }
        }

        $token = md5($data['key'] . '2c2c401f52b79fbc9740168d134b8954');
        $ip = rand(1,255).'.'.rand(1,255).'.'.rand(1,255).'.'.rand(1,255);
        $ag = array_rand($agent);
        $head = array(
            "CLIENT-IP:{$ip}", "X-FORWARDED-FOR:{$ip}",
            "Referer:http://qichacha.com/",
            "User-Agent:{$ag}",
        );
        $task = array(
            'url'  => $baseUrl . "key={$data['key']}&province=&token={$token}",
            'args' => array('data' => $data),
            'opt'  => array(
                CURLOPT_POST       => 0,
                CURLOPT_HTTPHEADER => $head,
                CURLOPT_TIMEOUT    => 5,
            ),
        );
        $curl->add($task, 'cbProcess', 'cbFail');
//        }
    } else {
//        echo "4\r\n";
    }
    $db->clear();
}

//回调采集失败后业务处理
function cbFail($r, $args = array())
{
    global $ip;
//    echo "Faile url:", $r['info']['url'], "\r\n";
}

//查看采集各种状态信息 流量 等
function getStatusInfo($r)
{
}

//判断keyword是否搜索过，搜索过返回true 否则返回false
function isKeyExist($key)
{
    global $db;
    $db->clear();
    $resArr = $db->get('a_keyword_app', '*', array('keyword'=>$key, 'LIMIT'=>1));
    if(empty($resArr)){
        return false;
    }else{
        $db->update('a_keyword_app', array('times' => $resArr['times']+1), array('id' => $resArr['id']));
        return true;
    }
}
