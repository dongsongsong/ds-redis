<?php
/**
 * Created by PhpStorm.
 * User: dongsong
 * Date: 2019/3/7
 * Time: 10:38
 */
namespace Redis;
/**
 * Redis缓存驱动
 * 要求安装phpredis扩展：https://github.com/nicolasff/phpredis
 */
class Redis  {
    /**
     * 架构函数
     * @param array $options 缓存参数
     * @access public
     */
    public function __construct($options = array()) {
        $config = $options;
        $options = array_merge(array (
            'host'          => $config['REDIS_HOST'] ? : '127.0.0.1',
            'port'          => $config['REDIS_PORT'] ? : 6379,
            'timeout'       => $config['DATA_CACHE_TIMEOUT'] ? : false,
            'DATA_CACHE_PREFIX'=>$config['DATA_CACHE_PREFIX'] ? : false,
            'persistent'    => false,
        ),$options);

        $this->options =  $options;
        $this->options['expire'] =  isset($options['expire'])?  $options['expire']  :   $config['DATA_CACHE_TIME'];
        $this->options['prefix'] =  isset($options['prefix'])?  $options['prefix']  :   $config['DATA_CACHE_PREFIX'];
        $this->options['length'] =  isset($options['length'])?  $options['length']  :   0;
        $func = $options['persistent'] ? 'pconnect' : 'connect';
        $this->handler  = new \Redis;
        $options['timeout'] === false ?
            $this->handler->$func($options['host'], $options['port']) :
            $this->handler->$func($options['host'], $options['port'], $options['timeout']);
    }
    public function psubscribe($patterns = array(), $callback)
    {
        $this->handler->psubscribe($patterns, $callback);
    }
    public function setOption()
    {
        $this->handler->setOption(\Redis::OPT_READ_TIMEOUT, -1);
    }


    /**
     * 读取缓存
     * @access public
     * @param string $name 缓存变量名
     * @return mixed
     */
    public function get($name) {
        $value = $this->handler->get($this->options['prefix'].$name);
        $jsonData  = json_decode( $value, true );
        return ($jsonData === NULL) ? $value : $jsonData;	//检测是否为JSON数据 true 返回JSON解析数组, false返回源数据
    }
    public function ttl($name) {
        $ttl = $this->handler->ttl($this->options['prefix'].$name);
        return $ttl;
    }

    /**
     * 写入缓存
     * @access public
     * @param string $name 缓存变量名
     * @param mixed $value  存储数据
     * @param integer $expire  有效时间（秒）
     * @return boolean
     */
    public function set($name, $value, $expire = null) {
        if(is_null($expire)) {
            $expire  =  $this->options['expire'];
        }
        $name   =   $this->options['prefix'].$name;
        //对数组/对象数据进行缓存处理，保证数据完整性
        $value  =  (is_object($value) || is_array($value)) ? json_encode($value,JSON_UNESCAPED_UNICODE) : $value;
        if(is_int($expire) && $expire) {
            $result = $this->handler->setex($name, $expire, $value);
        }else{
            $result = $this->handler->set($name, $value);
        }
        if($result && $this->options['length']>0) {
            // 记录缓存队列
            $this->queue($name);
        }
        return $result;
    }

    public function keys($key)
    {
        $key   =   $this->options['prefix'].$key;
        return $this->handler->keys($key);
    }

    public function delete($key)
    {
        $key   =   $this->options['prefix'].$key;
        return $this->handler->delete($key);
    }

    //返回队列的长度
    public function llen($key){
        return $this->handler->lLen($key);
    }
    //数据插入队列
    public function rpush($key,$value){
        return $this->handler->rPush($key,$value);
    }
    //消费队列
    public function lpop($key){
        return $this->handler->lPop($key);
    }

    public function get_all($key){
        return $this->handler->keys($key);
    }
    /**
     * 删除缓存
     * @access public
     * @param string $name 缓存变量名
     * @return boolean
     */
    public function rm($name) {
        return $this->handler->delete($this->options['prefix'].$name);
    }

    /**
     * 清除缓存
     * @access public
     * @return boolean
     */
    public function clear() {
        return $this->handler->flushDB();
    }

    public function incr($key){
        return $this->handler->incr($key);
    }

}

