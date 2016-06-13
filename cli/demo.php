<?php
/**
 * Desc: 登陆
 */

header("Content-type:text/html;charset=utf-8");
ini_set('display_errors', 'ON');
error_reporting(E_ALL);
date_default_timezone_set('PRC');
defined('ROOT_PATH') or define('ROOT_PATH', dirname(__FILE__));
require_once(ROOT_PATH . '/../lib/Curl/Curl.php');
require_once(ROOT_PATH . '/../lib/CheckCode.php');
require_once(ROOT_PATH . '/../lib/medoo.php');
require_once(ROOT_PATH . '/../config/config_local.php');
require_once('qichacha.php');

//$res = qichacha::login();
////var_dump($res);
//
////获取企业详情
//$header = json_decode($res['responseheader'],true);
//$cookie = $header['Set-Cookie'];
$curl = new \Curl\Curl();
//$curl->setOpt(CURLOPT_COOKIE, $cookie);
$curl->get('http://www.qichacha.com/firm_CN_b52197c0f716b4f770596548d0156513');

if ($curl->error) {
    echo 'Error: ' . $curl->errorCode . ': ' . $curl->errorMessage;
} else {
    var_dump($curl->response);
    file_put_contents('test.html', $curl->response);
}

exit();


$dbcom = new medoo(array(
    'database_type' => 'mysql',
    'database_name' => 'combusiness',
    'server' => '192.168.8.189',
    'username' => 'gongchang',
    'password' => 'gongchang123',
    'port' => 3306,
    'charset' => 'utf8',
    'option' => array(PDO::ATTR_CASE => PDO::CASE_NATURAL, PDO::ATTR_PERSISTENT => true)
));
$today = strtotime(date('Y-m-d'));

//先取一条可用用户 条件 状态可用,用户类别 企查查  没有登录过  最后登录时间不是当天
$userinfo = $dbcom->get("cj_user", array('uid', 'username', 'password', 'usertype', 'state', 'nums', 'logintime', 'requestheader', 'responseheader'), array("AND" => ['nums[<]' => 10, 'state' => 1, 'usertype' => 1, 'logintime[!]' => $today], "ORDER" => "nums ASC"));

if ($userinfo) {
    if (!empty($userinfo['responseheader'])) {
        return $userinfo;
    }
} else {
    exit();
}

var_dump($dbcom->log());
var_dump($userinfo);

$curl = new \Curl\Curl();
$curl->setUserAgent('Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_5) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/45.0.2454.85 Safari/537.36 QQBrowser/3.9.3943.400');

//获取验证码
$curl->download('http://www.qichacha.com/index_validateCode?verifyName=verifyLogin', 'tmp/' . $userinfo['username'] . '.jpg');
$code = CheckCode::dama('tmp/' . $userinfo['username'] . '.jpg');
echo $code;

//var_dump($curl->responseHeaders);
//exit();

//登陆
$curl->setCookie('PHPSESSID', $curl->getCookie('PHPSESSID'));
$curl->setCookie('SERVERID', $curl->getCookie('SERVERID'));
$curl->setCookie('think_language', $curl->getCookie('think_language'));
$curl->post('http://www.qichacha.com/global_user_loginaction', array(
    'name' => $userinfo['username'],
    'pswd' => $userinfo['password'],
    'verifyLogin' => $code,
));

if ($curl->error) {
    echo 'Error: ' . $curl->errorCode . ': ' . $curl->errorMessage;
} else {

    //$arr = $curl->responseHeaders->toArray();
    $res = json_decode($curl->response, true);
    $code = $res['success'];
    if (!$code) {
        echo 0;
    }
    echo 1;
}

//$res = $dbcom->update('cj_user', array('nums[+]' => 1, 'logintime' => $today), array("AND" => ['uid' => $userinfo['uid']]));
$res = $dbcom->update('cj_user', array('nums[+]' => 1, 'logintime' => $today, 'requestheader' => json_encode($curl->requestHeaders->toArray()), 'responseheader' => json_encode($curl->responseHeaders->toArray()), 'usertype' => 1), array("AND" => ['uid' => $userinfo['uid']]));
var_dump($res);
//var_dump($curl->responseHeaders);


//获取企业详情
//$curl->setCookie('SERVERID', 'a66d7d08fa1c8b2e37dbdc6ffff82d9e|1451004341|1451004248');
//$curl->setCookie('pspt', '%7B%22id%22%3A%221194%22%2C%22pswd%22%3A%222df5a83e9cc75ed667f259fe8e3c0ca2%22%2C%22_code%22%3A%22193b5bbdcd0eec81d8c63134c5cc56ff%22%7D');
//$curl->get('http://www.qichacha.com/firm_CN_b52197c0f716b4f770596548d0156513');
//
//if ($curl->error) {
//    echo 'Error: ' . $curl->errorCode . ': ' . $curl->errorMessage;
//} else {
//    var_dump($curl->response);
//    file_put_contents('test.html', $curl->response);
//}

//var_dump($curl->responseHeaders);