<?php
/**
 * 新数据入库后，触发详情页seo生成
 */
header("Content-type:text/html;charset=utf-8");
defined('ROOT_PATH') or define('ROOT_PATH', dirname(__FILE__));
ini_set ('memory_limit', '128M');

//include(ROOT_PATH . '/../config/config.php');
include(ROOT_PATH . '/../lib/medoo.php' );
include(ROOT_PATH . '/../lib/RabbitMQ.php' );

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

$temp = $db->get('num', '*', array('id' => 7));
$cid = intval($temp['num']);
while(true){
    $resArr = $db->get('cb_combusiness', array('cid', 'regno', 'areaid', 'address', 'regagency'), array('cid[>]' => $cid, 'LIMIT' => 1));
    if(!is_array($resArr) || empty($resArr)) {
        //没有数据 断开连接，休息30s，重新启动
        unset($db);
        unset($rabbitmqObj);
        sleep(30);
        $db = new medoo($dbcomConfig);
        $rabbitmqObj = new RabbitMQ($rabbitmqConfig);
        continue;
    }else{
        $cid = $resArr['cid'];
    }

    //企业详情页更新seo
    cominfoSeo($cid);

    $db->update('num', array('num' => $cid), array('id' => 7));
}

/**
 * 企业详情页更新seo
 */
function cominfoSeo($cid)
{
    global $rabbitmqObj;
    //同地区企业   热门关键词   站内推荐企业  队列广播模式
    $rabbitmqObj->set('v3cb', 'hy', $cid, 'ssdb');
}