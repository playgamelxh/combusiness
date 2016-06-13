<?php
/**
 * 处理企业状态整形化 注册资本整形化  和分类状态
 */
header("Content-type:text/html;charset=utf-8");
defined('ROOT_PATH') or define('ROOT_PATH', dirname(__FILE__));
ini_set ('memory_limit', '128M');

include(ROOT_PATH . '/../config/cate.php');
include(ROOT_PATH . '/../lib/medoo.php' );

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

//正常1-10 异常 11-20 其它21
//1 正常 2 异常 3其它

$min = isset($argv[2]) ? intval($argv[2]) : 0;
$max = isset($argv[3]) ? intval($argv[3]) : 0;
//$cid = $min;
//while($cid <= $max) {
$temp = $db->get('num', '*', array('id' => 2));
$cid = intval($temp['num']);
$cid = 25000000;
while($cid <= 25225206){
    $resArr = $db->get('cb_combusiness', array('cid', 'state', 'comtype', 'RegistCapi'), array('cid[>]' => $cid, 'LIMIT' => 1, 'ORDER' => 'cid asc'));
    if (!is_array($resArr) || empty($resArr)) {
        unset($db);
        sleep(3);
        $db = new medoo($dbcomConfig);
        continue;
    } else {
        $cid = $resArr['cid'];
    }
//    echo $cid, "\r\n";
//    if($cid%10 == 0) {
        $db->update('num', array('num' => $cid), array('id' => 2));
//    }

    $update = array();
    //处理企业状态
//    $update['intstate'] = getState($resArr['state']);

    //处理企业类型
//    $update['intcomtype'] = getComType($resArr['comtype']);

    //处理注册资本  25000000 25225206
    $update['regcapital'] = regcap($resArr['RegistCapi']);

    $db->update('cb_combusiness', $update, array('cid' => $cid));

    $db->clear();
}

//状态
function getState($str)
{
    if(empty($str)) {
        return 3;
    }
    $companyState = array(
        '在营' => 1,
        '开业' => 2,
        '存续' => 3,
        '正常' => 4,
        '成立' => 5,
        '迁入' => 6,

        '未注销' => 12,
        '吊销'   => 11,
        '注销'   => 13,
        '撤销'   => 17,
        '迁出'   => 14,
        '清算'   => 15,
        '停业'   => 16,
    );

    foreach ($companyState as $key => $value){
        if (strpos($str, $key) !== false) {
            return $value;
        }
    }
    return 3;
}

//企业类型
function getComType($str)
{
    if (empty($str)) {
        return 19;
    }
    $companyType = array(
        "有限责任公司"                => 1,
        "股份有限公司"                => 2,
        "内资企业"                   => 3,
        "集体"                      => 4,//集体企业
        "股份合作"                   => 5,//股份合作企业
        "联营"                      => 6,//联营企业
        "私营"                      => 7,//私营企业
        "港，澳，台商投资"            => 8,//港，澳，台商投资企业
        "合资经营企业"               => 9,//合资经营企业(港或澳，台资)
        "合作经营企业"               => 10,//合作经营企业(港或澳，台资)
        "港，澳，台商独资企业"        => 11,
        "港，澳，台商投资股份有限公司" => 12,
        "外商投资企业"               => 13,
        "中外合资经营企业"           => 14,
        "中外合作经营企业"           => 15,
        "外资企业"                  => 16,
        "外商投资股份有限公司"        => 17,
        "国有"                     => 18,//国有企业
        "个体"                     => 20, //个体经营
        "个人"                     => 20,//个人独资企业
        "其他企业"                  => 19,
    );
    foreach($companyType as $key => $value) {
        if(strpos($str, $key) !== false) {
            return $value;
        }
    }
    return 19;
}

//转换注册资本
function regcap($regcapStr)
{
    $regcap = 0;
    $temp = str_replace(array(',', '，'), '', $regcapStr);
    if(strpos($temp, '万') !== false) {
        $regcap = floatval($temp);
    } else {
        $regcap = floatval($temp)/10000;
    }
    return $regcap;
}