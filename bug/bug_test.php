<?php
//$str = 'a:1:{i:0;a:2:{s:4:"Name";s:36:"南京宗发电子科技有限公司";s:3:"Url";s:9:"一号店";}}';
//echo json_decode($str);
//print_r(unserialize($str));

ini_set('display_errors', 'ON');
error_reporting(E_ALL);
defined('ROOT_PATH') or define('ROOT_PATH', dirname(__FILE__));

$keyword = isset($argv[1]) ? trim($argv[1]) : '';

include(ROOT_PATH . '/../lib/Beanstalk.php' );
include(ROOT_PATH . '/../config/config.php');

$bean_config = array(
    'host' => '172.17.16.101'
);
$time = microtime(true);
$bean = new Socket_Beanstalk($bean_config);
$bean->connect();
$bean->choose('add_comname');
echo microtime(true) - $time,"\r\n";
$i = $bean->put(1024, 0, 1, serialize($keyword));
echo microtime(true) - $time,"\r\n";