<?php
/**
 * 更新工商数据 异步处理守护进程
 * 入队列数据范例 json_encode(array('id' => 1676743));  其中id为engine_com_search搜索表的id
 */
header("Content-type:text/html;charset=utf-8");
ini_set('display_errors', 'ON');
date_default_timezone_set('PRC');
error_reporting(E_ALL);

defined('ROOT_PATH') or define('ROOT_PATH', dirname(__FILE__));
//include(ROOT_PATH . '/../config/config.php');
include(ROOT_PATH . '/../lib/medoo.php');
include(ROOT_PATH . '/../lib/Curl.php');
include(ROOT_PATH . '/../lib/RabbitMQ.php' );

$env = isset($argv[1]) ? $argv[1] : 'dev';
$type = isset($argv[2]) ? $argv[2] : 'common';

if($env == 'dev') {
    include(ROOT_PATH . '/../config/config_local.php');
}elseif($env == 'test'){
    include(ROOT_PATH . '/../config/config_test.php');
} elseif($env == 'pro') {
    include(ROOT_PATH . '/../config/config.php');
}else{
    die('config');
}
include(ROOT_PATH . '/../config/cate.php');


$db             = new medoo($dbConfig);
$dbcom          = new medoo($dbcomConfig);
$curlObj        = new Curl();
$rabbitmqObj    = new RabbitMQ($rabbitmqConfig);

$agent = array(
    "Dalvik/2.1.0 (Linux; U; Android 5.1.1; Nexus 7 Build/LMY47V)",
    "Dalvik/2.1.0 (Linux; U; Android 2.3.6; zh-cn; GT-S5660 Build/GINGERBREAD) AppleWebKit/533.1)",
    "Dalvik/2.1.0 (Linux; U; Android 2.4.5; zh-cn; GT-S5660 Build/GINGERBREAD) AppleWebKit/533.1 )",
    "Dalvik/2.1.0 (Linux; U; Android 2.5.7; zh-cn; GT-S5660 Build/GINGERBREAD) AppleWebKit/533.1)",
    "Dalvik/2.1.0 (Linux; U; Android 2.6.9; zh-cn; GT-S5660 Build/GINGERBREAD) AppleWebKit/533.1)",
    "Dalvik/2.1.0 (Linux; U; Android 2.7.3; zh-cn; GT-S5660 Build/GINGERBREAD) AppleWebKit/533.1)",
    "Dalvik/2.1.0 (Linux; U; Android 2.8.1; zh-cn; GT-S5660 Build/GINGERBREAD) AppleWebKit/533.1)",
    "Dalvik/2.1.0 (Linux; U; Android 2.9.6; zh-cn; GT-S5660 Build/GINGERBREAD) AppleWebKit/533.1)",
    "Dalvik/2.1.0 (Linux; U; Android 3.3.6; zh-cn; GT-S5660 Build/GINGERBREAD) AppleWebKit/533.1)",
    "Dalvik/2.1.0 (Linux; U; Android 4.3.8; zh-cn; GT-S5660 Build/GINGERBREAD) AppleWebKit/533.1)",
    "Dalvik/2.1.0 (Linux; U; Android 5.0.1; zh-cn; GT-S5660 Build/GINGERBREAD) AppleWebKit/533.1)",
    "Dalvik/2.1.0 (iPhone; CPU iPhone OS 5_1 like Mac OS X) AppleWebKit/534.46 (KHTML, like Gecko) Mobile/9B176 MicroMessenger/4.3.2)",
    "Dalvik/2.1.0 (iPhone; CPU iPhone OS 5_1 like Mac OS X) AppleWebKit/534.46 (KHTML, like Gecko) Mobile/9B176 MicroMessenger/4.1.2)",
    "Dalvik/2.1.0 (iPhone; CPU iPhone OS 5_1 like Mac OS X) AppleWebKit/534.46 (KHTML, like Gecko) Mobile/9B176 MicroMessenger/4.2.2)",
    "Dalvik/2.1.0 (iPhone; CPU iPhone OS 5_1 like Mac OS X) AppleWebKit/534.46 (KHTML, like Gecko) Mobile/9B176 MicroMessenger/4.0.2)",
    "Dalvik/2.1.0 (iPhone; CPU iPhone OS 5_1 like Mac OS X) AppleWebKit/534.46 (KHTML, like Gecko) Mobile/9B176 MicroMessenger/5.0.2)",
    "Dalvik/2.1.0 (iPhone; CPU iPhone OS 5_1 like Mac OS X) AppleWebKit/534.46 (KHTML, like Gecko) Mobile/9B176 MicroMessenger/5.1.2)",
    "Dalvik/2.1.0 (iPhone; CPU iPhone OS 5_1 like Mac OS X) AppleWebKit/534.46 (KHTML, like Gecko) Mobile/9B176 MicroMessenger/5.2.2)",
    "Dalvik/2.1.0 (iPhone; CPU iPhone OS 5_1 like Mac OS X) AppleWebKit/534.46 (KHTML, like Gecko) Mobile/9B176 MicroMessenger/5.3.2)",
    "Dalvik/2.1.0 (iPhone; CPU iPhone OS 5_1 like Mac OS X) AppleWebKit/534.46 (KHTML, like Gecko) Mobile/9B176 MicroMessenger/5.3.2)",
    "Dalvik/2.1.0 (iPhone; CPU iPhone OS 5_1 like Mac OS X) AppleWebKit/534.46 (KHTML, like Gecko) Mobile/9B176 MicroMessenger/5.5.2)",
);


