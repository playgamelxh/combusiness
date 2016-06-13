<?php
/**
 * 将未更新的企业打入更新队列
 */
header("Content-type:text/html;charset=utf-8");
defined('ROOT_PATH') or define('ROOT_PATH', dirname(__FILE__));
ini_set ('memory_limit', '128M');

include(ROOT_PATH . '/../lib/medoo.php');
include(ROOT_PATH . '/../lib/RabbitMQ.php');

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

$db = new medoo($dbcomConfig);
$rabbitmqObj = new RabbitMQ($rabbitmqConfig);

//正常1-10 异常 11-20 其它21
//1 正常 2 异常 3其它

$min = isset($argv[2]) ? intval($argv[2]) : 0;
$max = isset($argv[3]) ? intval($argv[3]) : 0;
//$cid = $min;
//while($cid <= $max) {
$temp = $db->get('num', '*', array('id' => 6));
$cid = intval($temp['num']);
while(true){
    $resArr = $db->get('cb_combusiness', array('cid'), array('cid[>]' => $cid, 'LIMIT' => 1, 'ORDER' => 'cid asc'));
    if (!is_array($resArr) || empty($resArr) || $resArr['cid']<=0) {
        die('over');
    } else {
        $cid = $resArr['cid'];
    }
    $temp = $db->get('cb_combusiness_info', array('cid'), array('cid' => $cid, 'LIMIT' => 1, 'ORDER' => 'cid asc'));
    if(!is_array($temp) || empty($temp) || $temp['cid']<=0){
        //打入更新队列
        $rabbitmqObj->set('combusiness', 'importupdate', json_encode(array('cid' => $cid)), 'ssdb');
    }

    if($cid%10 == 0) {
        $db->update('num', array('num' => $cid), array('id' => 6));
    }
}