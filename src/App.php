<?php
namespace epii\server;


use epii\server\i\IArgsKeys;
use epii\server\i\IRun;


/**
 * Created by PhpStorm.
 * User: mrren
 * Date: 2018/11/8
 * Time: 2:32 PM
 */
class App
{


    private $init_fun = [];

    public static function getAppRoot()
    {
        return pathinfo($_SERVER["PHP_SELF"], PATHINFO_DIRNAME);
    }

    private $base_namespace = "app";

    public function setBaseNameSpace($base_name)
    {
        $this->base_namespace = trim($base_name, "\\");
        return $this;
    }

    public function __construct($configOrFilePath = null)
    {

        Args::cli_parse();

        if ($configOrFilePath && file_exists($config_file = $configOrFilePath)) {
            $config = json_decode(file_get_contents($configOrFilePath), true);
        } else
            $config = [];

        Args::setConfig($config);


    }

    private function init_one($irun)
    {
        $this->init_fun[] = $irun;
    }

    public function init(...$Iruns)
    {
        if (count($Iruns) > 0) {
            foreach ($Iruns as $irun) {
                if (!is_array($irun)) {
                    $this->init_one($irun);
                } else {
                    array_map(function ($c) {
                        $this->init_one($c);
                    }, $irun);
                }
            }
        }
        return $this;
    }


    public function run($app = null)
    {

        if ($app === null) {

            if ($app = Args::params("a")) {

            } else if ($app = Args::params("app")) {

            } else {
                $app = "index";
            }
            if ($app) {
                $config = Args::configVal("app");

                if (isset($config[$app])) {
                    $app = $config[$app];

                } else {
                    $app = str_replace(".", "\\", $app);
                }
            }
        }

        $m = "index";

        if (is_string($app)) {
            if (stripos($app, "@") > 0) {

                list($app, $m) = explode("@", $app);
            }
        }


        if (is_string($app) && (class_exists($app) || class_exists($app = $this->base_namespace . "\\" . $app))) {
            $run = new $app();

            if ($run instanceof IArgsKeys) {
                Args::setKeysForArgValues($run->keysForArgValues());

            }

            $this->beforRun();
            if (method_exists($run, "init")) {
                $run->init();
            }
            if (method_exists($run, $m)) {
                return $run->$m();
            } else {
                if ($run instanceof IRun) {
                    return $run->run();
                } elseif (method_exists($run, "__call")) {
                    return $run->$m();
                }
            }
        } else {
            $this->beforRun();
            return $this->init_one_run($app);
        }

        return null;
    }

    private function beforRun()
    {

        array_map(function ($irun) {

            $this->init_one_run($irun);
        }, $this->init_fun);

    }

    private function init_one_run($irun)
    {
        if (is_string($irun) && class_exists($irun)) {
            $tmp = new $irun();
            if ($tmp instanceof IRun) {
                return $tmp->run();
            }
        } else if (is_callable($irun)) {
            return $irun();
        }
        return null;
    }
}