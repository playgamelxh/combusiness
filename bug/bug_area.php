<?php
/**
 * 处理地区信息数据
 */
header("Content-type:text/html;charset=utf-8");
defined('ROOT_PATH') or define('ROOT_PATH', dirname(__FILE__));
ini_set('memory_limit', '128M');

//include(ROOT_PATH . '/../config/config.php');
include ROOT_PATH . '/../lib/medoo.php';
include ROOT_PATH . '/../lib/RabbitMQ.php';

$env = isset($argv[1]) ? $argv[1] : 'dev';
if ($env == 'dev') {
	include ROOT_PATH . '/../config/config_local.php';
} elseif ($env == 'test') {
	include ROOT_PATH . '/../config/config_test.php';
} elseif ($env == 'pro') {
	include ROOT_PATH . '/../config/config.php';
} else {
	die('config');
}

$db = new medoo($dbcomConfig);
$rabbitmqObj = new RabbitMQ($rabbitmqConfig);

$temp = $db->get('num', '*', array('id' => 4));
$cid = intval($temp['num']);

//echo getZoneByAddress('天津市滨海新区大港区中塘村', '天津市滨海新区工商行政管理局', '天津大港鹏翎胶管股份有限公司');
//die();

$areaArr = array();
$cid = 25245160;
while ($cid <= 25250939) {
	//从25245161开始 到  25250939

	$resArr = $db->get('cb_combusiness', '*', array('AND' => array('cid[>]' => $cid, 'province' => 0), 'LIMIT' => 1));
	if (!is_array($resArr) || empty($resArr)) {
		die('Over');
	} else {
		$cid = $resArr['cid'];
	}
	echo $cid, "\r\n";
	//一般处理
	common($resArr);
	//打入更新缓存队列
	$rabbitmqObj->set('combusiness', 'cbupdate', $arr['cid'], 'ssdb');

	//根据采集表处理
	/*
	    $resArr = $db->get('cb_combusiness', array('cid', 'comname', 'province', 'areaid'), array('AND' => array('cid[>]' => $cid, 'province[<=]' => 100000), 'ORDER' => 'cid asc', 'LIMIT' => 1));
	    if(!is_array($resArr) || empty($resArr)) {
	        die("Over!\r\n");
	    } else {
	        $cid = $resArr['cid'];
	    }
	    $db->update('num', array('num' => $cid), array('id' => 4));

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
*/
//die();

	//处理

	$db->clear();
}
//通过采集表处理

//一般处理
function common($resArr) {
	global $db;
	if ($resArr['province'] <= 100000 || $resArr['province'] > 999999) {
		//如果省份有问题  通过注册号 获取地区编号
		$areaid = 0;
		if (mb_strlen($resArr['regno'], 'utf-8') == 13 || mb_strlen($resArr['regno'], 'utf-8') == 15) {
			$areaid = substr($resArr['regno'], 0, 6);
		}
		//通过地址 获取地区编号
		if ($areaid <= 0) {
			$areaid = getZoneByAddress($resArr['address'], $resArr['regagency'], $resArr['comname']);
			if (strlen($areaid) > 6) {
				$areaid = substr($areaid, 0, 6);
			}
//            echo $areaid,'|',$resArr['address'],'|',$resArr['regagency'],"\r\n";
			//            die();
		}
		if ($areaid <= 0) {
			$update = array(
				'province' => 0,
				'city' => 0,
				'zone' => 0,
				'areaid' => 0,
			);
			$db->update('cb_combusiness', $update, array('cid' => $resArr['cid']));
			return;
		} else {
			//总局数据
			if ($areaid == 100000) {
				$areaid = 110000;
			}
		}
		$update['areaid'] = 0;
		$update['province'] = findArea(intval($areaid / 10000) * 10000);
		if ($update['province'] > 0) {
			$update['areaid'] = $update['province'];
		}
		$temp = substr($areaid, 0, 2);
		$city = 0;
		if (in_array($temp, array(11, 12, 31, 50))) {
			$city = intval($temp . '0100');
		} else {
			$city = intval($areaid / 100) * 100;
		}
		$update['city'] = findArea($city);
		if ($update['city'] > 0) {
			$update['areaid'] = $city;
		}
		$update['zone'] = findArea($areaid);
		if ($update['zone'] > 0) {
			$update['areaid'] = $areaid;
		}
		$db->update('cb_combusiness', $update, array('cid' => $resArr['cid']));

	} elseif ($resArr['areaid'] == 0) {
		//如果areaid有问题, 省份有值，通过省份去完善
		$update['areaid'] = $resArr['province'];
		$db->update('cb_combusiness', $update, array('cid' => $resArr['cid']));
	}

}

