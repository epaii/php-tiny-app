<?php

/**
 * Created by PhpStorm.
 * User: mrren
 * Date: 2019/2/17
 * Time: 6:29 PM
 */

namespace epii\server;

use epii\server\traits\auth;

abstract class api
{
    use auth;
    
    private $log_enable = false;
    private $log_enable_password = "1";
    protected   function enableLog(bool $enable,$password="")
    {   
        $this->log_enable = $enable;
        $this->log_enable_password=$password;
    }
    private function _log(){
        if($this->log_enable){
            $log_file = Tools::getRuntimeDirectory()."/apilogs";
        
            Tools::mkdir($log_file);
            file_put_contents($log_file."/".date("Ymd").".txt","\n".Tools::get_current_url()."&".http_build_query(Args::postVal()),FILE_APPEND);
        }
    }
    public function _show_log(){
        if(!$this->log_enable) exit;
        if($this->log_enable_password && (Args::getVal("_show_log")!= $this->log_enable_password))
        {
            return;
        }
        $date = Args::getVal("date");
        if(!$date) $date = date("Ymd");
        $log_file = Tools::getRuntimeDirectory()."/apilogs/".$date.".txt";
        if(file_exists($log_file)){
            if($clear =  Args::getVal("clear")){
              echo   @unlink($log_file);
            }else {
                $list = explode("\n", file_get_contents($log_file));
                echo   "<html><head><script> function copy(text) {
                    var input = document.createElement('input');
                    input.setAttribute('readonly', 'readonly'); // 防止手机上弹出软键盘
                    input.setAttribute('value', text);
                    document.body.appendChild(input);
                    // input.setSelectionRange(0, 9999);
                    input.select();
                    var res = document.execCommand('copy');
                    document.body.removeChild(input);
                    return res;
                }</script></head><body>";
                echo "<div><button onclick='window.location.href=window.location.href+\"&clear=1\"'>清除日志</button></div>";
                foreach($list as $value){
                    if(!trim($value)) continue;
                    echo "<p>{$value} <button onclick='copy(\"{$value}\")'>复制</button></p>";
                }
                echo "</body></html>";
            }
           
        }
        exit;
    }
    abstract protected function doAuth(): bool;

    protected function isAuth()
    {
        return $this->is_auth;
    }
    public function init()
    {
      
        if(Args::params("_show_log")){
            $this->_show_log();
            exit;
        }
        $this->_log();
        if(!$this->authCheck(function(){
            return $this->doAuth();
        })){
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
