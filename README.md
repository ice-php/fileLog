记录文本日志
=

* 获取单例

    FileLog::instance(): FileLog

* 初始设置
    
    FileLog::init(string $dirRoot): void
    
    初始化日志体系, 设置日志根目录

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