function company($resArr, $areaArr) {
	global $db;

	$comArr = $db->get('company', array('province', 'city', 'county'), array('comname' => $resArr['comname'], 'LIMIT' => 1));
	if (!is_array($comArr) || empty($comArr)) {
		return;
	}
	$p = trim($comArr['province']);
	$update['province'] = isset($areaArr[$p]) ? $areaArr[$p] : 0;
	if ($resArr['areaid'] <= 0 || ($update['province'] > 0 && intval($update['areaid'] / 10000) * 10000 != $update['province'])) {
		$update['areaid'] = $update['province'];
	}
	//市
	if (!empty($comArr['city'])) {
		$id = findAreaByNameParent($comArr['city'], $update['province']);
		if ($id > 0) {
			$update['city'] = $id;
			$update['areaid'] = $id;
			//区
			if (!empty($comArr['county'])) {
				$id = findAreaByNameParent($comArr['county'], $id);
				if ($id > 0) {
					$update['zone'] = $id;
					$update['areaid'] = $id;
				}
			}
		}
	}

	$db->update('cb_combusiness', $update, array('cid' => $resArr['cid']));
}

/**
 * 功能描述 根据注册号 获取地区编号id
 * @author 吕小虎
 * @datetime ${DATE} ${TIME}
 * @version
 * @param
 * @return
 */
function getAreaId($regno) {
	if (empty($regno)) {
		return 0;
	}
	$code = substr($regno, 0, 6);
	if (!is_numeric($code)) {
		return 0;
	}
	$areaid = findArea($code);
	if ($areaid == 0) {
		$code = intval(substr($code, 0, 4)) * 100;
		$areaid = findArea($code);
		if ($areaid == 0) {
			$code = intval(substr($code, 0, 2)) * 10000;
			$areaid = findArea($code);
		}
	}
	return $areaid;
}

function findArea($areaid) {
	global $db;
	$resArr = $db->get('area', '*', array('id' => $areaid));
	if (empty($resArr)) {
		return 0;
	} else {
		return $resArr['id'];
	}
}
function findAreaByName($name) {
	global $db;

	if (strpos($name, '县') !== false || strpos($name, '区') !== false) {
		$resArr = $db->select('area', '*', array('areaname' => $name));
		if (count($resArr) == 1) {
			return $resArr[0]['id'];
		} else {
			return 0;
		}
	} else {
		$resArr = $db->get('area', '*', array('areaname' => $name));
		if (empty($resArr)) {
			return 0;
		} else {
			return $resArr['id'];
		}
	}

}
function findAreaByNameParent($name, $p = 0) {
	global $db;
	$resArr = $db->get('area', '*', array('AND' => array('areaname' => $name, 'parentid' => $p)));
	if (empty($resArr)) {
		return 0;
	} else {
		return $resArr['id'];
	}
}
function getZoneByAddress($address, $regagency, $comname) {
//    echo $address,'---',$regagency;
	$zone = '';
	//匹配地址
	//    $p = '/[^省](.*?市)/';
	//    $p = '/([^省](.*?市)|[^市](.*?区|.*?县))/';
	//去掉空格
	$address = str_replace(' ', '', $address);
	$regagency = str_replace(array(' ', '市场'), '', $regagency);

	$pArr = array(
		'/[^省](.*?市)/',
		'/(.*?省)/',
		'/(.*?县|.*?区)/',
	);
	foreach ($pArr as $p) {

		preg_match($p, $address, $match);
		print_r($match);
		if (!empty($match)) {
			$zone = $match[0];
			if (!empty($zone)) {
				$areaid = findAreaByName($zone);
				if ($areaid > 0) {
					return $areaid;
				}

			}
		}

		//匹配工商局地址
		preg_match($p, $regagency, $match);
		print_r($match);
		$zone = isset($match[0]) ? $match[0] : '';
		if (!empty($zone)) {
			$areaid = findAreaByName($zone);
			if ($areaid > 0) {
				return $areaid;
			}

		}
	}
	//区域匹配，先不错县区级别
	global $areaArr;
	if (!is_array($areaArr) || empty($areaArr)) {
		global $db;
		$areaArr = $db->select('area', array('id', 'shortname'), array('level[<=]' => 2));
	}

	foreach ($areaArr as $value) {
		if (strpos($address, $value['shortname']) === 0) {
			return $value['id'];
		}
		if (strpos($regagency, $value['shortname']) === 0) {
			return $value['id'];
		}
	}
	//企业名称 地址判断先 去掉 分公司的情况
	//注意分公司的情况
	if (strpos($comname, '分公司') === false) {
		foreach ($areaArr as $value) {
			if (strpos($comname, $value['shortname']) === 0) {
				return $value['id'];
			}
		}
	}
	return 0;
}
