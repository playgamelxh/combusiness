<?php
/**
 * 处理错误数据
 */
header("Content-type:text/html;charset=utf-8");
defined('ROOT_PATH') or define('ROOT_PATH', dirname(__FILE__));
ini_set ('memory_limit', '128M');

include(ROOT_PATH . '/../lib/medoo.php' );
include(ROOT_PATH . '/../lib/RabbitMQ.php' );

$db = new medoo(array(
    'database_type' => 'mysql',
    'database_name' => 'combusiness',
//    'server' => '10.66.101.190',
    'server' => '172.17.18.2',
    'username' => 'root',
    'password' => 'gc7232275',
    'port' => 3306,
    'charset' => 'utf8',
    'option' => array(PDO::ATTR_CASE => PDO::CASE_NATURAL, PDO::ATTR_PERSISTENT => true)
));

//7619129  8000286-11615277  18377770
$min = isset($argv[1]) ? intval($argv[1]) : 0;
$max = isset($argv[2]) ? intval($argv[2]) : 0;
#$cid = 8000000;
$cid = $min;
while($cid <= $max) {
    $resArr = $db->get('cb_combusiness', array('cid', 'regno', 'areaid'), array('cid' => $cid, 'LIMIT' => 1));
    if(is_array($resArr) && !empty($resArr)) {
        //省 市 区
        $areaid = $resArr['areaid'];
        $update = array();
        if($areaid <= 0) {
            if(is_numeric($resArr['regno'])){
                $areaid = getAreaId($resArr['regno']);
                $update['areaid'] = $areaid;
            }
        }
        if($areaid > 0) {
            $update['province'] = intval($resArr['areaid']/10000)*10000;
            $temp = substr($resArr['areaid'], 0, 2);
            if (in_array($temp, array(11, 12, 31, 50))) {
                $update['city'] = intval($temp . '0100');
            } else {
                $update['city'] = intval($resArr['areaid']/100)*100;
            }
            $update['zone'] = $resArr['areaid'];
            $db->update('cb_combusiness', $update, array('cid' => $resArr['cid']));
        }
    }
    $cid++;
    if($cid%10 == 0){
//        echo $cid,"\r\n";
    }
    $db->clear();
}

/**
 * 功能描述 根据注册号 获取地区编号id
 * @author 吕小虎
 * @datetime ${DATE} ${TIME}
 * @version
 * @param
 * @return
 */
function getAreaId($regno)
{
    if (empty($regno)) {
        return 0;
    }
    $code = substr($regno, 0, 6);
    if (!is_numeric($code)) {
        return 0;
    }
    $areaid = findArea($code);
    if ($areaid==0) {
        $code = intval(substr($code, 0, 4))*100;
        $areaid = findArea($code);
        if ($areaid==0) {
            $code = intval(substr($code, 0, 2))*10000;
            $areaid = findArea($code);
        }
    }
    return $areaid;
}

function findArea($areaid)
{
//        $temp = Area::findFirst("id = {$areaid}");
//        $resArr = is_object($temp) ? $temp->toArray() : array();
    global $db;
    $resArr = $db->get('area', '*', array('id' => $areaid));
    if (empty($resArr)) {
        return 0;
    } else {
        return $resArr['id'];
    }
}