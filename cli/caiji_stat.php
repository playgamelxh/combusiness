<?php
/**
 * Created by PhpStorm.
 * User: Jam
 * Date: 2015/4/7
 * Time: 8:35
 * Desc：统计
 */
ini_set('display_errors', 'ON');
error_reporting(E_ALL);
date_default_timezone_set('PRC');
defined('ROOT_PATH') or define('ROOT_PATH', dirname(__FILE__));

include(ROOT_PATH . '/../lib/medoo.php' );
$db = new medoo(array(
    'database_type' => 'mysql',
    'database_name' => 'com_engine',
//    'server' => '10.66.101.190',
    'server' => '172.17.18.2',
    'username' => 'root',
    'password' => 'gc7232275',
    'port' => 3306,
    'charset' => 'utf8',
    'option' => array(PDO::ATTR_CASE => PDO::CASE_NATURAL)
));

$start = strtotime(date("Y-m-d"));
$end   = time();

$areaArr = array("总局" => "CN", "北京" =>"BJ", "天津" =>"TJ", "河北" =>"HB", "山西" =>"SX", "内蒙古" =>"NMG", "辽宁" =>"LN", "吉林" =>"JL",
    "黑龙江" =>"HLJ", "上海" =>"SH", "江苏" =>"JS", "浙江" =>"ZJ", "安徽" =>"AH", "福建" =>"FJ", "江西" =>"JX", "山东" =>"SD", "广东" =>"GD",
    "广西" =>"GX", "海南" =>"HAIN", "河南" =>"HEN", "湖北" =>"HUB", "湖南" =>"HUN", "重庆" =>"CQ" , "四川" =>"SC", "贵州" =>"GZ", "云南" =>"YN",
    "西藏" =>"XZ", "陕西" =>"SAX", "甘肃" =>"GS", "青海" =>"QH", "宁夏" =>"NX", "新疆" =>"XJ");

while($start<$end){
    deal($start);
    $start +=86400;
}

function deal($start)
{
    global $db, $areaArr;
    $data['daytime'] = $start;
    //当天添加企业
    $data['add_num'] = $db->count('company', array("AND" => array("addtime[>]" => $start, "addtime[<]" => $start+86400)));
    //当天第一步采集企业
//        $data['step1_num'] = $db->count('company', array("AND" => array("updatetime[>]" => $start, "updatetime[<]" => $start+86400, 'status' => 1)));
    //当天第二步采集企业
//        $data['step2_num'] = $db->count('company', array("AND" => array("updatetime[>]" => $start, "updatetime[<]" => $start+86400, 'status' => 2)));
    //各省情况
    $data['all_num'] = 0;
    $data['detail'] = array();
    foreach($areaArr as $key => $value){
        $table = "engine_company_".strtolower($value);
        $data['detail'][$key] = $db->count($table, array());
        $data['all_num'] +=  $data['detail'][$key];
    }
    $data['detail'] = json_encode($data['detail']);
    $res = $db->get('a_stat', '*', array('daytime' => $start));
    if($res['id']>0){
        $data['step2_num'] = $data['all_num']-$res['step1_num'];
        $db->update('a_stat', $data, array('id' => $res['id']));
    }else{
        $data['step1_num'] = $data['all_num'];
        $data['step2_num'] = 0;
        $db->insert('a_stat', $data);
    }
}
