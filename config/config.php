<?php
/**
 * Created by PhpStorm.
 * User: lxh
 * Date: 15/10/19
 * Time: 下午2:33
 * Desc: 配置文件 线上正式
 */

$dbConfig = array(
    'database_type' => 'mysql',
    'database_name' => 'combusiness',
//    'server' => '10.66.101.190',
    'server' => '172.17.18.6',
    'username' => 'gccominfo',
    'password' => 'E9NZf8Apwxd7SF56',
    'port' => 3306,
    'charset' => 'utf8',
    'option' => array(PDO::ATTR_CASE => PDO::CASE_NATURAL, PDO::ATTR_PERSISTENT => true)
);

$dbcomConfig = array(
    'database_type' => 'mysql',
    'database_name' => 'combusiness',
//    'server' => '10.66.101.190',
    'server' => '172.17.18.6',
    'username' => 'gccominfo',
    'password' => 'E9NZf8Apwxd7SF56',
    'port' => 3306,
    'charset' => 'utf8',
    'option' => array(PDO::ATTR_CASE => PDO::CASE_NATURAL, PDO::ATTR_PERSISTENT => true)
);

$dbgccominfo = array(
    'database_type' => 'mysql',
    'database_name' => 'gccominfo',
//    'server' => '10.66.101.190',
    'server' => '172.17.18.4',
    'username' => 'gccominfo',
    'password' => 'E9NZf8Apwxd7SF56',
    'port' => 3306,
    'charset' => 'utf8',
    'option' => array(PDO::ATTR_CASE => PDO::CASE_NATURAL, PDO::ATTR_PERSISTENT => true)
);

$dbcaijicominfo = array(
    'database_type' => 'mysql',
    'database_name' => 'caijicominfo',
//    'server' => '10.66.101.190',
    'server' => '172.17.18.6',
    'username' => 'gccominfo',
    'password' => 'E9NZf8Apwxd7SF56',
    'port' => 3306,
    'charset' => 'utf8',
    'option' => array(PDO::ATTR_CASE => PDO::CASE_NATURAL, PDO::ATTR_PERSISTENT => true)
);

$rabbitmqConfig = array(
    'host' => '172.17.16.103',
    'port' => '5672',
    'login' => 'admin',
    'password' => 'gc7232275',
);
$rabbitmq108Config = array(
    'host' => '172.17.16.108',
    'port' => '5672',
    'login' => 'admin',
    'password' => 'gc7232275',
);

$bean_config = array(
    'host' => '172.17.16.101'
);
