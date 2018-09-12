<?php
declare(strict_types=1);

namespace icePHP;

class FileLogException extends \Exception
{
    //使用文本日志前必须配置或指定日志目录
    const UNKNOWN_ROOT=1;
}