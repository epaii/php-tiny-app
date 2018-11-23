<?php
/**
 * Created by PhpStorm.
 * User: mrren
 * Date: 2018/11/9
 * Time: 9:41 AM
 */

namespace epii\server;


class Args
{

    private static $configs = [];

    public function __construct()
    {

    }

    public static function setConfig($config, $value = null)
    {
        if (is_array($config))
            self::$configs = array_merge(self::$configs, $config);
        else if (is_string($config)) {
            self::$configs[$config] = $value;
        }
    }

    public static function setValue($config, $value = null)
    {
        self::setConfig($config, $value);
    }


    public static function params($offset)
    {
        return isset($_REQUEST[$offset]) ? $_REQUEST[$offset] : (isset($_SESSION[$offset]) ? $_SESSION[$offset] : (isset(self::$configs[$offset]) ? self::$configs[$offset] : null));
    }

    public static function val($key)
    {
        return self::params($key);
    }

    public static function getConfigVal($key = NULL)
    {
        if (is_null($key)) {
            return self::$configs;
        } else if (isset(self::$configs[$key])) {
            return self::$configs[$key];
        }
        return null;
    }

    /**
     * 获取命令行参数值
     * @param integer|NULL $index
     * @return array|string|NULL
     */
    public static function getVal($index = NULL)
    {
        if (is_null($index)) {
            return $_GET;
        } else if (isset($_GET[$index])) {
            return $_GET[$index];
        }
        return null;
    }

    /**
     * 获取命令行参数值
     * @param integer|NULL $index
     * @return array|string|NULL
     */
    public static function postVal($index = NULL)
    {
        if (is_null($index)) {
            return $_POST;
        } else if (isset($_POST[$index])) {
            return $_POST[$index];
        }
        return null;
    }

    /**
     * 获取命令行参数值
     * @param integer|NULL $index
     * @return array|string|NULL
     */
    public static function cookieVal($index = NULL)
    {
        if (is_null($index)) {
            return $_COOKIE;
        } else if (isset($_COOKIE[$index])) {
            return $_COOKIE[$index];
        }
        return null;
    }

    /**
     * 获取命令行参数值
     * @param integer|NULL $index
     * @return array|string|NULL
     */
    public static function sessionVal($index = NULL)
    {
        if (is_null($index)) {
            return $_SESSION;
        } else if (isset($_SESSION[$index])) {
            return $_SESSION[$index];
        }
        return null;
    }


}