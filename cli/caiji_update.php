<?php
/**
 * Created by PhpStorm.
 * User: Jam
 * Date: 2015/4/2
 * Time: 13:58
 * Desc：第一步完善修改
 * Mark: 继续借用老的采集库搜索采集
 */

header("Content-type:text/html;charset=utf-8");
ini_set('display_errors', 'ON');
error_reporting(E_ALL);
defined('ROOT_PATH') or define('ROOT_PATH', dirname(__FILE__));

include(ROOT_PATH . '/../lib/CurlMulti/Core.php');
include(ROOT_PATH . '/../lib/CurlMulti/Exception.php');
include(ROOT_PATH . '/../lib/medoo.php' );
include(ROOT_PATH . '/../lib/RabbitMQ.php' );
$areaArr = include(ROOT_PATH . '/../lib/areas/data.php');

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
$dbcom = new medoo($dbcomConfig);

$rabbitmqObj = new RabbitMQ($rabbitmqConfig);

$i = 0;
while (true) {
//    $job = $bean->reserve(30);
//    $arr = !empty($job['body']) ? json_decode($job['body'], true) : array();
    $rs = $rabbitmqObj->get('combusiness_unique_ssdb');
    $arr = !empty($rs) ? json_decode($rs, true) : array();

    $result = doJob($arr);
    $i++;
    if ($i%100==0) {
        unset($db);
        unset($dbcom);
        $db = new medoo($dbConfig);
        $dbcom = new medoo($dbcomConfig);
        sleep(1);
    }

    $db->clear();
    $dbcom->clear();
}

//处理程序
function doJob($arr)
{
    global $db, $dbcom, $rabbitmqObj;
    if (!is_array($arr) || empty($arr)) {
        return 0;
    }
    foreach ($arr as $value) {
        if (!is_array($value)) {
//            print_r($value);
            continue;
        }
        $value = array_filter($value);
        if (!isset($value['Name']) || empty($value)) {
            continue;
        }
        //结果是多个
        $comArr = $db->get('company', '*', array('comname' => $value['Name'], 'LIMIT'=>1));
        //没有No,一般为注销企业
        if (empty($value['No'])) {
            $value['No'] = 1;
        }
        if (empty($value['Unique'])) {
//            print_r($value);
//            echo '失效', "\r\n";
        }
        $id = 0;
        if (empty($comArr)) {
            //企业不存在新增
            $data = array(
                'comname'        => $value['Name'],
                'reg_id'         => $value['No'],
                'unique_id'      => $value['Unique'],
                'status'         => 1,
                'source'         => 3,
                'addtime'        => time(),
                'updatetime'     => time(),
                'province_store' => getProvince($value['No']),
                'province'       => province(getProvince($value['No'])),
            );
//            echo $value['Name'], '--insert', "\r\n";
            $id = $db->insert('company', $data);
        } else {
            //企业存在修改  数据不对时修改，详情未采集修改
            $resArr = $dbcom->get('cb_combusiness', '*', array('comname' => $comArr['comname'], 'LIMIT' => 1));
            //数据不存在 采集
            if (!is_array($resArr) || empty($resArr)) {
                $id = $comArr['id'];
            }
            if(empty($comArr['reg_id']) || $comArr['reg_id']==1 || empty($comArr['unique_id'])){
                $data = array(
//                    'reg_id' => $value['No'],
//                    'unique_id' => $value['Unique'],
                    'status' => 1,
                    'updatetime' => time(),
                );
                if ((empty($comArr['reg_id']) || $comArr['reg_id']==1) && (!empty($value['No']) && $value['No'] != 1)) {
                    $data['reg_id'] = $value['No'];
                }
                //企业详情不存在，改变唯一编号
                if (empty($resArr) && ($comArr['unique_id'] != $value['Unique'])) {
                    $data['unique_id'] = $value['Unique'];
                }
                if (isset($data['reg_id']) || isset($data['unique_id'])) {
                    $db->update('company', $data, array('id' => $id));
//                    echo $value['Name'], ":{$value['Unique']}--update", "\r\n";
                }
            }
        }
        if ($id > 0) {
            //写入到新队列
            if (!empty($value['Unique'])) {
                $data = array(
                    'id'        => $id,
                    'unique_id' => trim($value['Unique']),
                );
                //echo $beand->put(1024, 0, 1, json_encode($data)), ':', $id, "\r\n";
                $rabbitmqObj->set('combusiness', 'unique1', json_encode($data), 'ssdb');
            }
        }
    }
    return 1;
}

//处理省
function province($str)
{
    $arr = array(
        "总局"  => "CN",
        "北京"  => "BJ",
        "天津"  => "TJ",
        "河北"  => "HB",
        "山西"  => "SX",
        "内蒙古" => "NMG",
        "辽宁"  => "LN",
        "吉林"  => "JL",
        "黑龙江" => "HLJ",
        "上海"  => "SH",
        "江苏"  => "JS",
        "浙江"  => "ZJ",
        "安徽"  => "AH",
        "福建"  => "FJ",
        "江西"  => "JX",
        "山东"  => "SD",
        "广东"  => "GD",
        "广西"  => "GX",
        "海南"  => "HAIN",
        "河南"  => "HEN",
        "湖北"  => "HUB",
        "湖南"  => "HUN",
        "重庆"  => "CQ",
        "四川"  => "SC",
        "贵州"  => "GZ",
        "云南"  => "YN",
        "西藏"  => "XZ",
        "陕西"  => "SAX",
        "甘肃"  => "GS",
        "青海"  => "QH",
        "宁夏"  => "NX",
        "新疆"  => "XJ"
    );
    $temp = rtrim($str, "省市");
    if (isset($arr[$temp])) {
        return $arr[$temp];
    }
    foreach ($arr as $key => $value) {
        $p = "/{$key}/";
        if (preg_match($p, $str)) {
            return $value;
        }
    }
}

//根据注册号 获取省
function getProvince($num)
{
    if (strlen($num) != 13 && strlen($num) != 15) {
        return '';
    }
    global $areaArr;
    $num = substr($num, 0, 2) . '0000';
    $num = intval($num);
    if (isset($areaArr[$num])) {
        return $areaArr[$num];
    } else {
        return '';
    }
}
