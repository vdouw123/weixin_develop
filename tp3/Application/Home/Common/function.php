<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/11/6 0006
 * Time: 09:59
 */

/**
 * 签名算法
 * @param $len 获取多少位随机数
 * @param null $chars 随机数产生源
 * @return string
 */
function getRandCode($len = 16)
{
    $array = array('a','b','c','d','e','f','g','h','j','k','m','n','p','q','r','s','t','u','v','w','x','y','z','A','B','C','D','E','F','G','H','J','K','M','N','P','Q','R','S','T','U','V','W','X','Y','2','3','4','5','6','7','8','9');
    $str = '';
    for ($i = 0; $i <= $len; $i++) {
        $str .= $array[rand(0, count($array) - 1)];
    }
    return $str;
}

/**
 * 获取jsapi_ticket票据
 * jsapi_ticket是公众号用于调用微信JS接口的临时票据
 * @return mixed
 */
function getJsApiTicket()
{
    if ($_SESSION['jsapi_ticket_expire_time'] > time() && $_SESSION['jsapi_ticket']) {
        $jsapi_ticket = $_SESSION['jsapi_ticket'];
    } else {
        $access_token = getWxTestAccessToken();
        $url = 'https://api.weixin.qq.com/cgi-bin/ticket/getticket?access_token=' . $access_token . '&type=jsapi';
        $res = http_curl($url);
        $jsapi_ticket = $res['ticket'];
        $_SESSION['jsapi_ticket'] = $jsapi_ticket;
        $_SESSION['jsapi_ticket_expire_time'] = time() + 7200;
    }
    return $jsapi_ticket;
}

/**
 * 万能的curl请求
 * @param $url 接口url
 * @param string $type 请求类型
 * @param string $res 返回数据类型
 * @param string $arr 请求参数
 * @return mixed|string
 */
function http_curl($url, $type = "get", $res = "json", $arr = "")
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);  //执行HTTP请求
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);  //若不为1，curl_exec将直接输入结果而不能保存到变量
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);  //有时漏了ssl_verifypeer这条，一直返回false;
    if ($type == "post") {
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $arr);
    }
    $output = curl_exec($ch);
    curl_close($ch);
    if ($res == "json") {
        if (curl_errno($ch)) {
            return curl_error($ch);
        } else {
            return json_decode($output, true);  //转换成数组
        }
    }
}

/**
 * 获取微信的access_token
 * @return mixed
 */
function getWxAccessToken()
{
    if ($_SESSION["vdouw_weixin_access_token"] && $_SESSION["expire_time"] > time()) {
        return $_SESSION["vdouw_weixin_access_token"];
    } else {
        $appid = C('AppID');
        $appsecret = C('AppSecret');
        $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=" . $appid . "&secret=" . $appsecret;
        $res = http_curl($url, "get", "json");
        $access_token = $res["access_token"];
        $_SESSION["vdouw_weixin_access_token"] = $access_token;
        $_SESSION["expire_time"] = time() + 7200;
        return $access_token;
    }
}

/**
 * 获取微信公众测试号的access_token
 * @return mixed
 */
function getWxTestAccessToken()
{
    if ($_SESSION["vdouw_weixin_access_token_test"] && $_SESSION["expire_time"] > time()) {
        return $_SESSION["vdouw_weixin_access_token_test"];
    } else {
        $appid = C('AppIDTest');
        $appsecret = C('AppSecretTest');
        $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=" . $appid . "&secret=" . $appsecret;
        $res = http_curl($url, "get", "json");
        $access_token = $res["access_token"];
        $_SESSION["access_token_test"] = $access_token;
        $_SESSION["expire_time"] = time() + 7200;
        return $access_token;
    }
}


/**
 * 订阅
 * @param $postObj
 */
function subscribe($postObj)
{
    $toUser = $postObj->FromUserName;
    $fromUser = $postObj->ToUserName;
    $time = time();
    $MsgType = 'text';
    $content = '欢迎你，' . $toUser . '关注我的公众号' . $fromUser;
    $template = '<xml><ToUserName><![CDATA[%s]]></ToUserName><FromUserName><![CDATA[%s]]></FromUserName><CreateTime>%s</CreateTime><MsgType><![CDATA[%s]]></MsgType><Content><![CDATA[%s]]></Content></xml>';
    $info = sprintf($template, $toUser, $fromUser, $time, $MsgType, $content);
    echo $info;
}

/**
 * 自动回复（纯文本）
 * @param $postObj
 * @param $content
 */
function replyOnlyText($postObj, $content)
{
    $template = '<xml><ToUserName><![CDATA[%s]]></ToUserName><FromUserName><![CDATA[%s]]></FromUserName><CreateTime>%s</CreateTime><MsgType><![CDATA[%s]]></MsgType><Content><![CDATA[%s]]></Content></xml>';
    $toUser = $postObj->FromUserName;
    $fromUser = $postObj->ToUserName;
    $createTime = time();
    $MsgType = 'text';
    $info = sprintf($template, $toUser, $fromUser, $createTime, $MsgType, $content);
    echo $info;
}

/**
 * 自动回复（图文）
 * @param $postObj
 * @param $arr
 */
function replyPicAndText($postObj, $arr)
{
    $toUser = $postObj->FromUserName;
    $fromUser = $postObj->ToUserName;
    $createTime = time();
    $MsgType = 'news';
    $template = '<xml><ToUserName><![CDATA[%s]]></ToUserName><FromUserName><![CDATA[%s]]></FromUserName><CreateTime>%s</CreateTime><MsgType><![CDATA[%s]]></MsgType><ArticleCount>' . count($arr) . '</ArticleCount><Articles>';
    foreach ($arr as $key => $value) {
        $template .= '<item><Title><![CDATA[' . $value["title"] . ']]></Title><Description><![CDATA[' . $value["description"] . ']]></Description><PicUrl><![CDATA[' . $value["picUrl"] . ']]></PicUrl><Url><![CDATA[' . $value["url"] . ']]></Url></item>';
    }
    $template .= '</Articles></xml>';
    $info = sprintf($template, $toUser, $fromUser, $createTime, $MsgType);
    echo $info;
}



