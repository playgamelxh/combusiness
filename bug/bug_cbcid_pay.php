<?php
/**
 * Created by PhpStorm.
 * User: lxh
 * Date: 16/4/14
 * Time: 下午3:07
 * Desc: 付费供应商的联系方式优先展示
 */
header("Content-type:text/html;charset=utf-8");
defined('ROOT_PATH') or define('ROOT_PATH', dirname(__FILE__));
ini_set ('memory_limit', '128M');

include(ROOT_PATH . '/../config/cate.php');
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
$gcdb = new medoo($dbgccominfo);
$rabbitmqObj = new RabbitMQ($rabbitmqConfig);

$cid = 0;
while(true){
    $resArr = $gcdb->get('gc_company', array('cid', 'comname', 'vipbegin'), array('AND' => array('cid[>]' => $cid, 'vipbegin[>]' => 0), 'LIMIT' => 1, 'ORDER' => 'cid asc'));
    if (empty($resArr)) {
        die('Over!');
    }
    $cid = $resArr['cid'];
    echo $cid,"-";
    $combusArr = $db->get('cb_combusiness', array('cid', 'comname', 'gccid'), array('comname' => $resArr['comname']));
//    print_r($resArr);
//    print_r($combusArr);die();
    if (is_array($combusArr) && !empty($combusArr)) {
        echo $cid,"\r\n";
        $temp[] = $cid;
        $cidArr = empty($combusArr['gccid']) ? array() : explode(',', $combusArr['gccid']);
        if (is_array($cidArr) && !empty($cidArr)) {
            foreach ($cidArr as $v) {
                if ($v != $cid) {
                    $temp[] = $v;
                    if (count($temp) >=3) {
                        break;
                    }
                }
            }
        }
        //更新数据
        $db->update('cb_combusiness', array('gccid' => implode(',', $temp)), array('comname' => $resArr['comname']));
        //清理缓存
        $rabbitmqObj->set('combusiness', 'cbupdate', $combusArr['cid'], 'ssdb');
    }

    $db->clear();
    $gcdb->clear();
}