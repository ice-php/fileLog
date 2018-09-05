<?php
declare(strict_types=1);

namespace icePHP;

/**
 * 写文本日志
 * 这是SLog的一个快捷入口
 *
 * @param string $file 日志文件名
 * @param string|array $msg 要记录的内容
 * @param $raw bool 是否以原始格式进行记录,否则会加些附加信息
 */
function writeLog(string $file, $msg, bool $raw = false): void
{
    FileLog::instance()->writeLog($file, $msg, $raw);
}
