# fileLog
记录文本日志

## 获取单例实例
instance(): FileLog

## 初始化日志体系, 设置日志根目录
init(string $dirRoot): void

## 设置一个用来表示本次会话请求的ID, 此ID会记录到日志中, 以便于表日志或Nginx日志相对应
setLogRequestId(int $id): void

## 记录异常日志
exception(\Exception $e): void

## 记录MCA(Module,Controller,Action)参数异常日志
mca(array $req): void 

## 记录超时处理日志
dispatch(float $time): void

##  记录轻度 安全参数 问题日志
antiLight(array $req): void

## 记录重度安全参数 问题日志
antiHigh(array $req): void

## 记录请求参数不合法
antiName(array $req): void

## 记录 IP黑名单日志
blackIp(string $rawIp, string $ip, array $req): void

## 记录FLASH包含日志
flash(string $ip, array $request, array $server): void

## 执行SQL前的日志
sqlBefore(string $name, string $sql): void

## 记录SQL执行结果日志
sqlAfter(string $name, string $sql, $data, float $time, string $op): void

## 开发人员主动写文本日志
writeLog(string $file, $msg, bool $raw = false): void

