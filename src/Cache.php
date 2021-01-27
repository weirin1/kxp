<?php

namespace Weirin\Kxp;

/**
 * Class Cache
 * @package Wechat
 */
class Cache
{
    private static $adapter;

    /**
     * @param CacheAdapterInterface $logAdapter
     */
    public static function setAdapter(CacheAdapterInterface $adapter)
    {
        self::$adapter = $adapter;
    }

    /**
     * @param $msg
     * @return void
     */
    public static function set($key, $value)
    {
        if(self::$adapter instanceof CacheAdapterInterface){
            self::$adapter->set($key, $value);
        }
    }

    /**
     * @param $key
     * @return mixed
     */
    public static function get($key)
    {
        if(self::$adapter instanceof CacheAdapterInterface){
            return self::$adapter->get($key);
        }
    }

    /**
     * @param $key
     * @return mixed
     */
    public static function exists($key)
    {
        if(self::$adapter instanceof CacheAdapterInterface){
            return self::$adapter->exists($key);
        }
    }
}