<?php
/**
 * 处理分类
 */
header("Content-type:text/html;charset=utf-8");
defined('ROOT_PATH') or define('ROOT_PATH', dirname(__FILE__));
ini_set('memory_limit', '128M');

include ROOT_PATH . '/../config/cate.php';
include ROOT_PATH . '/../lib/medoo.php';

$env = isset($argv[1]) ? $argv[1] : 'dev';
if ($env == 'dev') {
	include ROOT_PATH . '/../config/config_local.php';
} elseif ($env == 'test') {
	include ROOT_PATH . '/../config/config_test.php';
} elseif ($env == 'pro') {
	include ROOT_PATH . '/../config/config.php';
}

$db = new medoo($dbcomConfig);

$min = isset($argv[2]) ? intval($argv[2]) : 0;
$max = isset($argv[3]) ? intval($argv[3]) : 0;
//$cid = $min;
//while($cid <= $max){
$temp = $db->get('num', '*', array('id' => 3));
$cid = intval($temp['num']);
while (true) {
	$resArr = $db->get('cb_combusiness', array('cid', 'scope'), array('cid[>]' => $cid, 'LIMIT' => 1, 'ORDER' => 'cid asc'));
	if (!is_array($resArr) || empty($resArr)) {
		unset($db);
		sleep(3);
		$db = new medoo($dbcomConfig);
		continue;
	} else {
		$cid = $resArr['cid'];
	}
//    echo $cid,"\r\n";
	//    if($cid%10 == 0) {
	$db->update('num', array('num' => $cid), array('id' => 3));
//    }

	if (!empty($resArr['scope'])) {
		$match = array();
		foreach ($cate2 as $key => $value) {
			$keyword = $value['keyword'];
			if (!empty($keyword)) {
				$keywordArr = explode('|', $value['keyword']);
				if (is_array($keywordArr) && !empty($keywordArr)) {
					foreach ($keywordArr as $word) {
						if (strpos($resArr['scope'], $word) !== false) {
							$match[] = $key;
						}
					}
				}
			}
		}
		if (is_array($match) && !empty($match)) {
			$cate1 = array();
			foreach ($match as $value) {
				$cate1[] = intval($value / 100) * 100;
			}
			$update = array(
				'cate1' => implode(',', $cate1),
				'cate2' => implode(',', $match),
			);
//            print_r($update);
			//die();
			$db->update('cb_combusiness', $update, array('cid' => $cid));
		}
	}

	$db->clear();
}
