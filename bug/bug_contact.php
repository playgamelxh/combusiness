<?php
/**
 * Created by PhpStorm.
 * User: lxh
 * Date: 15/10/20
 * Time: 上午10:05
 * Desc: 处理导数据联系人未转移问题
 */

header("Content-type:text/html;charset=utf-8");
defined('ROOT_PATH') or define('ROOT_PATH', dirname(__FILE__));
ini_set ('memory_limit', '128M');

include(ROOT_PATH . '/../config/config.php');
include(ROOT_PATH . '/../lib/medoo.php' );

$db = new medoo($dbConfig);
$dbcom = new medoo($dbcomConfig);

$id = 0;
while(true) {
    $resArr = $db->get('engine_contact', '*', array('id[>]' => $id, 'LIMIT' => 1, 'ORDER' => 'id asc'));
    if(!is_array($resArr) || empty($resArr)){
        die('Over!');
    }else{
        $id = $resArr['id'];
    }

    if(empty($resArr['province'])){
        continue;
    }

    $comArr = $db->get('engine_company_'.strtolower($resArr['province']), '*', array('id' => $resArr['company_id'], 'LIMIT' => 1));

    if(is_array($comArr) && !empty($comArr)) {
        $comname = $comArr['Name'];

        $combusArr = $dbcom->get('cb_combusiness', '*', array('comname' => $comname, 'LIMIT' => 1));
        if(is_array($combusArr) && !empty($combusArr)){
            //判断是否存在
            $contactArr = $dbcom->get('cb_com_contact', '*', array('cid' => $combusArr['cid'], 'LIMIT' => 1));
            if(empty($contactArr)){
                $insert = array(
                    'cid'           => $combusArr['cid'],
                    'WebSite'       => $resArr['WebSite'],
                    'PhoneNumber'   => $resArr['PhoneNumber'],
                    'Email'         => $resArr['Email'],
                );
                $dbcom->insert('cb_com_contact', $insert);
            }
        }
    }

    $db->clear();
    $dbcom->clear();

}