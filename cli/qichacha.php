<?php

/**
 * Desc: 企查查工具类
 */
class qichacha
{

    public static $useragent = array(
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_5) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/45.0.2454.85 Safari/537.36 QQBrowser/3.9.3943.400',
        'Mozilla/5.0 (Macintosh; U; Intel Mac OS X 10_6_8; en-us) AppleWebKit/534.50 (KHTML, like Gecko) Version/5.1 Safari/534.50',
        'Mozilla/5.0 (Windows; U; Windows NT 6.1; en-us) AppleWebKit/534.50 (KHTML, like Gecko) Version/5.1 Safari/534.50',
        'Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.1; Trident/5.0',
        'Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.1; Trident/5.0',
        'Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 6.0; Trident/4.0)',
        'Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 6.0)',
        'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.6; rv:2.0.1) Gecko/20100101 Firefox/4.0.1',
        'Mozilla/5.0 (Windows NT 6.1; rv:2.0.1) Gecko/20100101 Firefox/4.0.1',
        'Opera/9.80 (Macintosh; Intel Mac OS X 10.6.8; U; en) Presto/2.8.131 Version/11.11',
        'Opera/9.80 (Windows NT 6.1; U; en) Presto/2.8.131 Version/11.11',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_7_0) AppleWebKit/535.11 (KHTML, like Gecko) Chrome/17.0.963.56 Safari/535.11',
        'Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.1; Maxthon 2.0)',
        'Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.1; Trident/4.0; SE 2.X MetaSr 1.0; SE 2.X MetaSr 1.0; .NET CLR 2.0.50727; SE 2.X MetaSr 1.0)',
        'Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.1; 360SE)',
        'Mozilla/5.0 (iPhone; U; CPU iPhone OS 4_3_3 like Mac OS X; en-us) AppleWebKit/533.17.9 (KHTML, like Gecko) Version/5.0.2 Mobile/8J2 Safari/6533.18.5',
        'Mozilla/5.0 (Linux; U; Android 3.0; en-us; Xoom Build/HRI39) AppleWebKit/534.13 (KHTML, like Gecko) Version/4.0 Safari/534.13',
        'MQQBrowser/26 Mozilla/5.0 (Linux; U; Android 2.3.7; zh-cn; MB200 Build/GRJ22; CyanogenMod-7) AppleWebKit/533.1 (KHTML, like Gecko) Version/4.0 Mobile Safari/533.1',
        'Mozilla/5.0 (iPad; U; CPU OS 4_3_3 like Mac OS X; en-us) AppleWebKit/533.17.9 (KHTML, like Gecko) Version/5.0.2 Mobile/8J2 Safari/6533.18.5',
        'Mozilla/5.0 (iPod; U; CPU iPhone OS 4_3_3 like Mac OS X; en-us) AppleWebKit/533.17.9 (KHTML, like Gecko) Version/5.0.2 Mobile/8J2 Safari/6533.18.5',
        'Mozilla/5.0 (Linux; Android 4.1.1; Nexus 7 Build/JRO03D) AppleWebKit/535.19 (KHTML, like Gecko) Chrome/18.0.1025.166 Safari/535.19',
        'Mozilla/5.0 (Linux; U; Android 2.3.6; zh-cn; GT-S5660 Build/GINGERBREAD) AppleWebKit/533.1 (KHTML, like Gecko) Version/4.0 Mobile Safari/533.1 MicroMessenger/4.5.255',
        'Mozilla/5.0 (Linux; U; Android 2.2.1; zh-cn; HTC_Wildfire_A3333 Build/FRG83D) AppleWebKit/533.1 (KHTML, like Gecko) Version/4.0 Mobile Safari/533.1',
        'Mozilla/5.0 (compatible; MSIE 10.0; Windows Phone 8.0; Trident/6.0; IEMobile/10.0; ARM; Touch; NOKIA; Lumia 920)',
        'Mozilla/5.0 (iPhone; CPU iPhone OS 5_0 like Mac OS X) AppleWebKit/534.46 (KHTML, like Gecko) Version/5.1 Mobile/9A334 Safari/7534.48.3',
        'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/535.11 (KHTML, like Gecko) Ubuntu/11.10 Chromium/27.0.1453.93 Chrome/27.0.1453.93 Safari/537.36',
        'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/535.11 (KHTML, like Gecko) Ubuntu/11.10 Chromium/27.0.1453.93 Chrome/27.0.1453.93 Safari/537.36',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.10; rv:34.0) Gecko/20100101 Firefox/34.0',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_7_2) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/27.0.1453.93 Safari/537.36',
    );

    //初始化类
    public static function init()
    {
    }

    //提供登陆方法,返回请求的头信息 和 相应的头信息
    public static function login()
    {
        $dbcom = new medoo(array(
            'database_type' => 'mysql',
            'database_name' => 'combusiness',
            'server' => '192.168.8.189',
            'username' => 'gongchang',
            'password' => 'gongchang123',
            'port' => 3306,
            'charset' => 'utf8',
            'option' => array(PDO::ATTR_CASE => PDO::CASE_NATURAL, PDO::ATTR_PERSISTENT => true)
        ));
        $today = strtotime(date('Y-m-d'));

        //先取一条可用用户 条件 状态可用,用户类别 企查查  没有登录过  最后登录时间不是当天
        $userinfo = $dbcom->get("cj_user", array('uid', 'username', 'password', 'usertype', 'state', 'nums', 'logintime', 'requestheader', 'responseheader'), array("AND" => ['nums[<]' => 10, 'state' => 1, 'usertype' => 1, 'logintime[!]' => $today], "ORDER" => "nums ASC"));

        $agent = array_rand(self::$useragent);
        $login_url = 'http://www.qichacha.com/index_validateCode?verifyName=verifyLogin';
        $check_code = '';

        if (empty($userinfo)) {
            $dbcom->clear();
            return false;
        }

        if (!empty($userinfo['responseheader'])) {
            $res = $dbcom->update('cj_user', array('nums[+]' => 1, 'logintime' => $today), array("AND" => ['uid' => $userinfo['uid']]));
            $dbcom->clear();
            return $userinfo;
        }


        $curl = new \Curl\Curl();
        $curl->setUserAgent($agent);

        //获取验证码
        $curl->download($login_url, 'tmp/' . $userinfo['username'] . '.jpg');

        if ($curl->error) {
            echo 'Error1: ' . $curl->errorCode . ': ' . $curl->errorMessage;
            $curl->close();
            $dbcom->clear();
            return false;
        } else {
            $check_code = CheckCode::dama('tmp/' . $userinfo['username'] . '.jpg');
            echo $check_code;
            if (empty($check_code)) {
                $curl->close();
                $dbcom->clear();
                return false;
            }
        }

        $curl->setCookie('PHPSESSID', $curl->getCookie('PHPSESSID'));
        $curl->setCookie('SERVERID', $curl->getCookie('SERVERID'));
        $curl->setCookie('think_language', $curl->getCookie('think_language'));

        //登陆
        $curl->post('http://www.qichacha.com/global_user_loginaction', array(
            'name' => $userinfo['username'],
            'pswd' => $userinfo['password'],
            'verifyLogin' => $check_code,
        ));

        if ($curl->error) {
            echo 'Error2: ' . $curl->errorCode . ': ' . $curl->errorMessage;
            $curl->close();
            $dbcom->clear();
            return false;
        } else {
            $res = json_decode($curl->response, true);
            $code = $res['success'];
            if (!$code) {
                $curl->close();
                $dbcom->clear();
                return false;
            }

            $res = $dbcom->update('cj_user', array('nums[+]' => 1, 'logintime' => $today, 'requestheader' => json_encode($curl->requestHeaders->toArray()), 'responseheader' => json_encode($curl->responseHeaders->toArray()), 'usertype' => 1), array("AND" => ['uid' => $userinfo['uid']]));

            if ($res) {
                $userinfo['logintime'] = $today;
                $userinfo['requestheader'] = json_encode($curl->requestHeaders->toArray());
                $userinfo['responseheader'] = json_encode($curl->responseHeaders->toArray());
                $curl->close();
                $dbcom->clear();
                return $userinfo;
            } else {
                $curl->close();
                $dbcom->clear();
                return false;
            }
        }
    }
}