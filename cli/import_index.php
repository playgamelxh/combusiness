<?php
/**
 * 采集库向工商库转移程序
 */
ini_set('display_error', 'On');
error_reporting(E_ALL);
date_default_timezone_set('PRC');

header("Content-type:text/html;charset=utf-8");
defined('ROOT_PATH') or define('ROOT_PATH', dirname(__FILE__));
require ROOT_PATH . '/../lib/medoo.php';
require ROOT_PATH . '/../lib/Curl.php';
require ROOT_PATH . '/import_gs.php';
//require '../init.php';

$db = new medoo(array(
    'database_type' => 'mysql',
    'database_name' => 'combusiness',
//    'server' => '10.66.101.190',
    'server' => '172.17.18.2',
    'username' => 'root',
    'password' => 'gc7232275',
    'port' => 3306,
    'charset' => 'utf8',
    'option' => array(PDO::ATTR_CASE => PDO::CASE_NATURAL, PDO::ATTR_PERSISTENT => true)
));

$db1 = new medoo(array(
    'database_type' => 'mysql',
    'database_name' => 'com_engine',
//    'server' => '10.66.101.190',
    'server' => '172.17.18.2',
    'username' => 'root',
    'password' => 'gc7232275',
    'port' => 3306,
    'charset' => 'utf8',
    'option' => array(PDO::ATTR_CASE => PDO::CASE_NATURAL, PDO::ATTR_PERSISTENT => true)
));

class Combusinessimport
{
    public $db;
    public $db1;
    public $min;
    public $max;

    public function __construct($db, $db1, $min, $max)
    {
        $this->db  = $db;
        $this->db1 = $db1;
        $this->min = $min;
        $this->max = $max;
    }
    public function run()
    {
        if($this->min>0 && $this->max>0){
            $where = array('AND' => array('cid[>=]' => $this->min, 'cid[<]' => $this->max));
        }else{
            $where = array();
        }
        $id = $this->db->max('cb_combusiness', 'cid', $where);
        ($id==0) && $id = $this->min;
        $gsObj = new Gs();
        $gsObj->setDb($this->db1);
        while ($id <= $this->max) {
            $id++;
            echo $id,"\r\n";
            //$resStr = $this->curl("http://com_engine.gongchang.cn/gs.php?id={$id}");
            //$resArr = empty($resStr) ? array() : json_decode($resStr, true);
            $resArr = $gsObj->get($id);
            //print_r($resArr);die();
            if (isset($resArr['company']) && is_array($resArr['company']) && !empty($resArr['company'])) {
                $this->insert($resArr);
            }

            $this->db->clear();
            $this->db1->clear();
        }
    }

