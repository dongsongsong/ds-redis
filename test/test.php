<?php
/**
 * Created by PhpStorm.
 * User: dongsong
 * Date: 2019/3/7
 * Time: 10:43
 */
require_once '../vendor/autoload.php';
use Redis\Redis;
$redis = new Redis([
    'REDIS_HOST'=>'127.0.0.1',
    'REDIS_PORT'=>6379,
    'DATA_CACHE_TIMEOUT'=>5,//链接服务器超时时间
    'DATA_CACHE_PREFIX'=>'test:',//key前缀
    'DATA_CACHE_TIME'=>3600*24*7//缓存过期时间
]);

print_r($redis);