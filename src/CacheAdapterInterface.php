<?php

namespace Weirin\Kxp;

/**
 * Interface CacheAdapterInterface
 * @package Wechat
 */
interface CacheAdapterInterface
{
    /**
     * @param $key
     */
    public function exists($key);

    /**
     * @param $key
     */
    public function get($key);

    /**
     * @param $key
     * @param $value
     * @return mixed
     */
    public function set($key, $value);
}