//$resArr = getNewData('6bc7e7ccdb755391651316a0227c059b');
//print_r($resArr);
//die();
$sid = '';

while(true) {

    //队列取值
    if($type=='common'){
        $rs = $rabbitmqObj->get('combusiness_update_ssdb');
        $resArr = json_decode($rs, true);echo $resArr['cid'],'|';
    } else {
        $rs = $rabbitmqObj->get('combusiness_importupdate_ssdb');
        $resArr = json_decode($rs, true);
    }

    //测试
//    $resArr['cid'] = 1676743;//三全28524
//    $resArr['cid'] = 2141959;//恒大 包头
//    $resArr['cid'] = 159913;//石家庄市深华建筑有限公司
//    print_r($resArr);
    //主要更新程序
    $i = false;
    if (isset($resArr['cid']) && ($resArr['cid'] >0)) {
        $arr = $dbcom->get('cb_combusiness', '*', array('cid' => $resArr['cid'], 'LIMIT' => 1));
        if (is_array($arr) && !empty($arr)) {
            $i = update($arr);

            //打入更新缓存队列
            $rabbitmqObj->set('combusiness', 'cbupdate', $arr['cid'], 'ssdb');
        }else{
            //不再企业搜索表中的数据  入队列采集数据
        }
    } else {
//        sleep(3);
        unset($db);
        unset($dbcom);
        unset($rabbitmqObj);
        $db    = new medoo($dbConfig);
        $dbcom = new medoo($dbcomConfig);
        $rabbitmqObj    = new RabbitMQ($rabbitmqConfig);
    }

    //错误处理
    getError();
    //清理数据
    $db->clear();
    $dbcom->clear();
}
//获取错误
function getError()
{
    global $db, $dbcom, $dbConfig, $dbcomConfig;
    $error = $db->error();
    if($error[1] > 0) {
        print_r($error);
        if($error[1]==2006){
            $db             = new medoo($dbConfig);
            $dbcom          = new medoo($dbcomConfig);
        }
    }
    $error = $dbcom->error();
    if($error[1] > 0) {
        print_r($error);
        if($error[1]==2006){
            $db             = new medoo($dbConfig);
            $dbcom          = new medoo($dbcomConfig);
        }
    }
}

//更新程序
function update($arr)
{
    global $db, $dbcom, $type;
    $resArr = $db->get('company', '*', array('comname' => $arr['comname'], 'LIMIT' => 1));
    $unique = $resArr['unique_id'];
    $comArr = getDetail($unique, $resArr['id']);
    //如果数据不存在，委托更新
    if(!is_array($comArr) || empty($comArr) || !isset($comArr['Company']['Name'])) {
        return 0;
    }
    if(($arr['comname'] != $comArr['Company']['Name'])) {
        //企业更名情况 修改搜索表
        echo "更名：{$arr['comname']}---{$comArr['Company']['Name']}";
        $db->update('company', array('comname' => $comArr['Company']['Name']), array('comname' => $arr['comname'], 'LIMIT' => 1));
    }
//    print_r($arr);print_r($resArr);
    //更新详情表
    updateDetail($arr, $comArr);
    getError();

    //更新副表
    updateInfoNum($arr, $comArr);

    //更新搜索表
    //修改地区错误
    $update = array(
        'province_store' => $comArr['Company']['Area']['Province'],
        'city'           => $comArr['Company']['Area']['City'],
        'county'         => $comArr['Company']['Area']['County'],
    );
    if(empty($resArr['province'])){
        $update['province'] = province($comArr['Company']['Area']['Province']);
    }
    $db->update('company', $update, array('id' => $resArr['id']));

    //添加更新年报表
    if($comArr['CountInfo']['AnnualReportCount'] > 0){
        updateReport($arr, $unique);
    }
    //添加更新对外投资表
    if($comArr['CountInfo']['InvesterCount'] > 0) {
//        updateInvestment($arr);   //暂时无法更新
    }
    //添加更新商标表
    if($comArr['CountInfo']['SAICCount'] > 0){
//        updateTrademark($arr);    //暂时无法更新
    }
    //添加更新诉讼表
    if($comArr['CountInfo']['ZhiXingCount'] > 0){
//        updateZhixing($arr);
    }
    //添加更新失信表
    if($comArr['CountInfo']['ShiXinCount'] > 0){
//        updateShixin($arr);
    }
    //更新投资表
    //updateTouzhi($arr['cid'], $resArr['unique_id']);  //暂时无法更新

    //更新时间大于一周  触发更新企查查
    if($type == 'common' && time() - strtotime($comArr['UpdateTime']) > 7*86400){
        //触发企查查更新
        $url = "http://app.qichacha.com/enterprises/new/newUpdateData?province={$resArr['province']}&unique={$unique}&user=";
        $temp = getHtml($url);
    }
}

