<?php

/**
 * Created by PhpStorm.
 * User: afyji
 * Date: 15/4/23
 * @description: 打码返回
 */
class CheckCode
{

    public static $id = '';


    public static function dama($checkImg, $typeid = 8001)
    {
        $damaUrl = 'http://api.ruokuai.com/create.json';
        $ch = curl_init();
        $ccheckImg = curl_file_create($checkImg);
        $postFields = array('username' => 'afyji728',
            'password' => '821280.30',
            'typeid' => $typeid,    //4位的字母数字混合码   类型表http://www.ruokuai.com/pricelist.aspx
            'timeout' => 15,    //中文以及选择题类型需要设置更高的超时时间建议90以上
            'softid' => '38065',    //改成你自己的
            'softkey' => 'ca10a9a43b70401d9456e20bbb9549df',    //改成你自己的
            'image' => $ccheckImg
        );


        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_URL, $damaUrl);
        curl_setopt($ch, CURLOPT_TIMEOUT, 65);    //设置本机的post请求超时时间，如果timeout参数设置60 这里至少设置65
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
        curl_setopt($ch, CURLOPT_INFILESIZE, filesize($checkImg));
//CURLOPT_FILE
        $result = curl_exec($ch);

        curl_close($ch);
//'{"Result":"1","Id":"96b36f7f-f747-4517-89d8-c2a9addba2b0"}'
        $result = json_decode($result, true);
        self::$id = $result['Id'];
        if (!isset($result['Result']))
            return '';
        $checkNo = $result['Result'];
        return $checkNo;
    }


    public static function getRefundByError()
    {
        $id = self::$id;
        if (empty($id))
            return;
        $refundUrl = 'http://api.ruokuai.com/reporterror.json';

        $ch = curl_init();
        $postFields = array(
            'username' => 'afyji728',
            'password' => '821280.30',
            'softid' => '38065',    //改成你自己的
            'softkey' => 'ca10a9a43b70401d9456e20bbb9549df',    //改成你自己的
            'id' => $id
        );

        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_URL, $refundUrl);
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);

        $result = curl_exec($ch);
        curl_close($ch);

        return $result;
    }
}