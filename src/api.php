<?php

/**
 * Created by PhpStorm.
 * User: mrren
 * Date: 2019/2/17
 * Time: 6:29 PM
 */

namespace epii\server;


abstract class api
{
    private $is_auth = false;
    private $log_enable = false;
    private $log_enable_password = "";
    protected   function enableLog(bool $enable,$password="")
    {   
        $this->log_enable = $enable;
        $this->log_enable_password=$password;
    }
    private function _log(){
        if($this->log_enable){
            $log_file = Tools::getRuntimeDirectory()."/apilogs";
        
            Tools::mkdir($log_file);
            file_put_contents($log_file."/".date("Ymd").".txt","\n".Tools::get_current_url()."\n".http_build_query(Args::postVal()),FILE_APPEND);
        }
    }
    public function _show_log(){
        if(!$this->log_enable) exit;
        if($this->log_enable_password && (Args::getVal("password")!= $this->log_enable_password))
        {
            return;
        }
        $date = Args::getVal("date");
        if(!$date) $date = date("Ymd");
        $log_file = Tools::getRuntimeDirectory()."/apilogs/".$date.".txt";
        if(file_exists($log_file)){
            if($clear =  Args::getVal("clear")){
              echo   @unlink($log_file);
            }else 
            echo file_get_contents($log_file);
        }
        exit;
    }
    protected function getNoNeedAuth(): array
    {
        return [];
    }
    protected function onAuthFail()
    {
    }

    abstract protected function doAuth(): bool;

    protected function isAuth()
    {
        return $this->is_auth;
    }
    public function init()
    {
        $this->_log();
        $auth_bool = true;
        $no = $this->getNoNeedAuth();
        if (count($no) > 0) {
            $m = \epii\server\App::getInstance()->getRunner()[1];
            if (in_array($m, $no) || ((count($no) == 1) && ($no[0] == "..."))) {
                $auth_bool = false;
            }
        }
        $this->is_auth = $this->doAuth();
        if ($auth_bool && !$this->is_auth) {

            $this->onAuthFail();
            $this->error("授权失败", ["error_type" => "auth", "tip" => "授权失败"]);
        }
    }

    protected function success($data = null, $msg = '', $code = 1, $type = null, array $header = [])
    {
        Response::success($data, $msg, $code, $type, $header);
    }


    protected function error($msg = '', $data = null, $code = 0, $type = null, array $header = [])
    {
        Response::error($msg, $data, $code, $type, $header);
    }
}
