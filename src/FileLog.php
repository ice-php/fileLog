<?php
declare(strict_types=1);

namespace icePHP;

/**
 * 文件日志 处理类
 * @author Ice
 * 通常不直接使用,如需要日志功能,请调用writeLog方法
 */
final class FileLog
{
    //当前是否配置为调试模式
    private $isConfigDebug = false;

    //日志文件根目录
    private $dirRoot = '';

    //禁止外部实例化
    private function __construct()
    {
        try {
            $this->isConfigDebug = Config::isDebug();
        } catch (ConfigException $e) {
            $this->isConfigDebug = true;
        }

        $this->dirRoot = configDefault('./log/', 'log', 'dir_log');
    }

    /**
     * 获取单例实例
     * @return FileLog
     */
    public static function instance(): FileLog
    {
        //单例句柄
        static $instance;
        if (!$instance) {
            $instance = new static();
        }
        return $instance;
    }

    /**
     * 初始化日志体系, 设置日志根目录
     * @param $dirRoot string 系统根目录
     */
    public function init(string $dirRoot): void
    {
        //设定日志根目录
        $this->dirRoot = $dirRoot;
    }

    //用来记录当前请求的表记录ID(可能没有)
    private $logRequestId;

    /**
     * 设置一个用来表示本次会话请求的ID, 此ID会记录到日志中, 以便于表日志或Nginx日志相对应
     * @param int $id
     */
    public function setLogRequestId(int $id): void
    {
        $this->logRequestId = $id;
    }

    /**
     * 具体写操作
     *
     * @param  $file string 日志文件的名称
     * @param  $msg string 要写入的信息
     * @return false|int 写入是否成功
     */
    private function write(string $file, string $msg)
    {
        // 将文件名中的目录分隔符标准化
        $file = str_replace('\\', '/', $file);

        // 在日志目录下创建年目录
        $path = $this->dirRoot . date('Y-m-d') . '/' . $file . '.log';
        makeDir(dirname($path));

        // 写入文件
        return write($path, $msg, FILE_APPEND | LOCK_EX);
    }

    /**
     * 记录应用日志
     *
     * @param string $file 要记录到的文件名(不要包含后缀)
     * @param mixed $msg 要记录的内容,请不要包括\t,\n之类的格式符号
     * @param $raw bool 是否以原始格式记录
     * @return false|int 是否记录成功
     */
    private function log(string $file, $msg, bool $raw = false)
    {
        // 全局日志开关
        $enable = configDefault(false, 'log', 'enable');
        if (!$enable and !$this->isConfigDebug) {
            return false;
        }

        // 数据格式转为字符串
        $msg = $this->msg($msg);

        // 写入一个空行
        if (!$msg) {
            return $this->write($file, PHP_EOL);
        }

        // 是否要求不附加日期数据
        return $this->write($file, $raw ? $msg : date('H:i:s') . "\t" . $msg . PHP_EOL);
    }

    /**
     * 将要日志的数据,转变为字符串
     *
     * @param mixed $msg 数组/对象/基本类型
     * @return string
     */
    private function msg($msg): string
    {
        // 将数组转为字符串
        if (is_array($msg) or is_object($msg) or is_bool($msg)) {
            return json($msg);
        }

        // 返回可显示的字符串
        return (string)$msg;
    }

    /**
     * 记录异常日志
     * @param $e \Exception
     */
    public function exception(\Exception $e): void
    {
        $this->log(
            configDefault('exception', 'log', 'exception') ?: 'exception',
            dump($_REQUEST, 'REQUEST', true) . PHP_EOL . var_export($e, true)
        );
    }

    /**
     * 记录MCA参数异常日志
     * @param $req string[]
     */
    public function mca(array $req): void
    {
        $this->log(configDefault('mca', 'log', 'mca') ?: 'mca', $req);
    }