    public function insert($resArr)
    {
        //处理错误的日期
        if ($resArr['company']['TermStart'] < 0) {
            $resArr['company']['TermStart'] = 0;
        }
        if ($resArr['company']['TermEnd'] < 0) {
            $resArr['company']['TermEnd'] = 0;
        }
        if ($resArr['company']['StartDate'] < 0) {
            $resArr['company']['StartDate'] = 0;
        }
        if ($resArr['company']['EndDate'] < 0) {
            $resArr['company']['EndDate'] = 0;
        }
        if ($resArr['company']['CheckDate'] < 0) {
            $resArr['company']['CheckDate'] = 0;
        }
        //写入combusiness表
        $combusArr = array(
            'cid'     => $resArr['id'],
            'comname' => $resArr['company']['Name'],
            'regno' => $resArr['company']['No'],
            'uniqueno' => $this->unique($resArr['company']['Name'], $resArr['company']['No']),
            'scope' => $resArr['company']['Scope'],
            'state' => $resArr['company']['Status'],
            'comtype' => $resArr['company']['EconKind'],
            'regcapital' => $resArr['company']['RegistCapi'],
            'address' => $resArr['company']['Address'],
            'businessstart' => $resArr['company']['TermStart'],
            'businessend' => $resArr['company']['TermEnd'],
            'checkdate' => intval($resArr['company']['CheckDate']),
            'regagency' => $resArr['company']['BelongOrg'],
            'legal' => $resArr['company']['OperName'],
            'startdate' => intval($resArr['company']['StartDate']),
            'enddate' => intval($resArr['company']['EndDate']),
            'cate1' => 0,
            'cate2' => 0,
            'uptime' => $resArr['updatetime'],
            'areaid' => $this->getAreaId($resArr['company']['No']),
        );
        //省 市 区
        if($combusArr['areaid'] > 0) {
            $combusArr['province'] = intval($combusArr['areaid']/10000)*10000;
            $temp = substr($combusArr['areaid'], 0, 2);
            if (in_array($temp, array(11, 12, 31, 50))) {
                $combusArr['city'] = intval($temp . '0100');
            } else {
                $combusArr['city'] = intval($combusArr['areaid']/100)*100;
            }
            $combusArr['zone'] = $combusArr['areaid'];
        }
//        $param = array(
//            "service" => "Combusiness\Services\Combusiness",
//            "method" => "add",
//            "args" => array($combusArr)
//        );
//        $res = $this->di->get("local")->call($param);
//        $cid = intval($res['data']);
        $i = $this->db->insert('cb_combusiness', $combusArr);
        if($i<=0){
            print_r($this->db->error());
        }
        $cid = $resArr['id'];
        if ($cid <= 0 || $i != $cid) {
            return;
//            die('wrong');
        }

        //写入员工表
        if (is_array($resArr['Employees']) && !empty($resArr['Employees'])) {
            $empArr = array();
            foreach ($resArr['Employees'] as $value) {
                $empArr[] = array(
                    'cid' => $cid,
                    'name' => $value['Name'],
                    'job' => $value['Job'],
                    'certno' => isset($value['CerNo']) && !empty($value['CerNo']) ? $value['CerNo'] : '无',
                );
            }
            //
//            $param = array(
//                "service" => "Combusiness\Services\Employee",
//                "method" => "addMore",
//                "args" => array($empArr)
//            );
//            $this->di->get("local")->call($param);
            if(is_array($empArr) && !empty($empArr)){
                $i = $this->db->insert('cb_employee', $empArr);
                if($i<=0){
                    print_r($this->db->error());
                }
            }
        }

        //写入变更表
        if (is_array($resArr['ChangeRecords']) && !empty($resArr['ChangeRecords'])) {
            $chaArr = array();
            foreach ($resArr['ChangeRecords'] as $value) {
                $chaArr[] = array(
                    'cid' => $cid,
                    'infoname' => $value['ProjectName'],
                    'oldvalue' => $value['BeforeContent'],
                    'newvalue' => $value['AfterContent'],
                    'uptime' => intval($value['ChangeDate']),
                );
            }
            //
//            $param = array(
//                "service" => "Combusiness\Services\Changelog",
//                "method" => "addMore",
//                "args" => array($chaArr)
//            );
//            $this->di->get("local")->call($param);
            if(is_array($chaArr) && !empty($chaArr)){
                $i = $this->db->insert('cb_changelog', $chaArr);
                if($i<=0){
                    print_r($this->db->error());
                }
            }

        }

        //写入合伙人表
        if (is_array($resArr['Partners']) && !empty($resArr['Partners'])) {
            $parArr = array();
            foreach ($resArr['Partners'] as $value) {
                $parArr[] = array(
                    'cid' => $cid,
                    'stockholder' => $value['StockName'],
                    'stocktype' => $value['StockType'],
                    'stockpercent' => $value['StockPercent'],
                    'identifyname' => $value['IdentifyType'],
                    'identifyno' => $value['IdentifyNo'],
                    'shouldcapi' => $value['ShouldCapi'],
                    'shoulddate' => intval($value['ShoudDate']),
                    'shouldtype' => $value['InvestType'],
                    'realtype' => $value['InvestName'],
                    'realcapi' => $value['RealCapi'],
                    'realdate' => intval($value['CapiDate']),
                );
            }
            //
//            $param = array(
//                "service" => "Combusiness\Services\Partner",
//                "method" => "addMore",
//                "args" => array($parArr)
//            );
//            $this->di->get("local")->call($param);
            if(is_array($parArr) && !empty($parArr)){
                $i = $this->db->insert('cb_partner', $parArr);
                if($i<=0){
                    print_r($this->db->error());
                }
            }

        }
        //写入年表表
        if (is_array($resArr['report']) && !empty($resArr['report'])) {
            $repArr = array();
            foreach ($resArr['report'] as $value) {
                $repArr[] = array(
                    'cid' => $cid,
                    'title' => $value['title'],
                    'publictime' => $this->strTime($value['publicTime']),
                    'info' => $value['info'],
                    'website' => $value['website'],
                    'partner' => $value['Parteners'],
                    'invest' => $value['invest'],
                    'situation' => $value['situation'],
                    'warrant' => $value['warrant'],
                    'stockright' => $value['stockRightChange'],
                    'changeinfo' => $value['change'],
                );
            }
            //
//            $param = array(
//                "service" => "Combusiness\Services\Report",
//                "method" => "addMore",
//                "args" => array($repArr)
//            );
//            $this->di->get("local")->call($param);
            if(is_array($repArr) && !empty($repArr)){
                $i = $this->db->insert('cb_partner', $repArr);
                if($i<=0){
                    print_r($this->db->error());
                }
            }

        }
        return $cid;
    }
    //处理时间函数
    public function strTime($str)
    {
        $str = str_replace(array('年', '月', '日'), '-', $str);
        return strtotime($str);
    }
    //省份信息转换
    public function province($province)
    {
        $arr = array(
            "CN" => 0, "BJ" => 1, "TJ" => 3, "HB" => 4, "SX" => 5, "NMG" => 6, "LN" => 7, "JL" => 8,
            "HLJ" => 9, "SH" => 10, "JS" => 11, "ZJ" => 12, "AH" => 13, "FJ" => 14, "JX" => 15, "SD" => 16,
            "GD" => 17, "GX" => 18, "HAIN" => 19, "HEN" => 20, "HUB" => 21, "HUN" => 22, "CQ" => 23, "SC" => 24,
            "GZ" => 25, "YN" => 26, "XZ" => 27, "SAX" => 28, "GS" => 29, "QH" => 30, "NX" => 31, "XJ" => 32);
        return $arr[$province];
    }
    //自定义唯一unique
    public function unique($name, $reg_id)
    {
        $salt = '48$%Yd&s4i';
        $str = md5($name.$reg_id.$salt);
        return substr($str, 3, 26);
    }

