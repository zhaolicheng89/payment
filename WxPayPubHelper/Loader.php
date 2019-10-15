<?php
namespace WxPayPubHelper;
/**
 * 注册SDK自动加载机制
 * @author zhaolicheng <564014151@qq.com>
 * @date 2019-04-28 11:06
 */
spl_autoload_register(function ($class) {
    $filename = getcwd().DIRECTORY_SEPARATOR. str_replace('\\', DIRECTORY_SEPARATOR, $class) . '.php';
    //print_r($filename);exit;
    file_exists($filename) && require($filename);
});
/*
 * 微信SDK加载器
 * @author zhaolicheng <564014151@qq.com>
 * @date 2019-04-28 11:06
 */
class Loader
{
    /**
     * 配置参数
     * @var array
     */
    static protected $config = array();

    /**
     * 对象缓存
     * @var array
     */
    static protected $cache = array();

    static public function &get($type='Wxpay', $config = array())
    {
        $index = md5(strtolower($type) . md5(json_encode(self::$config)));
        if (!isset(self::$cache[$index])) {
            $basicName = 'WxPayPubHelper\\' . ucfirst(strtolower($type));
            $className = "\\{$basicName}";
            self::$cache[$index] = new $className(self::config($config));
        }
        return self::$cache[$index];
    }
    /**
     * 设置配置参数
     * @param array $config
     * @return array
     */
    static public function config($config = array()){
        return $config;
    }
    public  function ceshi($order_number)
    {
        return $order_number;
    }
}





