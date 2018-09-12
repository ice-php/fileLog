记录文本日志
=

* 获取单例

    FileLog::instance(): FileLog

* 初始设置
    
    FileLog::init(string $dirRoot): void
    
    初始化日志体系, 设置日志根目录
    
    如未特殊指定，则使用配置中指定的目录；如配置文件中也未指定，则使用系统根目录，即index.php所在目录。

* 设置一个用来表示本次会话请求的ID, 此ID会记录到日志中, 以便于表日志或Nginx日志相对应
    FileLog::setLogRequestId(int $id): void

* 记录异常日志

    FileLog::exception(\Exception $e): void

* 记录MCA(Module,Controller,Action)参数异常日志
    
    FileLog::mca(array $req): void 

* 记录超时处理日志
    
    FileLog::dispatch(float $time): void

*  记录轻度 安全参数 问题日志
    
    FileLog::antiLight(array $req): void

* 记录重度安全参数 问题日志
    
    FileLog::antiHigh(array $req): void

* 记录请求参数不合法
    
    FileLog::antiName(array $req): void

* 记录 IP黑名单日志
    
    FileLog::blackIp(string $rawIp, string $ip, array $req): void

* 记录FLASH包含日志
    
    FileLog::flash(string $ip, array $request, array $server): void

* 执行SQL前的日志
    
    FileLog::sqlBefore(string $name, string $sql): void

* 记录SQL执行结果日志
    
    FileLog::sqlAfter(string $name, string $sql, $data, float $time, string $op): void
        
    如果配置项log|sql为True，且SQL执行时间超过配置项log|sql|limit(默认为10s)，则记录此SQL及结果到$name指定的日志中。

* 主动写文本日志
    
    FileLog::writeLog(string $file, $msg, bool $raw = false): void
    
    或
    
    writeLog(string $file, $msg, bool $raw = false): void
    
    如果配置项 log|writeLog 为True，则允许开发人员主动记录文本日志
    
* 配置文件示例
~~~php
<?php
/**
 * 日志相关配置(包括文件日志与数据库日志)
 */

return [
    //日志全局配置
    'enable' => false,                    //日志功能是否开启
    'dir_log' => DIR_ROOT . 'run/log/',        //日志文件根目录

    //数据库日志
    'requestLog' => ['icePHP\TableLog', 'request'], //由哪个方法记录系统请求日志
    'operationLog' => ['icePHP\TableLog', 'db'],//由哪个方法记录系统操作日志

    //由哪个方法获取当前后台管理员编号和管理员名称, 应该是个静态方法,且从SESSION或本地文件中读取,此时还不能访问数据库
    'getAdminId' => ['MCurrentUser', 'getAdminId'],
    'getAdminName' => ['MCurrentUser', 'getAdminName'],

    //数据库日志-免记录名单,日志表,对这些表的增删改,就不要再次记录日志了
    'noLogTables' => [
        'logRequest',
        'logOperation'
    ],

    //数据库请求日志-免记录名单 ,以下MCA请求就不记录请求日志了
    'noLogRequest' => [
        ['aModule', 'aController', 'aAction']
    ],

    'logTables' => ['logRequest', 'logOperation', 'logSms'],//日志表,对这些表的增删改,就不要再次记录日志了

    //请求日志-执行时间日志
    'dispatch' => [
        'limit' => 0, //执行时间长度限制(毫秒)
        'file' => 'web/dispatch', //日志文件
    ],

    //请求日志-发生MCA异常的记录
    'mca' => 'anti/mca',

    //请求日志-防入侵相关日志配置
    'anti' => [
        //IP黑名单
        'black_ip' => 'anti/black_ip',

        //参数名称不允许
        'param_name' => 'anti/param_name',

        //参数值轻度约束
        'light' => 'anti/light',

        //参数值高度约束
        'high' => 'anti/high',

        //不允许被Flash调用
        'flash' => 'anti/flash',
    ],

    //请求日志-捕获抛出的异常时的记录
    'exception' => 'exception/exception',

    //请求日志-以下MCA,要记录请求体
    'requestBody' => [
        ['aModule', 'aController', 'aAction']
    ],

    //所有SQL文本日志
    'sql' => [
        //查询请求日志
        'query' => 'sql/query',
        'execute' => 'sql/execute',
        'queryHandle' => 'sql/queryHandle',
        'queryFast' => 'sql/queryFast',  //快速查询不记录查询结果
        'limit' => 0, //超时则记录,
        'afterQuery' => 'sql/afterQuery',
        'afterExecute' => 'sql/afterExecute',
        'afterQueryHandle' => 'sql/afterQueryHandle',
    ],

    //开发人员用writeLog写的日志是否记录
    'writeLog' => true,
];
~~~