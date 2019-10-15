<?php
namespace Aliyun\dangmianfu;
/**
 * 注册SDK自动加载机制
 * @author zhaolicheng <564014151@qq.com>
 * @date 2019-04-28 11:06
 */
spl_autoload_register(function ($class) {
    $filename = getcwd().DIRECTORY_SEPARATOR. str_replace('\\', DIRECTORY_SEPARATOR, $class) . '.php';
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

    static public function &get($type='dangmianfu',$config)
    {
      //  $Test=new \Aliyun\dangmianfu\AlipayPrecreateCodeUrl();exit;
        $index = md5(strtolower($type) . md5(json_encode(self::$config)));
        if (!isset(self::$cache[$index])) {
            $basicName = "\\Aliyun\\{$type}\\AlipayPrecreateCodeUrl";
            $className = "{$basicName}";
            if($type=='dangmianfu') {
                $basicName = "\\Aliyun\\dangmianfu\\AlipayPrecreateCodeUrl";
                $className = "{$basicName}";
            }
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
        !empty($config) && self::$config = array_merge(self::$config, $config);
        return self::$config;
    }
    public  function ceshi2($order_number)
    {
        return $order_number;
    }
}





