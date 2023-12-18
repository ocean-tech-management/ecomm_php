<?php
// +----------------------------------------------------------------------
// |[ 文档说明: 订单模块异常类 异常编码:15001]
// +----------------------------------------------------------------------
// | Copyright (c) 2018~2024 http://www.mlhcmk.com All rights reserved.
// +----------------------------------------------------------------------


namespace app\lib\exceptions;


use app\lib\BaseException;

class OrderException extends BaseException
{
    public $errorCode = 15001;
}