//更新详情表
function updateDetail($arr, $comArr)
{
    global $dbcom;
    $resArr = $dbcom->get('cb_combusiness', '*', array('cid' => $arr['cid']));
    if(is_array($resArr) && !empty($resArr)) {
        //更新主表信息
        $data = $comArr['Company'];
        $insert = array(
            'comname'       => $data['Name'],
            'regno'         => $data['No'],
            'uniqueno'      => unique($data['Name'], $data['No']),
            'scope'         => $data['Scope'],
            'state'         => $data['Status'],
            'comtype'       => $data['EconKind'],
            'regcapital'    => regcap($data['RegistCapi']),
            'businessstart' => strtotime($data['TermStart']),
            'businessend'   => strtotime($data['TeamEnd']),
            'checkdate'     => strtotime($data['CheckDate']),
            'regagency'     => $data['BelongOrg'],//工商局
            'legal'         => $data['OperName'],
            'startdate'     => strtotime($data['StartDate']),
            'enddate'       => strtotime($data['EndDate']),
//            'cate1'         => 0,
//            'cate2'         => 0,
            'uptime'        => strtotime($data['UpdatedDate']),
//            'areaid'        => getAreaId($data['No']),
            'intstate'      => getState($data['Status']),
            'intcomtype'    => getComType($data['EconKind']),
            'RegistCapi'    => $data['RegistCapi'],
        );
        $insert = area($insert, $data);
        //省 市 区处理
//        if($insert['areaid']<=0 && isset($data['Area']['City']) && !empty($data['Area']['City'])){
//            $insert['areaid'] = findAreaByName($data['Area']['City']);
//        }
//        if($insert['areaid'] > 0) {
//            $insert['province'] = intval($insert['areaid']/10000)*10000;
//            $temp = substr($insert['areaid'], 0, 2);
//            if (in_array($temp, array(11, 12, 31, 50))) {
//                $insert['city'] = intval($temp . '0100');
//            } else {
//                $insert['city'] = intval($insert['areaid']/100)*100;
//            }
//            $insert['zone'] = $insert['areaid'];
//        }
        //分类处理
        if(is_array($data['Industry']) && !empty($data['Industry'])) {
            global $qccCate1, $qccCate2;
            $insert['cate1'] = $qccCate1[$data['Industry']['IndustryCode']];
            $insert['cate2'] = $qccCate2[$data['Industry']['SubIndustryCode']];
        }
        //如果数据有不同，更改
        $update = array();
        foreach($insert as $key => $value){
            if($arr[$key] != $value) {
                $update[$key] = $value;
            }
        }
        if(is_array($update) && !empty($update)){
            $dbcom->update('cb_combusiness', $update, array('cid' => $arr['cid']));
//            print_r($dbcom->error());
//            print_r($update);die();
        }

        //更新变更记录
        $ChangeRecords = $comArr['Company']['ChangeRecords'];
        if(is_array($ChangeRecords) && !empty($ChangeRecords)){
            $table = "cb_changelog";
            foreach($ChangeRecords as $value){
                $title = $value['ProjectName'];
                $time = strtotime($value['ChangeDate']);
                $temp = $dbcom->get($table, '*', array('AND' => array('cid' => $arr['cid'], 'infoname' => $title, 'uptime' => $time), 'LIMIT' => 1));
                if(is_array($temp) && !empty($temp)) {//数据存在 修改
                    $update = array();
                    ($temp['infoname'] != $value['ProjectName']) && $update['infoname'] = $value['ProjectName'];
                    ($temp['oldvalue'] != $value['BeforeContent']) && $update['oldvalue'] = $value['BeforeContent'];
                    ($temp['newvalue'] != $value['AfterContent']) && $update['newvalue'] = $value['AfterContent'];
                    (isset($temp['ChangeDate']) && $temp['ChangeDate'] != $time) && $update['uptime'] = $time;
                    if(is_array($update) && !empty($update)){
                        $dbcom->update($table, $update, array('id' => $temp['id']));
                    }
                }else{//数据不存在 添加
                    $insert = array(
                        'cid'      => $arr['cid'],
                        'infoname' => $value['ProjectName'],
                        'oldvalue' => $value['BeforeContent'],
                        'newvalue' => $value['AfterContent'],
                        'uptime'   => $time,
                    );
                    $dbcom->insert($table, $insert);
                }
            }
        }
        //更新员工记录
        $Employees = $comArr['Company']['Employees'];
        if(is_array($Employees) && !empty($Employees)) {
            $table = "cb_employee";
            foreach($Employees as $value){

                $temp = $dbcom->get($table, '*', array('AND' => array('cid' => $arr['cid'], 'name' => $value['Name'], 'job' => $value['Job']), 'LIMIT' => 1));
                if(is_array($temp) && !empty($temp)) {//数据存在 修改
                    $update = array();
                    ($temp['name'] != $value['Name']) && $update['name'] = $value['Name'];
                    ($temp['job'] != $value['Job']) && $update['job'] = $value['Job'];
                    (!isset($value['CerNo']) || empty($value['CerNo'])) && $value['CerNo'] = '无';
                    ($temp['certno'] != $value['CerNo']) && $update['certno'] = $value['CerNo'];
                    if(is_array($update) && !empty($update)){
                        $dbcom->update($table, $update, array('id' => $temp['id']));
                    }
                }else {//数据不存在 添加
                    $insert = array(
                        'cid'       => $arr['cid'],
                        'name'      => $value['Name'],
                        'job'       => $value['Job'],
                        'certno'    => isset($value['CerNo']) && !empty($value['CerNo']) ? $value['CerNo'] : '无',
                    );
                    $dbcom->insert($table, $insert);
                }
            }
        }
        //更新合伙人
        $Partners = $comArr['Company']['Partners'];
        if(is_array($Partners) && !empty($Partners)) {
            $table = "cb_partner";
            foreach($Partners as $value){
                $temp = $dbcom->get($table, '*', array('AND' => array('cid' => $arr['cid'], 'stockholder' => $value['StockName']), 'LIMIT' => 1));
                $insert = array(
                    'stockholder' => $value['StockName'],
                    'stocktype' => $value['StockType'],
                    'stockpercent' => $value['StockPercent'],
                    'identifyname' => $value['IdentifyType'],
                    'identifyno' => $value['IdentifyNo'],
                    'shouldcapi' => $value['ShouldCapi'],
                    'shoulddate' => strtotime($value['ShoudDate']),
                    'shouldtype' => $value['InvestType'],
                    'realtype' => $value['InvestName'],
                    'realcapi' => $value['RealCapi'],
                    'realdate' => strtotime($value['CapiDate']),
                );
                if(is_array($temp) && !empty($temp)) {//数据存在 修改
                    $update = array();
                    foreach($insert as $key => $value){
                        if($temp[$key] != $value){
                            $update[$key] = $value;
                        }
                    }
                    if(is_array($update) && !empty($update)){
                        $dbcom->update($table, $update, array('id' => $temp['id']));
                    }
                }else {//数据不存在 添加
                    $insert['cid'] = $arr['cid'];
                    $dbcom->insert($table, $insert);
                }
            }
        }
        //更新联系人
        $ContactInfo = isset($comArr['Company']['ContactInfo']) ? $comArr['Company']['ContactInfo'] : array();
        if(is_array($ContactInfo) && !empty($ContactInfo)) {
            $table = 'cb_com_contact';
            $temp = $dbcom->get('cb_com_contact', '*', array('cid' => $arr['cid'], 'LIMIT' => 1));
            if(is_array($temp) && !empty($temp)) {
                $update = array();
                if(isset($ContactInfo['WebSite']) && !empty($ContactInfo['WebSite'])){
                    $update['WebSite'] = json_encode($ContactInfo['WebSite']);
                }
                if(isset($ContactInfo['PhoneNumber']) && !empty($ContactInfo['PhoneNumber'])){
                    $update['PhoneNumber'] = $ContactInfo['PhoneNumber'];
                }
                if(isset($ContactInfo['Email']) && !empty($ContactInfo['Email'])){
                    $update['Email'] = $ContactInfo['Email'];
                }
                if(is_array($update) && !empty($update)) {
                    $dbcom->update($table, $update, array('id' => $temp['id']));
                }
            }else{
                $insert = array(
                    'cid'           => $arr['cid'],
                    'WebSite'       => isset($ContactInfo['WebSite']) ? json_encode($ContactInfo['WebSite'], JSON_UNESCAPED_UNICODE) : json_encode(array()),
                    'PhoneNumber'   => isset($ContactInfo['PhoneNumber']) ? $ContactInfo['PhoneNumber'] : '',
                    'Email'         => isset($ContactInfo['Email']) ? $ContactInfo['Email'] : '',
                );
                $dbcom->insert($table, $insert);
            }
        }
    }
}