    /**
     * 记录超时处理日志
     * @param $time  float 本次请求的处理时间
     */
    public function dispatch(float $time): void
    {
        //是否记录派发日志
        if (!configDefault(false, 'log', 'dispatch')) {
            return;
        }

        // 如果用时少于下限,则不记录
        $limit = configDefault(1, 'log', 'dispatch', 'limit');
        if ($limit and $time < $limit) {
            return;
        }

        // 处理数据格式
        $request = json_encode($_REQUEST, JSON_UNESCAPED_UNICODE);

        // 写入日志
        $this->log(configDefault('dispatch', 'log', 'dispatch', 'file'), $time . "\t" . $request . PHP_EOL);
    }

    /**
     * 记录轻度 安全参数 问题日志
     * @param string[] $req
     */
    public function antiLight(array $req): void
    {
        $this->isConfigDebug ? print('anti light') : null;
        $this->log(configDefault('anti/light', 'log', 'anti', 'light'), $req);
    }

    /**
     * 记录重度安全参数 问题日志
     * @param string[] $req
     */
    public function antiHigh(array $req): void
    {
        $this->isConfigDebug ? print('anti high') : null;
        $this->log(configDefault('anti/high', 'log', 'anti', 'high'), $req);
    }

    /**
     * 记录请求参数不合法
     * @param string[] $req
     */
    public function antiName(array $req): void
    {
        $this->isConfigDebug ? print('anti param name') : null;
        $this->log(configDefault('anti/param_name', 'log', 'anti', 'param_name'), $req);
    }

    /**
     * 记录 IP黑名单日志
     * @param $ip string IP
     * @param $rawIp string 原始IP
     * @param string[] $req
     */
    public function blackIp(string $rawIp, string $ip, array $req): void
    {
        $this->isConfigDebug ? print('anti black ip') : null;
        $this->log(configDefault('anti/blackIp', 'log', 'anti', 'black_ip'), ['rawIp' => $rawIp, 'ip' => $ip, 'request' => $req]);
    }

    /**
     * 记录FLASH包含日志
     * @param $ip string IP地址
     * @param $request string[] 请求参数数组
     * @param $server string[] 环境变量数组
     */
    public function flash(string $ip, array $request, array $server): void
    {
        $this->isConfigDebug ? print('anti flash') : null;
        $this->log(configDefault('anti/flash', 'log', 'anti', 'flash'), ['ip' => $ip, 'request' => $request, 'server' => $server]);
    }

    /**
     * 执行SQL前的日志
     * @param $name string 日志名称
     * @param $sql string SQL语句
     */
    public function sqlBefore(string $name, string $sql): void
    {
        //查看是否存在SQL日志配置
        $file = configDefault(false, 'log', 'sql', $name);
        $file ? $this->log($file, $name . "\t" . intval($this->logRequestId) . "\t" . '<SQL>' . $sql . '</SQL>') : null;
    }

    /**
     * 记录SQL执行结果日志
     * @param $name string 日志名称
     * @param $sql string SQL语句
     * @param $data mixed 返回结果
     * @param $time float 用时
     * @param $op string 操作(SELECT/DELETE/INSERT/UPDATE)
     */
    public function sqlAfter(string $name, string $sql, $data, float $time, string $op): void
    {
        //查看是否存在SQL日志配置
        $config = configDefault(false, 'log', 'sql');
        if (!$config) {
            return;
        }

        // 如果用时间少于下限,则不记录,注:DELETE不受时间限制
        $limit = configDefault(10, 'log', 'sql', 'limit');
        if ($op != 'DELETE' and $limit and $time < $limit) {
            return;
        }

        // 处理数据格式
        $sql = str_replace(["\n", "\t", "  "], ' ', $sql);

        //记录日志
        $this->log($config[$name], $op . "\t" . intval($this->logRequestId) . "\t" . $time . "\t" . '<SQL>' . $sql . '</SQL>' . "\t" . '<RESULT>' . json($data) . '</RESULT>');
    }

    /**
     * 开发人员主动写文本日志
     * @param string $file 日志文件名
     * @param mixed $msg 要记录的内容
     * @param $raw bool 是否以原始格式进行记录,否则会加些附加信息
     */
    public function writeLog(string $file, $msg, bool $raw = false): void
    {
        //是否允许记录开发人员日志
        configDefault(false, 'log', 'writeLog') ? $this->log($file, $msg, $raw) : null;
    }
}