<?php
/**
 * Created by PhpStorm.
 * User: mrren
 * Date: 2018/11/9
 * Time: 9:41 AM
 */

namespace epii\server;


class Args implements \ArrayAccess
{

    private static $configs = [];

    public function __construct()
    {

    }

    public static function setConfig($config)
    {
        self::$configs = $config;
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
    public  static function getVal($index = NULL)
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


    /**
     * Whether a offset exists
     * @link http://php.net/manual/en/arrayaccess.offsetexists.php
     * @param mixed $offset <p>
     * An offset to check for.
     * </p>
     * @return boolean true on success or false on failure.
     * </p>
     * <p>
     * The return value will be casted to boolean if non-boolean was returned.
     * @since 5.0.0
     */
    public  function offsetExists($offset)
    {
        // TODO: Implement offsetExists() method.
        return isset($_REQUEST[$offset]) || $_SESSION[$offset];
    }

    /**
     * Offset to retrieve
     * @link http://php.net/manual/en/arrayaccess.offsetget.php
     * @param mixed $offset <p>
     * The offset to retrieve.
     * </p>
     * @return mixed Can return all value types.
     * @since 5.0.0
     */
    public function offsetGet($offset)
    {
        // TODO: Implement offsetGet() method.

        return  self::val($offset);
    }

    /**
     * Offset to set
     * @link http://php.net/manual/en/arrayaccess.offsetset.php
     * @param mixed $offset <p>
     * The offset to assign the value to.
     * </p>
     * @param mixed $value <p>
     * The value to set.
     * </p>
     * @return void
     * @since 5.0.0
     */
    public function offsetSet($offset, $value)
    {
        // TODO: Implement offsetSet() method.
        self::$configs[$offset] = $value;
    }

    /**
     * Offset to unset
     * @link http://php.net/manual/en/arrayaccess.offsetunset.php
     * @param mixed $offset <p>
     * The offset to unset.
     * </p>
     * @return void
     * @since 5.0.0
     */
    public function offsetUnset($offset)
    {
        // TODO: Implement offsetUnset() method.
        unset($_GET[$offset]);
        unset($_POST[$offset]);
        unset($_COOKIE[$offset]);
        unset($_SESSION[$offset]);
        unset(self::$configs[$offset]);

    }


}