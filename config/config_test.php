<?php
/**
 * Created by PhpStorm.
 * User: lxh
 * Date: 15/10/19
 * Time: 下午2:33
 * Desc: 配置文件  线上测试
 */

$dbConfig = array(
    'database_type' => 'mysql',
    'database_name' => 'combusiness',
//    'server' => '10.66.101.190',
    'server' => '172.17.18.4',
    'username' => 'root',
    'password' => 'gc7232275',
    'port' => 3306,
    'charset' => 'utf8',
    'option' => array(PDO::ATTR_CASE => PDO::CASE_NATURAL, PDO::ATTR_PERSISTENT => true)
);

$dbcomConfig = array(
    'database_type' => 'mysql',
    'database_name' => 'combusiness',
//    'server' => '10.66.101.190',
    'server' => '172.17.18.4',
    'username' => 'root',
    'password' => 'gc7232275',
    'port' => 3306,
    'charset' => 'utf8',
    'option' => array(PDO::ATTR_CASE => PDO::CASE_NATURAL, PDO::ATTR_PERSISTENT => true)
);

$dbgccominfo = array(
    'database_type' => 'mysql',
    'database_name' => 'gccominfo',
//    'server' => '10.66.101.190',
    'server' => '172.17.18.4',
    'username' => 'root',
    'password' => 'gc7232275',
    'port' => 3306,
    'charset' => 'utf8',
    'option' => array(PDO::ATTR_CASE => PDO::CASE_NATURAL, PDO::ATTR_PERSISTENT => true)
);

$dbcaijicominfo = array(
    'database_type' => 'mysql',
    'database_name' => 'caijicominfo',
//    'server' => '10.66.101.190',
    'server' => '172.17.18.4',
    'username' => 'root',
    'password' => 'gc7232275',
    'port' => 3306,
    'charset' => 'utf8',
    'option' => array(PDO::ATTR_CASE => PDO::CASE_NATURAL, PDO::ATTR_PERSISTENT => true)
);

$rabbitmqConfig = array(
    'host' => '172.17.11.5',
    'port' => '5672',
    'login' => 'admin',
    'password' => 'gc895316',
);

$bean_config = array(
    'host' => '192.168.8.189'
);