<?php
/**
 * Created by PhpStorm.
 * User: lxh
 * Date: 16/3/10
 * Time: 下午4:04
 */
header("Content-type:text/html;charset=utf-8");
defined('ROOT_PATH') or define('ROOT_PATH', dirname(__FILE__));
ini_set ('memory_limit', '128M');

include(ROOT_PATH . '/../lib/medoo.php' );
include(ROOT_PATH . '/../lib/Curl.php');

$dbHelpConfig = array(
    'database_type' => 'mysql',
    'database_name' => 'help',
    'server' => '172.17.18.2',
    'username' => 'gchelp',
    'password' => 'UF6FKvZVUWDY9aN2',
    'port' => 3306,
    'charset' => 'utf8',
    'option' => array(PDO::ATTR_CASE => PDO::CASE_NATURAL, PDO::ATTR_PERSISTENT => true)
);
$dbHelp  = new medoo($dbHelpConfig);

$dbConfig = array(
    'database_type' => 'mysql',
    'database_name' => 'gccominfo',
    'server' => '172.17.18.4',
    'username' => 'gccominfo',
    'password' => 'E9NZf8Apwxd7SF56',
    'port' => 3306,
    'charset' => 'utf8',
    'option' => array(PDO::ATTR_CASE => PDO::CASE_NATURAL, PDO::ATTR_PERSISTENT => true)
);
$db  = new medoo($dbConfig);
$curObj = new Curl();

$id= 0;
while(true) {
    $resArr = $dbHelp->get('hp_tort', '*', array('id[>]' => $id, 'ORDER' => 'id asc'));
    if (empty($resArr)) {
        echo "OVER!\r\n";
        die();
    }
    $id = $resArr['id'];
    $comname = $resArr['companyname'];
    $url     = $resArr['shopurl'];
    preg_match('/http:\/\/([^\.\/]+)\./i', $url, $match);
    $username = isset($match[1]) ? $match[1] : '';
    if (!empty($username)) {
        $temp = $db->get('gc_company', '*', array('username' => $username));
        if(is_array($temp) && !empty($temp)) {
            $cid = $temp['cid'];
            $url = "http://api.gongchang.com/combusiness/combusiness/unlink?cid={$cid}&comname=".urlencode($comname);
            $curObj->setUrl($url);
            $curObj->setTimeout(5);
            $curObj->run();
        }
    }
    echo "{$id},{$comname},{$username}\r\n";

    $dbHelp->clear();
    $db->clear();
}
