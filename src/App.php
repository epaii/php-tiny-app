<?php

namespace epii\server;

use epii\lock\drivers\FileLock;
use epii\lock\Lock;
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
    private $init_fun_end = [];
    private static $_app = null;
    private $runner_object = null;
    public $runner_name_space_pre = null;
    private $runner_method = null;
    private $name_space_pre = [];
    private $forbid_name_space_pre = [];
    private static $singleton_init_array = [];
    private $blocking_app =[];
    private $blocking_error_handler =null;
    public function defaultApp($app)
    {
        if (!isset($_REQUEST['app'])) {
            $_REQUEST['app'] = $app;
        }
        return $this;
    }

    public static function getAppRoot()
    {
        return pathinfo($_SERVER["PHP_SELF"], PATHINFO_DIRNAME);
    }

    public static function getAppRootDir()
    {
        return pathinfo($_SERVER["SCRIPT_FILENAME"], PATHINFO_DIRNAME);
    }

    public static function getInstance($configOrFilePath = null)
    {
        if (!self::$_app) {
            new static($configOrFilePath);
        }

        return self::$_app;
    }

    public function getBaseNameSpace()
    {
        return $this->name_space_pre;
    }

    public function setBaseNameSpace(...$base_name)
    {
        $this->name_space_pre = array_merge($this->name_space_pre, $base_name);

        return $this;
    }
    public function setBlockingApp(...$apps)
    {
        $this->blocking_app = array_merge($this->blocking_app, $apps);
        return $this;
    }
    public function setBlockingDriver($driver_class_name, $config = array())
    {
        Lock::init($driver_class_name,$config);
        return $this;
    }
    public function setBlockingErrorHandler(callable $blocking_error_handler)
    {
        $this->blocking_error_handler = $blocking_error_handler;
        return $this;
    }
    public function setDisableNameSpace(...$ban_name)
    {
        $this->forbid_name_space_pre = array_merge($this->forbid_name_space_pre, $ban_name);
        return $this;
    }

    public function __construct($configOrFilePath = null)
    {

        self::$_app = $this;

        Args::cli_parse();

        if ($configOrFilePath && file_exists($config_file = $configOrFilePath)) {
            $config = json_decode(file_get_contents($configOrFilePath), true);
        } else if (is_array($configOrFilePath)) {
            $config = $configOrFilePath;
        } else {
            $config = [];
        }

        Args::setConfig($config);

    }

    private function init_one($irun)
    {
        $this->init_fun[] = $irun;
    }

    public function init_unshift($irun)
    {
        array_unshift($this->init_fun, $irun);
        return $this;
    }

    public function init_end($irun)
    {
        $this->init_fun_end[] = $irun;
        return $this;
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

    public function getRunner()
    {
        return [$this->runner_object, $this->runner_method];
    }

    public function run($app = null)
    {

        $html = "";

        if ($app === null || ( is_string($app) && class_exists($app))) {

            $this->beforRun();

            if($app === null)
            {
                if ($app = Args::params("a")) {

                } else if ($app = Args::params("app")) {
    
                } else {
                    $app = "index";
                }
                $req_app = $app;
    
    
                if ($app) {
                    $config = Args::configVal("app");
    
                    if (isset($config[$app])) {
                        $app = $config[$app];
    
                    } else {
                        $app = str_replace(".", "\\", $app);
                        $app = str_replace("/", "@", $app);
                    }
                }
    
                $m = "index";
    
                if (is_string($app)) {
                    if (stripos($app, "@") > 0) {
    
                        list($app, $m) = explode("@", $app);
                    }
                }
    
                $find = false;
    
                $app_o = $app;
    
                $name_list = array_merge(array_unique($this->name_space_pre), ["", "app"]);
    
                foreach ($name_list as $item) {
                    $item = rtrim($item, "\\");
                    if ($item) {
                        $app = $item . "\\" . $app_o;
                    } else {
                        $app = $app_o;
                    }
                    if (class_exists($app)) {
                        $this->runner_name_space_pre = $item;
                        $find = true;
                        break;
                    }
                }
    
                if (!$find) {
                    echo "class wrong!";
                    exit;
                }
                $find = false;
                foreach ($name_list as $item) {
    
                    if ($item && stripos($app, $item . "\\") === 0) {
                        $find = true;
                    }
                }
    
                if (!$find) {
                    echo "class over!";
                    exit;
                }
            }else{
                $m = "run";
            }
            




            if ($this->forbid_name_space_pre) {
                foreach ($this->forbid_name_space_pre as $item) {
                    if (stripos($app, $item) === 0) {
                        echo "class forbid!";
                        exit;
                    }
                }
            }

          
            $run = new $app();
            $this->runner_object = $run;

            if ($run instanceof IArgsKeys) {
                Args::setKeysForArgValues($run->keysForArgValues());
            }

            if (method_exists($run, $m)) {
                $this->runner_method = $m;
            } else {
                if ($run instanceof IRun) {
                    $this->runner_method = "run";
                } elseif (method_exists($run, "__call")) {
                    $this->runner_method = $m;
                }
            }

            $goto_run  = true;   
            $lock  = false;
            if($req_app)
            {
                if(in_array($req_app,$this->blocking_app)){
                    if(!Lock::isInit()){
                        Lock::init(FileLock::class,["lock_dir"=>Tools::getRuntimeDirectory().DIRECTORY_SEPARATOR."lock"]);
                    }
                    $lock =Lock::doLock($req_app);
                    if(!$lock){
                        $goto_run = false;
                        
                        if (method_exists($run, $bef = $this->runner_method."_blocking_error")) {
                            $html = $run->{$bef}();
                        }else{
                            if($this->blocking_error_handler)
                            {
                                $f = $this->blocking_error_handler;
                                $f($req_app);
                            }else{
                                if(Args::is_cli() || Args::isPost() || Args::isAjax()){
                                    Response::error("系统繁忙请重试", ["error_type" => "system_busy", "tip" => "系统繁忙请重试"]);
                                }else{
                                    $html = "system_busy";
                                }
                            }
                           
                        }    
                    } 
                }
            }
             if($goto_run){
                if (method_exists($run, "init")) {
                    $run->init();
                }
                if ($this->runner_method) {
                    $html = $run->{$this->runner_method}();
                }
             }
             if($lock){
                Lock::unLock($req_app);
             }

        } else if(is_callable($app)) {
            $this->runner_object = $app;
            $this->beforRun();

            $html = self::init_one_run($app);
        }

        if ($html) {
            Response::show($html);
        }
        return;
    }

    private function beforRun()
    {

        array_map(function ($irun) {

            self::init_one_run($irun);
        }, array_merge($this->init_fun, $this->init_fun_end));

    }

    private static function init_one_run($irun)
    {
        if (is_string($irun) && class_exists($irun)) {
            self::$singleton_init_array[$irun] = 1;
            $tmp = new $irun();
            if ($tmp instanceof IRun) {
                return $tmp->run();
            }
        } else if (is_callable($irun)) {
            return $irun();
        }
        return null;
    }

    public static function singletonInit(string $classname)
    {
        if (!isset(self::$singleton_init_array[$classname])) {
            self::init_one_run($classname);

        }
    }

}
