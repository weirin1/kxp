<?php

namespace Weirin\Kxp;

/**
 *
 * Class AccessToken
 *
 * 当前实例返回 APP 的 AccessToken 非用户授权的 access token
 *
 * 过期时间 7200 秒
 * 自动管理 access token ， 过期会重新获取.
 *
 * @官方文档说明：
 *   access_token是商家的全局唯一票据，商家调用各接口时都需使用access_token。
 *   开发者需要进行妥善保存。
 *   access_token的存储至少要保留512个字符空间。
 *   access_token的有效期目前为2个小时，需定时刷新，重复获取将导致上次获取的access_token失效。
 *
 *
 * 公众平台的API调用所需的access_token的使用及生成方式说明：
 * 1、为了保密appsecrect，第三方需要一个access_token获取和刷新的中控服务器。
 *    而其他业务逻辑服务器所使用的access_token均来自于该中控服务器，不应该各自去刷新，
 *    否则会造成access_token覆盖而影响业务；
 *
 * 2、目前access_token的有效期通过返回的expire_in来传达，
 *    目前是7200秒之内的值。中控服务器需要根据这个有效时间提前去刷新新access_token。
 *    在刷新过程中，中控服务器对外输出的依然是老access_token，此时公众平台后台会保证在刷新短时间内，
 *    新老access_token都可用，这保证了第三方业务的平滑过渡；
 *
 * 3、access_token的有效时间可能会在未来有调整，所以中控服务器不仅需要内部定时主动刷新，
 *    还需要提供被动刷新access_token的接口，这样便于业务服务器在API调用获知access_token已超时的情况下，可以触发access_token的刷新流程。
 *
 *
 * @package Kxp
 */
class AccessToken
{
    /**
     * 用来保存 Access Token 的唯一标识，禁止修改.
     */
    const STORE_ID = 'KXP_ACCESS_TOKEN';


    /**
     * @param $appId
     * @param $appSecret
     * @param bool $forceRefresh
     * @return string
     */
    public static function get($baseUrl, $appId, $appSecret, $forceRefresh = false)
    {

        $storeId = self::STORE_ID . '_' . $appId;
        // access_token 应该全局存储与更新，
        $data = false;
        if (Cache::exists($storeId)) {
            $data = json_decode(Cache::get($storeId));
        }

        if (!isset($data->access_token) || !isset($data->expire_time)) {
            $data = new \stdClass();
            $data->expire_time = 0;
            $data->access_token = '';
        }

        $time = time();
        //如果当前日期已经超过了过期时间，那么重新获取access token
        if ($time > $data->expire_time || true == $forceRefresh) {

            $ch = curl_init();
            curl_setopt($ch,CURLOPT_URL,  $baseUrl . "/oauth2/token");
            $encodedAuth = base64_encode("{$appId}:{$appSecret}");
            curl_setopt($ch, CURLOPT_HTTPHEADER, array("Authorization : Basic ".$encodedAuth));
            $postStr = 'grant_type=client_credentials&scope=user';
            curl_setopt($ch, CURLOPT_POST,true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postStr);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $res = curl_exec($ch);
            curl_close($ch);
            $res = json_decode($res);
            if (isset($res->access_token)) {
                $data->expire_time = $time + 3600 * 24;
                $data->access_token = $res->access_token;
                Cache::set($storeId, json_encode($data));

                // 跟踪日志 @todo

                return $data->access_token;
            } else {
                // 跟踪日志 @todo
            }

        } else {
            return $data->access_token;
        }
    }
}