    /**
     * 功能描述 curl处理函数
     * @author 吕小虎
     * @datetime 2015-7-30 9:42
     * @version 1.0
     * @param
     * @return
     */
    public function curl($url, $method = '', $post = '', $returnHeaderInfo = false, $timeout = 10)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_TIMEOUT, $timeout); //设置超时时间,单位秒
        curl_setopt($curl, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.1; zh-CN; rv:1.9.2.8) Gecko/20100722 Firefox/3.6.8");
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        if ($method == 'post') {
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $post);
        }
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        $str      = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        unset($curl);
        if (!$str) {
            return false;
        }
        //返回头信息
        if ($returnHeaderInfo) {
            return array($httpCode, $str);
        }
        return $str;
    }

    /**
     * 功能描述 处理省市区字段
     * @author 吕小虎
     * @datetime ${DATE} ${TIME}
     * @version
     * @param
     * @return
     */
    public function areaAction()
    {
        $cid = 0;
        while (true) {
            echo "$cid\r\n";
//            $temp = Cbcombusiness::findFirst(array('conditions' => 'cid > :cid: and areaid = 0', 'bind' => array('cid' => $cid)));
            $temp = $this->db->get('cb_combusiness', '*', array('AND' => array('cid[>]' => $cid, 'areaid' => 0)));
            $resArr = is_object($temp) ? $temp->toArray() : array();
            if (!is_array($resArr) || empty($resArr)) {
                die("run over!\r\n");
            } else {
                $cid = $resArr['cid'];
            }
            $temp->areaid = $this->getAreaId($resArr['regno']);
            if ($temp->save() == false) {
                echo "error\r\n";
                die();
            }
        }
    }

    /**
     * 功能描述 根据注册号 获取地区编号id
     * @author 吕小虎
     * @datetime ${DATE} ${TIME}
     * @version
     * @param
     * @return
     */
    public function getAreaId($regno)
    {
        if (empty($regno)) {
            return 0;
        }
        $code = substr($regno, 0, 6);
        if (!is_numeric($code)) {
            return 0;
        }
        $areaid = $this->findArea($code);
        if ($areaid==0) {
            $code = intval(substr($code, 0, 4))*100;
            $areaid = $this->findArea($code);
            if ($areaid==0) {
                $code = intval(substr($code, 0, 2))*10000;
                $areaid = $this->findArea($code);
            }
        }
        return $areaid;
    }

    public function findArea($areaid)
    {
//        $temp = Area::findFirst("id = {$areaid}");
//        $resArr = is_object($temp) ? $temp->toArray() : array();
        $resArr = $this->db->get('area', '*', array('id' => $areaid));
        if (empty($resArr)) {
            return 0;
        } else {
            return $resArr['id'];
        }
    }
}

$min = isset($argv[1]) ? intval($argv[1]) : 0;
$max = isset($argv[2]) ? intval($argv[2]) : 0;
$combus = new Combusinessimport($db, $db1, $min, $max);
$combus->run();