//更新副表
function updateInfoNum($arr, $comArr)
{
    global $dbcom;
    $update = array();
    $table = 'cb_combusiness_info';
    $temp = $dbcom->get($table, '*', array('cid' => $arr['cid']));
    $insert = array(
        'investernum' => $comArr['CountInfo']['InvesterCount'],
        'shixinnum' => $comArr['CountInfo']['ShiXinCount'],
        'zhixingnum' => $comArr['CountInfo']['ZhiXingCount'],
        'saiccnum' => $comArr['CountInfo']['SAICCount'],
        'reportnum' => $comArr['CountInfo']['AnnualReportCount'],
    );
    if(is_array($temp) && !empty($temp)){
        $dbcom->update($table, $insert, array('cid' => $temp['cid']));
    }else{
        $insert['cid'] = $arr['cid'];
        $dbcom->insert($table, $insert);
    }
}

//添加更新年报
function updateReport($arr, $unique)
{
    global $dbcom, $sid;
    $url = "http://139.129.76.139/enterprises/w1/getAnnualReportListById?id={$sid}";
    $resArr = getHtml($url);
    if (is_array($resArr) && !empty($resArr)) {
        foreach ($resArr as $res) {
            $url = "http://139.129.76.139/enterprises/w1/getAnnualReportItemById?id={$sid}&year={$res['Year']}";
            $value = getHtml($url);

            if(is_array($value) && !empty($value)){
                $table = 'cb_com_annualreport';
                $temp = $dbcom->get($table, '*', array('AND' => array('cid' => $arr['cid'], 'No' => $value['No']), 'LIMIT' => 1));
                $insert = array(
                    'No'                        => $value['No'],
                    'Year'                      => $value['Year'],
                    'Remarks'                   => $value['Remarks'],
                    'HasDetailInfo'             => $value['HasDetailInfo'] ? 1 : 0,
                    'PublishDate'               => strtotime($value['PublishDate']),
                    'BasicInfoData'             => isset($value['BasicInfoData']) ? json_encode($value['BasicInfoData'], JSON_UNESCAPED_UNICODE) : json_encode(array()),
                    'AssetsData'                => isset($value['AssetsData']) ? json_encode($value['AssetsData'], JSON_UNESCAPED_UNICODE) : array(),
                    'ChangeList'                => isset($value['ChangeList']) ? json_encode($value['ChangeList'], JSON_UNESCAPED_UNICODE) : json_encode(array()),
                    'InvestInfoList'            => isset($value['InvestInfoList']) ? json_encode($value['InvestInfoList'], JSON_UNESCAPED_UNICODE) : json_encode(array()),
                    'PartnerList'               => isset($value['PartnerList']) ? json_encode($value['PartnerList'], JSON_UNESCAPED_UNICODE) : json_encode(array()),
                    'ProvideAssuranceList'      => isset($value['ProvideAssuranceList']) ? json_encode($value['ProvideAssuranceList'], JSON_UNESCAPED_UNICODE) : json_encode(array()),
                    'StockChangeList'           => isset($value['StockChangeList']) ? json_encode($value['StockChangeList'], JSON_UNESCAPED_UNICODE) : json_encode(array()),
                    'WebSiteList'               => isset($value['WebSiteList']) ? json_encode($value['WebSiteList'], JSON_UNESCAPED_UNICODE) : json_encode(array()),
                    'AdministrationLicenseList' => isset($value['AdministrationLicenseList']) ? json_encode($value['AdministrationLicenseList'], JSON_UNESCAPED_UNICODE) : '',
                );
                $t = $value['HasDetailInfo'] ? 1 : 0;
                ($temp['HasDetailInfo'] != $t) && $update['HasDetailInfo'] = $t;
                $t = strtotime($value['PublishDate']);
                ($temp['PublishDate'] != $t) && $update['PublishDate'] = $t;

                if(is_array($temp) && !empty($temp)){//存在修改
                    $dbcom->update($table, $insert, array('id' => $temp['id']));
                }else{//添加
                    $insert['cid'] = $arr['cid'];
                    $dbcom->insert($table, $insert);
                }
            }
        }
    }
}
//添加更新投资表
function updateInvestment($arr)
{
    global $dbcom;
    $page = 1;
    $max  = 0;
    while(true){
        $table = 'cb_com_investment';
        $name = $arr['comname'];
        $token = md5($name . '2c2c401f52b79fbc9740168d134b8954');
        $url = "http://app.qichacha.com/enterprises/new/a1/searchInvestment?name=".urlencode($name)."&province=&cityCode=&pageIndex={$page}&token={$token}";
        $data = getHtml($url);
        if(isset($data['Result']) && is_array($data['Result']) && !empty($data['Result'])){
            foreach($data['Result'] as $value){
                $temp = $dbcom->get($table, '*', array('AND' => array('cid' => $arr['cid'], 'Name' => $value['Name']), 'LIMIT' => 1));
                $update = array(
                    'KeyNo'       => $value['KeyNo'],
                    'Name'        => $value['Name'],
                    'No'          => $value['No'],
                    'BelongOrg'   => $value['BelongOrg'],
                    'OperName'    => $value['OperName'],
                    'StartDate'   => strtotime($value['StartDate']),
                    'EndDate'     => strtotime($value['EndDate']),
                    'Status'      => $value['Status'],
                    'Province'    => $value['Province'],
                    'UpdatedDate' => strtotime($value['UpdatedDate']),
                    'Area'        => json_encode($value['Area'], true),
                );
                if(is_array($temp) && !empty($temp)){//存在 更新
                    $dbcom->update($table, $update, array('id' => $temp['id']));
                }else{//不存在 添加
                    $insert = $update;
                    $insert['cid'] = $arr['cid'];
                    $dbcom->insert($table, $insert);
                }
            }
        }
        $page++;
        $max = ceil($data['Paging']['TotalRecords']/$data['Paging']['PageSize']);
        if($page>$max){
            break;
        }
    }
}
//商标信息
function updateTrademark($arr)
{
    global $dbcom;
    $page = 1;
    $max  = 0;
    while(true) {
        $table = 'cb_com_trademark';
        $name = $arr['comname'];
        $token = md5($name . '2c2c401f52b79fbc9740168d134b8954');
        $url = "http://app.qichacha.com/enterprises/new/a1/trademarkList?name=".urlencode($name)."&pageIndex={$page}&token={$token}";
        $data = getHtml($url);
        //处理数据
        if(is_array($data['Items']) && !empty($data['Items'])){
            foreach($data['Items'] as $value){
                $temp = $dbcom->get($table, '*', array('AND' => array('cid' => $arr['cid'], 'RegNo' => $value['RegNo']), 'LIMIT' => 1));
                $update = array(
                    'ID'            => $value['ID'],
                    'RegNo'         => $value['RegNo'],
                    'Name'          => $value['Name'],
                    'CategoryId'    => $value['CategoryId'],
                    'Category'      => $value['Category'],
                    'Person'        => $value['Person'],
                    'HasImage'      => $value['HasImage'] ? 1 : 0,
                    'Flow'          => $value['Flow'],
                    'ImageUrl'      => isset($value['ImageUrl']) ? $value['ImageUrl'] : '',
                    'FlowStatus'    => isset($value['FlowStatus']) ? $value['FlowStatus'] : '',
                );
                if(is_array($temp) && !empty($temp)){
                    $dbcom->update($table, $update, array('iid' => $temp['iid']));
                }else{
                    $insert = $update;
                    $insert['cid'] = $arr['cid'];
                    $dbcom->insert($table, $insert);
                }
            }
        }
        $page++;
        $max = ceil($data['TotalRecords']/$data['PageSize']);
        if($page>$max){
            break;
        }
    }
}
//诉讼
function updateZhixing($arr)
{
    global $dbcom;
    $page = 1;
    $max  = 0;
    while(true) {
        $table = 'cb_com_zhixing';
        $name = $arr['comname'];
        $token = md5($name . '2c2c401f52b79fbc9740168d134b8954');
        $url = "http://app.qichacha.com/enterprises/new/a1/searchZhixing?name=".urlencode($name)."&isExactlySame=true&province=&pageIndex={$page}&token={$token}&user=";
        $data = getHtml($url);
        //处理数据
        if(is_array($data['Result']) && !empty($data['Result'])){
            foreach($data['Result'] as $value){
                $temp = $dbcom->get($table, '*', array('AND' => array('cid' => $arr['cid'], 'Id' => $value['Id']), 'LIMIT' => 1));
                $update = array(
                    'Id'                => $value['Id'],
                    'Name'              => $value['Name'],
                    'Liandate'          => $value['Liandate'],
                    'Anno'              => $value['Anno'],
                    'Follows'           => $value['Follows'],
                    'Executegov'        => $value['Executegov'],
                    'Biaodi'            => $value['Biaodi'],
                    'Status'            => $value['Status'],
                    'Createdate'        => strtotime($value['Createdate']),
                    'Updatedate'        => strtotime($value['Updatedate']),
                    'Sourceid'          => $value['Sourceid'],
                    'Partycardnum'      => $value['Partycardnum'],
                    'JudicialOpinionId' => $value['JudicialOpinionId'],
                );
                if(is_array($temp) && !empty($temp)){
                    $dbcom->update($table, $update, array('iid' => $temp['iid']));
                }else{
                    $insert = $update;
                    $insert['cid'] = $arr['cid'];
                    $i = $dbcom->insert($table, $insert);
//                    if($i<=0){
//                        print_r($dbcom->error());
//                    }
                }
            }
        }

        $page++;
        $max = ceil($data['Paging']['TotalRecords']/$data['Paging']['PageSize']);
        if($page>$max){
            break;
        }
    }
}
//失信信息
function updateShixin($arr)
{
    global $dbcom;
    $page = 1;
    $max  = 0;
    while(true) {
        $table = 'cb_com_shixin';
        $name = $arr['comname'];
        $token = md5($name . '2c2c401f52b79fbc9740168d134b8954');
        $url = "http://app.qichacha.com/enterprises/new/a1/searchShixin?name=".urlencode($name)."&isExactlySame=true&province=&pageIndex={$page}&token={$token}&user=";
        $data = getHtml($url);
        //处理数据
        if(is_array($data['Result']) && !empty($data['Result'])){
            foreach($data['Result'] as $value){
                $temp = $dbcom->get($table, '*', array('AND' => array('cid' => $arr['cid'], 'Id' => $value['Id']), 'LIMIT' => 1));
                $update = array(
                    'Id'                => $value['Id'],
                    'Sourceid'          => $value['Sourceid'],
                    'Uniqueno'          => $value['Uniqueno'],
                    'Name'              => $value['Name'],
                    'Liandate'          => $value['Liandate'],
                    'Anno'              => $value['Anno'],
                    'Orgno'             => $value['Orgno'],
                    'Ownername'         => $value['Ownername'],
                    'Executegov'        => $value['Executegov'],
                    'Province'          => $value['Province'],
                    'Executeunite'      => $value['Executeunite'],
                    'Yiwu'              => $value['Yiwu'],
                    'Executestatus'     => $value['Executestatus'],
                    'Actionremark'      => $value['Actionremark'],
                    'Publicdate'        => strtotime($value['Publicdate']),
                    'Follows'           => $value['Follows'],
                    'Age'               => $value['Age'],
                    'Sexy'              => $value['Sexy'],
                    'Createdate'        => strtotime($value['Createdate']),
                    'Updatedate'        => strtotime($value['Updatedate']),
                    'Executeno'         => $value['Executeno'],
                    'Performedpart'     => $value['Performedpart'],
                    'Unperformpart'     => $value['Unperformpart'],
                    'Isperson'          => $value['Isperson'],
                    'JudicialOpinionId' => $value['JudicialOpinionId'],
                );
                if(is_array($temp) && !empty($temp)){
                    $dbcom->update($table, $update, array('iid' => $temp['iid']));
                }else{
                    $insert = $update;
                    $insert['cid'] = $arr['cid'];
                    $dbcom->insert($table, $insert);
                }
            }
        }

        $page++;
        $max = ceil($data['Paging']['TotalRecords']/$data['Paging']['PageSize']);
        if($page>$max){
            break;
        }
    }
}

