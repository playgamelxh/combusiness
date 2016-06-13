<?php
/**
 * 处理注册资本筛选
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

$temp = $db->get('num', '*', array('id' => 5));
$cid = intval($temp['num']);
while(true) {
    $resArr = $db->get('cb_combusiness', array('cid', 'comname', 'RegistCapi'), array('cid[>]' => $cid, 'LIMIT' => 1));
    if(is_array($resArr) && !empty($resArr)){
        $temp = $db->get('cb_com_capita', '*', array('comname' => $resArr['comname'], 'limit' => 1));
        //处理注册资本
        if(is_array($temp) && !empty($temp['RegistCapi'])) {
            $regcap = 0;
            $temp = str_replace(array(',', '，'), '', $temp['RegistCapi']);
            if(strpos($temp, '万') !== false) {
                $regcap = floatval($temp);
            } else {
                $regcap = floatval($temp)/10000;
            }
            $db->update('cb_combusiness', array('regcapital' => $regcap, 'RegistCapi' => $temp['RegistCapi']), array('cid' => $resArr['cid']));
        }
        $cid = $resArr['cid'];
        if($cid%100 == 0){
            $db->update('num', array('num' => $cid), array('id' => 5));
            print_r($db->error());
        }
    }else{
        die("Over!\r\n");
    }
    $db->clear();
}