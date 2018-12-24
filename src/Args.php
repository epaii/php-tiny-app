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

    // store options
    private static $optsArr = [];
    // store args
    private static $argsArr = [];
    // 是否解析过
    private static $isParse = false;

    private static $keysForArgValues = [];

    private static $configs = [];

    private static $iscli = null;

    public static function is_cli()
    {
        if (self::$iscli === null) {
            self::$iscli = preg_match("/cli/i", php_sapi_name()) ? true : false;
        }
        return self::$iscli;
    }


    public static function cli_parse()
    {
        if (self::is_cli() && !self::$isParse) {
            self::parseArgs();
        }
    }


    /**
     * 获取选项值
     * @param string|NULL $opt
     * @return array|string|NULL
     */
    public static function optVal($opt = NULL)
    {


        if (is_null($opt)) {
            return self::$optsArr;
        } else if (isset(self::$optsArr[$opt])) {
            return self::$optsArr[$opt];
        }
        return null;
    }


    /**
     * 获取命令行参数值
     * @param integer|NULL $index
     * @return array|string|NULL
     */
    public static function argVal($index = NULL)
    {


        if (is_null($index)) {
            return self::$argsArr;
        } else if (isset(self::$argsArr[$index])) {
            return self::$argsArr[$index];
        }
        return null;
    }


    private function __construct()
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

        if (self::is_cli()) {

            if (isset(self::$argsArr[$offset])) {
                return self::$argsArr[$offset];
            } else if (isset(self::$optsArr[$offset])) {
                return self::$optsArr[$offset];
            } else if (isset(self::$configs[$offset])) {
                return self::$configs[$offset];
            }
            return null;
        }

        return isset($_REQUEST[$offset]) ? $_REQUEST[$offset] : (isset($_SESSION[$offset]) ? $_SESSION[$offset] : (isset(self::$configs[$offset]) ? self::$configs[$offset] : null));
    }

    public static function val($key)
    {
        return self::params($key);
    }

    public static function configVal($key = NULL)
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


    /**
     * 是否是 -s 形式的短选项
     * @param string $opt
     * @return string|boolean 返回短选项名
     */
    private static function isShortOptions($opt)
    {
        if (preg_match('/^\-([a-zA-Z0-9])$/', $opt, $matchs)) {
            return $matchs[1];
        }
        return false;
    }

    /**
     * 是否是 -svalue 形式的短选项
     * @param string $opt
     * @return array|boolean 返回短选项名以及选项值
     */
    private static function isShortOptionsWithValue($opt)
    {
        if (preg_match('/^\-([a-zA-Z0-9])(\S+)$/', $opt, $matchs)) {
            return [$matchs[1], $matchs[2]];
        }
        return false;
    }

    /**
     * 是否是 --longopts 形式的长选项
     * @param string $opt
     * @return string|boolean 返回长选项名
     */
    private static function isLongOptions($opt)
    {
        if (preg_match('/^\-\-([a-zA-Z0-9\-_]{2,})$/', $opt, $matchs)) {
            return $matchs[1];
        }
        return false;
    }

    /**
     * 是否是 --longopts=value 形式的长选项
     * @param string $opt
     * @return array|boolean 返回长选项名及选项值
     */
    private static function isLongOptionsWithValue($opt)
    {
        if (preg_match('/^\-\-([a-zA-Z0-9\-_]{2,})(?:\=(.*?))$/', $opt, $matchs)) {
            return [$matchs[1], $matchs[2]];
        }
        return false;
    }

    /**
     * 是否是命令行参数
     * @param string $value
     * @return boolean
     */
    private static function isArg($value)
    {
        return !preg_match('/^\-/', $value);
    }

    /**
     * 解析命令行参数
     *
     */
    public final static function parseArgs()
    {
        global $argv;
        if (!self::$isParse) {
            // index start from one
            $index = 1;
            $length = count($argv);
            $args_values = [];
            while ($index < $length) {
                // current value
                $curVal = $argv[$index];
                // check, short or long options
                if (($key = self::isShortOptions($curVal)) || ($key = self::isLongOptions($curVal))) {
                    // go ahead
                    $index++;
                    if (isset($argv[$index]) && self::isArg($argv[$index])) {
                        self::$optsArr[$key] = $argv[$index];
                    } else {
                        self::$optsArr[$key] = true;
                        // back away
                        $index--;
                    }
                } // check, short or long options with value
                else if (($key = self::isShortOptionsWithValue($curVal))
                    || ($key = self::isLongOptionsWithValue($curVal))
                ) {
                    self::$optsArr[$key[0]] = $key[1];
                } // args
                else if (self::isArg($curVal)) {
                    $args_values[] = $curVal;
                }
                // incr index
                $index++;
            }

            self::$argsArr = $args_values;

            self::$isParse = true;
        }

    }


    public static function setKeysForArgValues($keys)
    {

        if (!self::$keysForArgValues) {

            self::$keysForArgValues = $keys;
            $args_values = self::$argsArr;
            self::$argsArr = [];
            for ($_i = 0; $_i < count(self::$keysForArgValues); $_i++) {
                self::$argsArr[self::$keysForArgValues[$_i]] = isset($args_values[$_i]) ? $args_values[$_i] : null;
            }

        }
    }


}