//获得投资
function updateTouzhi($cid, $unique)
{
    global $dbcom;
    $table = 'cb_com_touzi';
    $url = "http://app.qichacha.com/enterprises/html5/new/getPicMap?unique={$unique}&upstreamCount=1&downstreamCount=1";
    $data = getHtml($url);
    if (isset($data['Result']) && !empty($data['Result'])) {
        $nodeArr = isset($data['Result']['Nodes']) ? $data['Result']['Nodes'] : array();
        $linkArr = isset($data['Result']['Links']) ? $data['Result']['Links'] : array();
        $insert = array();
        if(is_array($nodeArr) && !empty($nodeArr)) {
            foreach($nodeArr as $k => $v){
                $temp = array(
                    'KeyNo'     => $v['KeyNo'],
                    'Name'      => $v['Name'],
                    'Category'  => $v['Category'],
                    'ShortName' => $v['ShortName'],
                );
                if($k>0){
                    $temp['Target'] = $linkArr[$k-1]['Target'];
                }
                $insert[] = $temp;
            }
        }
        if(is_array($insert) && !empty($insert)) {
            foreach($insert as $value) {
                $temp = $dbcom->get($table, '*', array('AND' => array('cid' => $cid, 'KeyNo' => $value['KeyNo']), 'LIMIT' => 1));
                if(is_array($temp) && !empty($temp)){
                    $dbcom->update($table, $value, array('id' => $temp['id']));
                }else{
                    $value['cid'] = $cid;
                    $i = $dbcom->insert($table, $value);
                    if($i<=0){
                        print_r($dbcom->error());
                    }
                }
            }
        }
    }
}

