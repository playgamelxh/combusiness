<?php
/**
 * 获取企业详情
 */
class Gs
{
    public $db;

    public function __construct()
    {

    }

    public function setDb($db)
    {
        $this->db = $db;
    }

    public function get($id, $comname = '')
    {
        $db = $this->db;

        $return  = array();
        $comArr  = array();
        if ($id>0) {
            $comArr = $db->get('engine_com_search', '*', array('id' => $id, 'LIMIT' => 1));
        } elseif( $id<=0 && !empty($comname)) {
            $comArr = $db->get('engine_com_search', '*', array('name' => $comname, 'LIMIT' => 1));
        }
//var_dump($comArr);
//print_r($db->error());
        if(!empty($comArr)){
            $p = trim(strtolower($comArr['province']));
            if(!empty($comArr) && !empty($p)){
                $return['id'] = $id;
                $fieldArr = array('id','No','Name','EconKind','OperName','Address','RegistCapi','StartDate','EndDate','TermStart','TeamEnd','Scope','BelongOrg','CheckDate','Status');
                $return['company'] = $db->get("engine_company_{$p}", $fieldArr, array('Name' => $comArr['name']));
//        echo "engine_company_{$p}";print_r($return);die();
                //RegistCapi 字段处理
                $return['company']['RegistCapi'] = str_replace(array('\r', '\n', '\t'), '', $return['company']['RegistCapi']);
                $cid = $return['company']['id'];
                if(!empty($return['company']) && $cid>0){
                    $return['company']['province'] = strtoupper($p);
                    //错误字段转换
                    $return['company']['TermEnd'] = $return['company']['TeamEnd'];
                    unset($return['company']['TeamEnd']);

                    //合伙人
                    $fieldArr = array('SeqId', 'StockType', 'StockName', 'StockPercent', 'IdentifyType', 'IdentifyNo', 'ShouldCapi', 'ShoudDate', 'InvestType', 'InvestName', 'RealCapi', 'CapiDate');
                    $temp = $db->select("engine_partners_{$p}", $fieldArr, array('company_id' => $cid));
                    $parnterArr = array();
                    if(is_array($temp) && !empty($temp)){
                        foreach ($temp as $value) {
                            if(mb_strlen($value['InvestName'], 'UTF-8') > 20) {
                                $value['InvestName'] = substr($value['InvestName'], 0, 20);
                            }
                            if(mb_strlen($value['InvestName'], 'UTF-8') > 20) {
                                $value['InvestName'] = substr($value['InvestName'], 0, 20);
                            }
                            if(mb_strlen($value['RealCapi'], 'UTF-8') > 20) {
                                $value['RealCapi'] = substr($value['RealCapi'], 0, 20);
                            }
                            if(mb_strlen($value['CapiDate'], 'UTF-8') > 20) {
                                $value['CapiDate'] = substr($value['CapiDate'], 0, 20);
                            }
                            if(mb_strlen($value['ShouldCapi'], 'UTF-8') > 20) {
                                $value['ShouldCapi'] = substr($value['ShouldCapi'], 0, 20);
                            }
                            if(mb_strlen($value['ShoudDate'], 'UTF-8') > 20) {
                                $value['ShoudDate'] = substr($value['ShoudDate'], 0, 20);
                            }
                            if(mb_strlen($value['InvestType'], 'UTF-8') > 20) {
                                $value['InvestType'] = substr($value['InvestType'], 0, 20);
                            }
                            if(isset($value['SeqId']) && $value['SeqId']>0){
                                $parnterArr[$value['SeqId']] = $value;
                            }else{
                                $parnterArr[] = $value;
                            }
                        }
                    }
                    $return['Partners'] = $parnterArr;

                    //变更
                    $fieldArr = array('No', 'ProjectName', 'BeforeContent', 'AfterContent', 'ChangeDate');
                    $temp =  $db->select("engine_changerecords_{$p}", $fieldArr, array('company_id' => $cid));
                    $changeArr = array();
                    if(is_array($temp) && !empty($temp)){
                        foreach ($temp as $value) {
                            if($value['No']>0){
                                $changeArr[$value['No']] = $value;
                            }else{
                                $changeArr[] = $value;
                            }
                            if($value['ChangeDate'] < 2016) {
                                $value['ChangeDate'] = strtotime($value['ChangeDate']);
                            }
                        }
                    }
                    $return['ChangeRecords']= $changeArr;

                    //主要员工
                    $fieldArr = array('No', 'Name', 'Job', 'CerNo');
                    $temp =  $db->select("engine_employees_{$p}", $fieldArr, array('company_id' => $cid));
                    $employArr = array();
                    if(is_array($temp) && !empty($temp)){
                        foreach ($temp as $value) {
                            if (mb_strlen($value['Job'],'UTF-8')>20)
                                continue;
                            if($value['No']>0){
                                $employArr[$value['No']] = $value;
                            }else{
                                $employArr[] = $value;
                            }
                        }
                    }
                    $return['Employees']= $employArr;

                    //年报
                    $fieldArr = array('title', 'publicTime', 'info', 'website', 'Parteners', 'invest', 'situation', 'warrant', 'stockRightChange', 'change');
                    $temp =  $db->select("engine_employees_{$p}", $fieldArr, array('company_id' => $cid));
                    $return['report']= $temp;

                    //更新时间
                    $return['updatetime'] = $comArr['updatetime'];
                } else {
                    $return = array();
                }
                //处理字段转换
                $return = $this->coverArr($return);
            }
        } else {
            $return['updatetime'] = time();
        }
        return $return;
    }

    //为移动接口转换数据格式 统一为字符串
    public function coverArr($arr)
    {
        if(is_array($arr)){
            foreach($arr as $key => $value){
                $arr[$key] = $this->coverArr($value);
            }
        }else{
            if($arr==null)
                $arr = '';
        }
        return $arr;
    }
    //判断是否有转义做处理
    public function is_stripslashes($str)
    {
        $arr = json_decode($str, true);
        if(!is_array($arr)){
            $arr = json_decode(stripslashes($str), true);
        }
        return $arr;
    }


}