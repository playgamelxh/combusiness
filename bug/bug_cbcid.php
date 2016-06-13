<?php
/**
 * 处理关联
 * 规则，最多关联三个，首先通过执照审核的位主联系方式，放第一位；黄页有商铺的(username有值的)才可以关联
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
$caijidb = new medoo($dbcaijicominfo);
$rabbitmqObj = new RabbitMQ($rabbitmqConfig);
$rabbitmq108Obj = new RabbitMQ($rabbitmq108Config);

$min = isset($argv[2]) ? intval($argv[2]) : 0;
$max = isset($argv[3]) ? intval($argv[3]) : 0;
//$cid = $min;
//while($cid <= $max) {
$temp = $db->get('num', '*', array('id' => 1));
$cid = $temp['num'];
//0 - 738596
//$cid = $min;
$i   = 0;
while(true){

     //重新关联
    $resArr = $db->get('cb_combusiness', array('cid', 'comname', 'gccid'), array('cid[>]' => $cid, 'LIMIT' => 1, 'ORDER' => 'cid asc'));
    if (!is_array($resArr) || empty($resArr)) {
        die("Over!---{$cid}---\r\n");
    } else {
        $cid = $resArr['cid'];
    }

    if($cid%100==0){
        $db->update('num', array('num' => $cid), array('id' => 1));
    }

    $cidArr = array();

    //查询工厂库 获得企业cid 限定企业状态 最多取3个
    $comArr = $gcdb->select('gc_company', array('cid'), array('AND' => array('comname' => $resArr['comname'], 'state[>]' => 0), 'LIMIT' => 3));
    if(is_array($comArr) && !empty($comArr)) {
        foreach($comArr as $value){
            $cidArr[] = $value['cid'];
        }
        if(!empty($cidArr)){
            //获取最早通过营业执照审核的企业
            $liceArr = $gcdb->get('gc_buslicense', array('cid'), array('cid' => $cidArr, 'order' => 'addtime asc', 'LIMIT' => 1));
            //如果有，把该企业cid放到首页
            if(is_array($liceArr) && $liceArr['cid']>0){
                array_unshift($cidArr, $liceArr['cid']);    //开头添加重要联系企业
                $cidArr = array_unique($cidArr);            //去重
            }
        }
    }

    //如果不足三个 从黄页中寻找
    if (count($cidArr) < 3){
        //查询采集库 获得企业cid 企业状态正常 username不为空有商铺地址 限制数目
        $num = 3 - count($cidArr);
        $cajiArr = $caijidb->select('gc_company', array('cid'),
            array('AND' => array('comname' => $resArr['comname'], 'state[>]' => 0, 'username[!]' => '', 'LIMIT' => $num))
        );
        if(is_array($cajiArr) && !empty($cajiArr)) {
            foreach ($cajiArr as $value) {
                $cidArr[] = $value['cid'];
            }
        }
    }

    //修改关联字段
    $cidStr = (is_array($cidArr) && !empty($cidArr)) ? implode(',', $cidArr) : '';
    if ($resArr['gccid'] != $cidStr) {
        $db->update('cb_combusiness', array('gccid' => $cidStr), array('cid' => $resArr['cid']));
        $rabbitmqObj->set('combusiness', 'cbupdate', $resArr['cid'], 'ssdb');
    }


    //处理已经关联企业 省份错误问题

//    $resArr = $db->get('cb_combusiness', array('cid', 'comname', 'province', 'gccid'), array('AND' => array('cid[>]' => $cid, 'gccid[!]' => ''), 'LIMIT' => 1, 'ORDER' => 'cid asc'));
//    if (!is_array($resArr) || empty($resArr)) {
//        die("Over!---{$cid}---\r\n");
//    } else {
//        $cid = $resArr['cid'];
//    }
//    if ($i%10==0) {
////        echo "{$i}:",$cid,'|';
//        $db->update('num', array('num' => $cid), array('id' => 1));
//    }

    //修复错误省份
//    wrongProvince($resArr);

    //修复错误关联id
//    wrongCbcid($resArr);

    $i++;
    $db->clear();
    $gcdb->clear();
    $caijidb->clear();
}

//处理错误的cbcid
function wrongCbcid($resArr)
{
    global $db, $gcdb, $caijidb, $rabbitmq108Obj;

    $cidStr = $resArr['gccid'];
    $cidArr = explode(',', $cidStr);
    $comname = $resArr['comname'];

    //获取供应商
    $gcArr = $gcdb->select('gc_company', array('cid'), array('AND' => array('comname' => $comname, 'cbcid[>]' => 0)));
    if(is_array($gcArr)) {
        $gcCidArr = array();
        foreach($gcArr as $value) {
            if(!in_array($value['cid'], $cidArr)) {
                $gcCidArr[] = $value['cid'];
            }
        }
        if(!empty($gcCidArr)) {
            $gcdb->update('gc_company', array('cbcid' => 0), array('cid' => $gcCidArr));
            foreach ($gcCidArr as $cid) {
                //打队列
                $rabbitmq108Obj->set('v3com', 'com', $cid, 'ssdb');
            }
        }
    }
    //采集企业
    $caiArr = $caijidb->select('gc_company', array('cid'), array('AND' => array('comname' => $comname, 'cbcid[>]' => 0)));
    if(is_array($caiArr)) {
        $caijiCidArr = array();
        foreach($caiArr as $value) {
            if(!in_array($value['cid'], $cidArr)) {
                $caijiCidArr[] = $value['cid'];
            }
        }
        if(!empty($caijiCidArr)) {
            $caijidb->update('gc_company', array('cbcid' => 0), array('cid' => $caijiCidArr));
            foreach ($caijiCidArr as $cid) {
                //打队列
                $rabbitmq108Obj->set('v3wwwcaiji', 'com', $cid, 'ssdb');
            }
        }
    }
}


//处理错误省份
function wrongProvince($resArr)
{
    global $db, $gcdb, $caijidb, $rabbitmqObj, $rabbitmq108Obj;
    $cidStr = $resArr['gccid'];
    $cidArr = explode(',', $cidStr);

    if (is_array($cidArr) && !empty($cidArr)) {
        $gcCidArr = array();
        $caijiCidArr = array();
        foreach ($cidArr as $cid) {
            //
            if ($cid >= 50000000) {
                $caijiCidArr[] = $cid;
            } else {
                $gcCidArr[] = $cid;
            }
            $province = 0;
            //供应商
            if (is_array($gcCidArr) && !empty($gcCidArr)) {
                $gcArr = $gcdb->select('gc_company', array('cid', 'province'), array('cid' => $gcCidArr));
                foreach($gcArr as $comArr) {
                    if($comArr['province'] > 0) {
                        $province = $comArr['province'];break;
                    }
                }
                if ($province > 0) {
                    //修复工商数据错误的省份
                    if ($province != $resArr['province']) {
                        $db->update('cb_combusiness', array('province' => $province), array('cid' => $resArr['cid']));
                        //打队列
                        $rabbitmqObj->set('combusiness', 'cbupdate', $resArr['cid'], 'ssdb');
                    }
                    //修改其它企业错误的省份
                    $gcdb->update('gc_company', array('province' => $province), array('cid' => $gcCidArr));
                    if (is_array($caijiCidArr) && !empty($caijiCidArr)) {
                        $caijidb->update('gc_company', array('province' => $province), array('cid' => $caijiCidArr));
                    }
                }
            }
            //采集
            if ($province == 0) {
                //用工商信息省份信息 修改 企业省份信息
                if (is_array($gcCidArr) && !empty($gcCidArr)) {
                    $gcdb->update('gc_company', array('province' => $resArr['province']), array('cid' => $gcCidArr));
                }
                //采集
                if (is_array($caijiCidArr) && !empty($caijiCidArr)) {
                    $caijidb->update('gc_company', array('province' => $resArr['province']), array('cid' => $caijiCidArr));
                }
            }
            //清理缓存
            if (is_array($gcCidArr) && !empty($gcCidArr)) {
                foreach ($gcCidArr as $cid) {
                    //打队列
                    $rabbitmq108Obj->set('v3com', 'com', $cid, 'ssdb');
                }
            }
            if (is_array($caijiCidArr) && !empty($caijiCidArr)) {
                foreach ($caijiCidArr as $cid) {
                    //打队列
                    $rabbitmq108Obj->set('v3wwwcaiji', 'com', $cid, 'ssdb');
                }
            }
        }
    }

    //打入更新缓存队列
//    $rabbitmqObj->set('combusiness', 'cbupdate', $cid, 'ssdb');
}