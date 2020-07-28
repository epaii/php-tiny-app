<?php

namespace epii\server\traits;

trait auth 
{
    private $is_auth = false;
    protected function getNoNeedAuth(): array
    {
        return [];
    }
    protected function onAuthFail()
    {
    }
    protected function isAuth()
    {
        return $this->is_auth;
    }
    public function authCheck(callable $check):bool
    {
        
        $auth_bool = true;
        $no = $this->getNoNeedAuth();
        if (count($no) > 0) {
            $m = \epii\server\App::getInstance()->getRunner()[1];
            if (in_array($m, $no) || ((count($no) == 1) && ($no[0] == "..."))) {
                $auth_bool = false;
            }
        }
        $this->is_auth = $check();
        if ($auth_bool && !$this->is_auth) {

            $this->onAuthFail();
            return false;
        }
        return true;
    }
}