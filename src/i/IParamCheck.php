<?php
/**
 * Created by PhpStorm.
 * User: mrren
 * Date: 2019/3/26
 * Time: 11:09 AM
 */

namespace epii\server\i;


interface  IParamCheck
{
    public function param_get_required(): array;

    public function param_on_result(bool $ok,array $error_param);
}