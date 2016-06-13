<?php
/**
 * 处理新采集的数据信息
 */
header("Content-type:text/html;charset=utf-8");
defined('ROOT_PATH') or define('ROOT_PATH', dirname(__FILE__));
ini_set ('memory_limit', '128M');

//include(ROOT_PATH . '/../config/config.php');
include(ROOT_PATH . '/../lib/medoo.php' );
include(ROOT_PATH . '/../lib/RabbitMQ.php' );
include(ROOT_PATH . '/../config/cate.php');

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

$temp = $db->get('num', '*', array('id' => 10));
$cid = intval($temp['num']);
while(true){

    $resArr = $db->get('cb_combusiness', '*', array('cid[>]' => $cid, 'LIMIT' => 1));
    if(!is_array($resArr) || empty($resArr)) {
        sleep(3);
    }else{
        $cid = $resArr['cid'];
    }

    try {
        //处理地区
        doArea($resArr);
        //处理分类
        doCate($resArr);
        //处理关键
        doCbcid($resArr);
        //处理整形话
        doState($resArr);
    } catch (Exception $e) {
        unset($db);
        unset($gcdb);
        unset($caijidb);
        $db = new medoo($dbcomConfig);
        $gcdb = new medoo($dbgccominfo);
        $caijidb = new medoo($dbcaijicominfo);
    }

    //修改数目
    $db->update('num', array('num' => $cid), array('id' => 10));
    //打入更新缓存队列
    $rabbitmqObj->set('combusiness', 'cbupdate', $cid, 'ssdb');

    $db->clear();
}

function doArea($resArr)
{
    global $db;

//    echo $areaid;
    $areaid = $resArr['areaid'];
    if($areaid = 0) {
        //通过注册号 获取地区编号
        if(mb_strlen($resArr['regno'], 'utf-8') == 13 || mb_strlen($resArr['regno'], 'utf-8') == 15){
            $update['areaid'] = substr($resArr['regno'], 0, 6);
            $areaid = $update['areaid'];
        }
        //通过地址 获取地区编号
        if($areaid<0){
            $areaid = getZoneByAddress($resArr['address'], $resArr['regagency']);
        }
    }
    if($areaid <= 0 )
        return;

    $update['province'] = findArea(intval($areaid/10000)*10000);
    $temp = substr($areaid, 0, 2);
    $city = 0;
    if (in_array($temp, array(11, 12, 31, 50))) {
        $city = intval($temp . '0100');
    } else {
        $city = intval($areaid/100)*100;
    }
    $update['city'] = findArea($city);
    $update['zone'] = findArea($areaid);
    $db->update('cb_combusiness', $update, array('cid' => $resArr['cid']));
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
    global $db;
    $resArr = $db->get('area', '*', array('id' => $areaid));
    if (empty($resArr)) {
        return 0;
    } else {
        return $resArr['id'];
    }
}
function findAreaByName($name)
{
    global $db;
    $resArr = $db->get('area', '*', array('areaname' => $name));
    if (empty($resArr)) {
        return 0;
    } else {
        return $resArr['id'];
    }
}
function getZoneByAddress($address, $regagency)
{
    $pArr = array(
        '/[^省](.*?市)/',
        '/(.*?省)/',
        '/[^市](.*?区|.*?县)/',
    );
    foreach($pArr as $p){
        preg_match($p, $address, $match);
        if(empty($match)) {
            //匹配工商局地址
            preg_match($p, $regagency, $match);
            $zone = isset($match[0]) ? $match[0] : '';
        }else{
            $zone = $match[0];
        }
        if (!empty($zone)) {
            $areaid = findAreaByName($zone);
            if($areaid>0)
                return $areaid;
        }
    }
    return 0;
}

//处理分类
function doCate($resArr)
{
    global $db, $cate2;
    if (!empty($resArr['scope'])) {
        $match = array();
        foreach ($cate2 as $key => $value) {
            $keyword = $value['keyword'];
            if(!empty($keyword)) {
                $keywordArr = explode('|', $value['keyword']);
                if(is_array($keywordArr) && !empty($keywordArr)) {
                    foreach($keywordArr as $word) {
                        if (strpos($resArr['scope'], $word) !== false) {
                            $match[] = $key;
                        }
                    }
                }
            }
        }
        if(is_array($match) && !empty($match)) {
            $cate1 = array();
            foreach($match as $value){
                $cate1[] = intval($value/100) * 100;
            }
            $update = array(
                'cate1' => implode(',', $cate1),
                'cate2' => implode(',', $match),
            );
            $db->update('cb_combusiness', $update, array('cid' => $resArr['cid']));
        }
    }
}

//企业关联
function doCbcid($resArr)
{
    global $db, $gcdb, $caijidb;

    //查询工厂库 获得企业cid 限定企业状态 最多取3个
    $comArr = $gcdb->select('gc_company', array('cid'), array('AND' => array('comname' => $resArr['comname'], 'state[>]' => 0), 'limit' => '3'));
    $cidArr = array();
    if(is_array($comArr) && !empty($comArr)) {
        foreach($comArr as $value){
            $cidArr[] = $value['cid'];
        }
        if(!empty($cidArr)){
            //获取最早通过营业执照审核的企业
            $liceArr = $gcdb->get('gc_buslicense', array('cid'), array('cid' => $cidArr, 'order' => 'addtime asc', 'limit' => 1));
            //如果有，把该企业cid放到首页
            if(is_array($liceArr) && $liceArr['cid']>0){
                array_unshift($cidArr, $liceArr['cid']);    //开头添加重要联系企业
                $cidArr = array_unique($cidArr);            //去重
            }
            //如果这几家商铺province 不对，修正。
            if ($resArr['province'] > 0) {
                $gcdb->update('gc_company', array('province' => $resArr['province']), array('AND' => array('cid' => $cidArr, 'province[!]' => $resArr['province'])));
            }
        }
    }

    //如果不足三个 从黄页中寻找
    if (count($cidArr) < 3){
        //查询采集库 获得企业cid 企业状态正常 username不为空有商铺地址 限制数目
        $num = 3 - count($cidArr);
        $cajiArr = $caijidb->select('gc_company', array('cid'),
            array('AND' => array('comname' => $resArr['comname'], 'state[>]' => 0, 'username[!]' => '', 'limit' => $num))
        );
        if(is_array($cajiArr) && !empty($cajiArr)) {
            $caijiCidArr = array();
            foreach ($cajiArr as $value) {
                $cidArr[]    = $value['cid'];
                $caijiCidArr = $value['cid'];
            }
            //如果这几家商铺province 不对，修正。
            if (is_array($caijiCidArr) && !empty($caijiCidArr) && $resArr['province'] > 0) {
                $caijidb->update('gc_company', array('province' => $resArr['province']), array('AND' => array('cid' => $caijiCidArr, 'province[!]' => $resArr['province'])));
            }
        }
    }

    //修改关联字段
    if(is_array($cidArr) && !empty($cidArr)) {
        $cidStr = implode(',', $cidArr);
        $db->update('cb_combusiness', array('gccid' => $cidStr), array('cid' => $resArr['cid']));
    }
}

//处理整形话
function doState($resArr)
{
    global $db;
    $update = array();
    //处理企业状态
    $update['intstate'] = getState($resArr['state']);

    //处理企业类型
    $update['intcomtype'] = getComType($resArr['comtype']);

    //处理注册资本
    $update['regcapital'] = regcap($resArr['RegistCapi']);

    $db->update('cb_combusiness', $update, array('cid' => $resArr['cid']));
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