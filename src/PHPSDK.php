<?php

namespace Weirin\Kxp;

/**
 *
 *
 *	二维码平台PHP-SDK, 官方API
 *  @author  Lcn <378107001@qq.com>
 *  @version 1.0
 *
 *  全局返回码说明如下
 *  -1      系统繁忙，此时请开发者稍候再试
 *  0      请求成功
 *  40001  获取access_token时AppSecret错误,请开发者认真比对AppSecret的正确性
 *  40002  不合法的access_token，请开发者认真比对access_token的有效性（如是否过期）
 *
 *  二维码验证
 *  40003   缺少f_user_id
 *  40004   缺少token
 *  40005   二维码不存在
 *  40006   该优惠券已被商家发送过
 *  40007   内部错误，修改二维码状态失败
 *
 * Class PHPSDK
 * @package Kxp
 */
class PHPSDK
{

    const BASE_URL = 'http://api.hsh1.cn';
    const BASE_URL_TEST = 'http://api.haiqiyu.com';


    private $token;
    private $appid;
    private $appsecret;
    private $access_token;
    public  $debug =  false;
    private $_logcallback;
    private $baseUrl;
    public $testMode = false;


    /**
     * @param $options
     */
    public function __construct($options)
    {
        $this->token = isset($options['token']) ? $options['token'] : '';
        $this->appid = isset($options['appid']) ? $options['appid'] : '';
        $this->appsecret = isset($options['appsecret']) ? $options['appsecret'] : '';
        $this->debug = isset($options['debug'])?$options['debug'] : false;
        $this->_logcallback = isset($options['logcallback']) ? $options['logcallback'] : false;
        $this->testMode = isset($options['test_mode']) ? $options['test_mode'] : false;

        if ($this->testMode) {
            $this->baseUrl = self::BASE_URL_TEST;
        } else {
            $this->baseUrl = self::BASE_URL;
        }
    }

    /**
     * 签名验证
     * @return bool
     */
    private function checkSignature()
    {
        $signature = isset($_GET["signature"])?$_GET["signature"]:'';
        $timestamp = isset($_GET["timestamp"])?$_GET["timestamp"]:'';
        $nonce = isset($_GET["nonce"])?$_GET["nonce"]:'';

        $token = $this->token;
        $tmpArr = array($token, $timestamp, $nonce);
        sort($tmpArr, SORT_STRING);
        $tmpStr = implode( $tmpArr );
        $tmpStr = sha1( $tmpStr );

        if( $tmpStr == $signature ){
            return true;
        }else{
            return false;
        }
    }

    /**
     * 二维码系统签名验证
     *
     * @return bool
     */
    public function valid()
    {
        return $this->checkSignature();
    }

    /**
     * 验证二维码合法性
     * @param int $fUserId 扫码用户的ID
     * @param string $token 二维码token
     * @return mixed
     */
    public function checkQrcode($fUserId, $token)
    {
        if (!$this->access_token && !$this->checkAuth()) {
           return false;
        }

        $data = array(
            'f_user_id' => $fUserId,
            'token' => $token,
        );

        $result = $this->safeHttpPost($this->baseUrl . '/check-qrcode?', $data);
        return json_decode($result, true);
    }

    /**
     * 设置二维码已使用，避免二维码被再次使用
     * @param int $fUserId 扫码用户的ID
     * @param string $token 二维码token
     * @return mixed
     */
    public function setCouponSent($fUserId, $token)
    {
        if (!$this->access_token && !$this->checkAuth()) {
            return false;
        }

        $data = array(
            'f_user_id' => $fUserId,
            'token' => $token,
        );

        $result = $this->safeHttpPost($this->baseUrl . '/set-coupon-sent?', $data);
        return json_decode($result, true);
    }


    /**
     * 删除验证数据
     */
    public function resetAuth()
    {
        $this->access_token = '';
    }


    /**
     * 通用auth验证方法，暂时仅用于菜单更新操作
     * @return bool|mixed
     */
    public function checkAuth($forceRefresh = false)
    {
        $result = $this->getAccessToken($forceRefresh);
        if($result){
            $this->access_token = $result;
            return  $this->access_token;
        }
        return false;
    }

    /**
     * 获取文件中的AccessToken
     * @return mixed
     */
    private function getAccessToken($forceRefresh = false)
    {
        $this->access_token = AccessToken::get($this->baseUrl, $this->appid, $this->appsecret, $forceRefresh);
        return $this->access_token;
    }

    /**
     * 返回对应商家的唯一AppID
     * @return string
     */
    public function getAppID()
    {
        return $this->appid;
    }

    /**
     * 封装一个较为稳妥的Http get请求接口
     * 说明: 可以自动纠正一次401错误(access_token无效)
     * @param $urlHeader
     * @return bool|mixed
     */
    public function safeHttpGet($urlHeader)
    {
        $result = Http::get($urlHeader . 'access_token=' . $this->access_token);
        if ($result) {
            $json = json_decode($result);
            if (isset($json->errcode) && $json->errcode == 40002) {
                if ($this->checkAuth(true)) {
                    $result = Http::get($urlHeader . 'access_token=' . $this->access_token);
                }
            }
        }
        return $result;
    }

    /**
     * 封装一个较为稳妥的Http post请求接口
     * 说明: 可以自动纠正一次401错误(access_token无效)
     * @param $urlHeader
     * @param $param
     * @return bool|mixed
     */
    public function safeHttpPost($urlHeader, $param)
    {
        $result = Http::post($urlHeader . 'access_token=' . $this->access_token, $param);

        if ($result) {
            $json = json_decode($result);
            if(isset($json->errcode) && $json->errcode == 40002)
            {
                if ($this->checkAuth(true))
                {
                    $result = Http::post($urlHeader . 'access_token=' . $this->access_token, $param);
                }
            }
        }

        return $result;
    }
}
