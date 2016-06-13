<?php
/**
 * Created by PhpStorm.
 * User: Jam
 * Date: 2015/4/8
 * Time: 10:58
 * Desc：遗漏补缺
 */
header("Content-type:text/html;charset=utf-8");
defined('ROOT_PATH') or define('ROOT_PATH', dirname(__FILE__));
ini_set ('memory_limit', '128M');

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

$db = new medoo($dbConfig);

$rabbitmqObj = new RabbitMQ($rabbitmqConfig);

$temp = $db->get('num', '*', array('id' => 8));
$id = $temp['num'];

while(true){
//        $resArr = $db->query("select * from company where id>{$id} and unique_id ='' limit 1")->fetch();
//        $resArr = $db->get('company', '*', array('AND' => array('id[>]' => $id, 'unique_id' => ''), 'LIMIT' => 1));
    $resArr = $db->get('company', '*', array('id[>]' => $id, 'LIMIT' => 1, 'ORDER' => 'id asc'));
    if(empty($resArr)){
        sleep(60);
    }
    if(is_numeric($resArr['comname']) && empty($resArr['unique_id'])){
        $db->delete('company', array('id' => $resArr['id'], 'LIMIT' => 1));
    }

    $id = $resArr['id'];
    $db->update('num', array('num' => $id), array('id' => 8));

    $data = array(
        'id' => $resArr['id'],
        'province' => $resArr['province'],
        'comname' => $resArr['comname'],
    );

    $rabbitmqObj->set('combusiness', 'comname', json_encode($data), 'ssdb');

    unset($data);
    $db->clear();
}