//获取新的详情
function getDetail($unique, $id)
{
    //旧方法
//    $token = md5($unique . '2c2c401f52b79fbc9740168d134b8954');
//    $urlStr = "http://app.qichacha.com/enterprises/new/a1/getData?unique={$unique}&token={$token}&user=";
//    return getHtml($urlStr);
    //新方法
    return getNewData($unique, $id);
}
//新方法获取
function getNewData($unique, $id)
{
    $url = "http://qichacha.com/firm_CN_{$unique}";
    $html = getHtmlStr($url);
    preg_match('/xmlHttp\.open\("GET", "(.*?)", true\)/', $html, $match);
//    print_r($match);
    $url = isset($match[1]) ? $match[1] : '';
    if (!empty($url)) {
        $url = getHtml($url);
        $str = str_replace("http://mob.qichacha.com/#/share/", '', $url);
        if (!empty($str)) {
            //记录id
            global $dbcom, $sid;
            $sid = $str;
            $dbcom->update('company', array('encodeid' => $str), array('id' => $id));
            $url = "http://139.129.76.139/enterprises/w1/getDataById?id={$str}";
            //echo $url;
            return getHtml($url);
        }
    }
    return array();
}
//获取html内容
function getHtml($url)
{
    global $curlObj, $agent;
    if (empty($url))
        return array();
    $ip = rand(1,255).'.'.rand(1,255).'.'.rand(1,255).'.'.rand(1,255);
    $ag = array_rand($agent);
    $header = array(
        "CLIENT-IP:{$ip}", "X-FORWARDED-FOR:{$ip}",
        "Referer:http://qichacha.com/",
        "User-Agent:{$ag}",
    );
    $curlObj->setUrl($url);
    $curlObj->setHttpHeader($header);
    $curlObj->setTimeout(30);
    $resStr = $curlObj->run();
    $resArr = !empty($resStr) ? json_decode($resStr, true) : array();
    if (isset($resArr['data'])) {
        return $resArr['data'];
    }else{
        return array();
    }
}
//获取html内容
function getHtmlStr($url)
{
    global $curlObj, $agent;
    if (empty($url))
        return array();
    $ip = rand(1,255).'.'.rand(1,255).'.'.rand(1,255).'.'.rand(1,255);
    $ag = array_rand($agent);
    $header = array(
        "CLIENT-IP:{$ip}", "X-FORWARDED-FOR:{$ip}",
        "Referer:http://qichacha.com/",
        "User-Agent:{$ag}",
    );
    $curlObj->setUrl($url);
    $curlObj->setHttpHeader($header);
    $curlObj->setTimeout(30);
    return $curlObj->run();
}

