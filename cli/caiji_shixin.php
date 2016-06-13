<?php
/**
 * 国家法院失信采集
 */
ini_set('display_errors', 'ON');
error_reporting(E_ALL);

header("Content-type:text/html;charset=utf-8");
defined('ROOT_PATH') or define('ROOT_PATH', dirname(__FILE__));
include(ROOT_PATH . '/../lib/medoo.php');
include(ROOT_PATH . '/../lib/Curl.php');

$db = new medoo(array(
    'database_type' => 'mysql',
//    'database_name' => 'combusiness',
//    'server' => '10.66.101.190',
//    'username' => 'root',
//    'password' => 'gc7232275',

    'database_name' => 'fayuan',
    'server' => 'localhost',
    'username' => 'root',
    'password' => '123456',

    'port' => 3306,
    'charset' => 'utf8',
    'option' => array(PDO::ATTR_CASE => PDO::CASE_NATURAL)
));
$curl = new Curl();

$id = 2504;
while($id<=1239000){
    echo "{$id}\r\n";
    $resArr = $db->get('shixin', '*', array('id' => $id));
    if(is_array($resArr) && !empty($resArr)){
        $id++;
        continue;
    }
    $url = "http://shixin.court.gov.cn/detail?id={$id}";
    $resArr = getHtml($url);

    if(is_array($resArr) && !empty($resArr)){
        //判断数据是否存在
        print_r($resArr);
        $i = $db->insert('shixin', $resArr);
        if($i<=0){
            print_r($db->error());
            die();
        }
    }
    file_put_contents('shixin.txt', $id);
    $id++;
    $db->clear();
}

function getHtml($url)
{
    global $curl;
    $curl->setUrl($url);
    $html = $curl->run();
    return json_decode($html, true);
}