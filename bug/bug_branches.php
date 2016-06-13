<?php
/**
 * Created by PhpStorm.
 * User: lxh
 * Date: 15/10/20
 * Time: 上午10:28
 * Desc: 处理企业分支机构
 */

header("Content-type:text/html;charset=utf-8");
defined('ROOT_PATH') or define('ROOT_PATH', dirname(__FILE__));
ini_set('memory_limit', '128M');

include ROOT_PATH . '/../config/config.php';
include ROOT_PATH . '/../lib/medoo.php';

$db = new medoo($dbConfig);
$dbcom = new medoo($dbcomConfig);

$areaArr = array("总局" => "CN", "北京" => "BJ", "天津" => "TJ", "河北" => "HB", "山西" => "SX", "内蒙古" => "NMG", "辽宁" => "LN", "吉林" => "JL",
	"黑龙江" => "HLJ", "上海" => "SH", "江苏" => "JS", "浙江" => "ZJ", "安徽" => "AH", "福建" => "FJ", "江西" => "JX", "山东" => "SD", "广东" => "GD",
	"广西" => "GX", "海南" => "HAIN", "河南" => "HEN", "湖北" => "HUB", "湖南" => "HUN", "重庆" => "CQ", "四川" => "SC", "贵州" => "GZ", "云南" => "YN",
	"西藏" => "XZ", "陕西" => "SAX", "甘肃" => "GS", "青海" => "QH", "宁夏" => "NX", "新疆" => "XJ");

foreach ($areaArr as $area) {
	doJob($area);
}

//
function doJob($p) {
	global $db, $dbcom;
	$id = 0;
	while (true) {
		$comArr = $db->get('engine_company_' . strtolower($p), array('id', 'Name', 'Branches'), array('id[>]' => $id, 'LIMIT' => 1));
		if (empty($comArr)) {
			break;
		} else {
			$id = $comArr['id'];
		}
		$branchArr = json_decode($comArr['Branches'], true);
		if (is_array($branchArr) && !empty($branchArr)) {
			$combusArr = $dbcom->get('cb_combusiness', array('cid', 'comname'), array('comname' => $comArr['Name'], 'LIMIT' => 1));
			if (is_array($combusArr) && !empty($combusArr)) {
				$insert = array();
				foreach ($branchArr as $arr) {
					$insert[] = array(
						'cid' => $combusArr['cid'],
						'regno' => $arr['RegNo'],
						'comname' => $arr['Name'],
					);
				}
				$dbcom->insert('cb_branch', $insert);
			}
		}

		$db->clear();
		$dbcom->clear();
	}
}