//转换注册资本
function regcap($regcapStr)
{
    $regcap = 0;
    $temp = str_replace(array(',', '，'), '', $regcapStr);
    if(strpos($temp, '万') != false) {
        $regcap = floatval($temp);
    } else {
        $regcap = floatval($temp)/10000;
    }
    return $regcap;
}

//自定义唯一unique
function unique($name, $reg_id)
{
    $salt = '48$%Yd&s4i';
    $str = md5($name.$reg_id.$salt);
    return substr($str, 3, 26);
}

/**
 * 功能描述 根据注册号 获取地区编号id
 */
function getAreaId($regno)
{
    if (empty($regno)) {
        return 0;
    }
    $code = substr($regno, 0, 6);
    if (!is_numeric($code) || !in_array(strlen($regno), array(13, 15))) {
        return 0;
    }
    $areaid = findAreaById($code);
    if ($areaid==0) {
        $code = intval(substr($code, 0, 4))*100;
        $areaid = findAreaById($code);
        if ($areaid==0) {
            $code = intval(substr($code, 0, 2))*10000;
            $areaid = findAreaById($code);
        }
    }
    return $areaid;
}
function findAreaById($areaid)
{
    global $dbcom;
    $resArr = $dbcom->get('area', '*', array('id' => $areaid));
    if (empty($resArr)) {
        return 0;
    } else {
        return $resArr['id'];
    }
}
function findAreaByName($name)
{
    global $dbcom;
    $resArr = $dbcom->get('area', '*', array('areaname' => $name));
    if (empty($resArr)) {
        return 0;
    } else {
        return $resArr['id'];
    }
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
        '撤销'   => 13,
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

function findAreaByNameParent($name='', $p = 0)
{
    global $db;

    if(empty($name))
        return 0;
    $resArr = $db->get('area', '*', array('AND' => array('areaname' => $name, 'parentid' => $p)));
    if (empty($resArr)) {
        return 0;
    } else {
        return $resArr['id'];
    }
}
function area($arr, $data)
{
    global $db;
    $areaArr = array(
        "CN" => 110000,
        "BJ" => 110000,
        "TJ" => 120000,
        "HB" => 130000,
        "SX" => 140000,
        "NMG" => 150000,
        "LN" => 210000,
        "JL" => 220000,
        "HLJ" => 230000,
        "SH" => 310000,
        "JS" => 320000,
        "ZJ" => 330000,
        "AH" => 340000,
        "FJ" => 350000,
        "JX" => 360000,
        "SD" => 370000,
        "HEN" => 410000,
        "HUB" => 420000,
        "HUN" => 430000,
        "GD" => 440000,
        "GX" => 450000,
        "HAIN" => 460000,
        "CQ" => 500000,
        "SC" => 510000,
        "GZ" => 520000,
        "YN" => 530000,
        "XZ" => 540000,
        "SAX" => 610000,
        "GS" => 620000,
        "QH" => 630000,
        "NX" => 640000,
        "XJ" => 650000
    );
    $comArr = $db->get('company', array('province', 'city', 'county'), array('comname' => $data['Name'], 'LIMIT' => 1));
    if(is_array($comArr) && empty($comArr)){
        $p = trim($comArr['province']);
        if(isset($areaArr[$p])){
            $arr['province'] = $areaArr[$p];
        }
    } else {
        if(isset($data['Area']) && !empty($data['Area'])) {
            $temp = findAreaByNameParent($data['Area']['Province'], 0);
            if($temp>0){
                $arr['province'] = $temp;
            }
        }
    }
    $arr['areaid'] = $arr['province'];
    //市
    if(!empty($comArr['city'])){
        $city = findAreaByNameParent($data['Area']['City'], $arr['province']);
        if($city>0){
            if($arr['province'] <= 0) {//防止部分省份有问题，导致更新出错
                $arr['province'] = intval($city/10000)*10000;
            }
            $arr['city'] = $city;
            $arr['areaid'] = $city;
            //区
            if(!empty($comArr['county'])){
                $aid = findAreaByNameParent($data['Area']['County'], $city);
                if($aid>0){
                    $arr['zone'] = $aid;
                    $arr['areaid'] = $aid;
                }
            }
        }
    }
    return $arr;
}