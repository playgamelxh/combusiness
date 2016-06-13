<?php
/**
 * Created by PhpStorm.
 * User: lxh
 * Date: 15/10/19
 * Time: 下午2:33
 * Desc: 配置文件 本地测试
 */

$dbConfig = array(
//    'database_type' => 'mysql',
//    'database_name' => 'com_engine',
////    'server' => '10.66.101.190',
//    'server' => '172.17.18.2',
//    'username' => 'root',
//    'password' => 'gc7232275',
//    'port' => 3306,
//    'charset' => 'utf8',
//    'option' => array(PDO::ATTR_CASE => PDO::CASE_NATURAL, PDO::ATTR_PERSISTENT => true)
);

$dbcomConfig = array(
    'database_type' => 'mysql',
    'database_name' => 'combusiness',
    'server' => '192.168.8.189',
    'username' => 'gongchang',
    'password' => 'gongchang123',
    'port' => 3306,
    'charset' => 'utf8',
    'option' => array(PDO::ATTR_CASE => PDO::CASE_NATURAL, PDO::ATTR_PERSISTENT => true)
);

$dbgccominfo = array(
    'database_type' => 'mysql',
    'database_name' => 'gccominfo',
    'server' => '192.168.8.189',
    'username' => 'gongchang',
    'password' => 'gongchang123',
    'port' => 3306,
    'charset' => 'utf8',
    'option' => array(PDO::ATTR_CASE => PDO::CASE_NATURAL, PDO::ATTR_PERSISTENT => true)
);

$dbcaijicominfo = array(
    'database_type' => 'mysql',
    'database_name' => 'caijicominfo',
    'server' => '192.168.8.189',
    'username' => 'gongchang',
    'password' => 'gongchang123',
    'port' => 3306,
    'charset' => 'utf8',
    'option' => array(PDO::ATTR_CASE => PDO::CASE_NATURAL, PDO::ATTR_PERSISTENT => true)
);

$rabbitmqConfig = array(
    'host' => '192.168.8.18',
    'port' => '5672',
    'login' => 'v3work',
    'password' => 'gc7232275',
);

