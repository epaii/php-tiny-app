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
    protected function getNoNeedAuth(): array
    {
        return [];
    }

    abstract protected function doAuth(): bool;

    public function init()
    {
        $no = $this->getNoNeedAuth();
        if (count($no) > 0) {
            $m = \epii\server\App::getInstance()->getRunner()[1];
            if (!in_array($m, $no)) {
                if (!$this->doAuth()) {
                    $this->error("授权失败");
                }

            }
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