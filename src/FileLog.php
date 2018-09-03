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

    //禁止外部实例化
    private function __construct()
    {
        $this->isConfigDebug = Config::isDebug();
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

    //日志文件根目录
    private $dirRoot = './';

    /**
     * 初始化日志体系, 设置日志根目录
     * @param $dirRoot string 系统根目录
     */
    public function init(string $dirRoot): void
    {
        //设定日志根目录
        $this->dirRoot = Config::get('log', 'dir_log') ?: $dirRoot . 'run/log/';
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

        $dir = rtrim(Config::get('log', 'dir_log') ?: $this->dirRoot . 'run/log/', '/\\') . '/';

        // 在日志目录下创建年目录
        $path = $dir . date('Y-m-d') . '/' . $file . '.log';
        $this->makeDir(dirname($path));

        // 写入文件
        return $this->writeFile($path, $msg, FILE_APPEND | LOCK_EX);
    }

    /**
     * 对file_put_contents的封装,以修正文件所有者
     * @param $file string 文件名
     * @param $content string 文件内容
     * @param int $flag FILE_APPEND&|LOCK_EX
     * @return false|int
     */
    private function writeFile(string $file, $content, int $flag = 0)
    {
        //当前用户
        $current = getenv('USERNAME') ?: getenv('USER');

        //应该是这个用户
        $should = Config::get('system', 'OS_USER');

        //如果操作系统是Windows或当前已经是应该的用户,则不处理
        if (isWindows() or $current === $should) {
            return file_put_contents($file, $content, $flag);
        }

        //提前调整文件所有者
        if (function_exists('posix_getpwuid') && is_file($file)) {
            $owner = posix_getpwuid(fileowner($file));
            if ($owner !== $should) {
                chown($file, $should);
            }
        }

        //写入文件
        $ret = file_put_contents($file, $content, $flag);

        if (function_exists('posix_getpwuid')) {
            //延后调整文件所有者
            $owner = posix_getpwuid(fileowner($file));
            if ($owner !== $should) {
                chown($file, $should);
            }
        }

        return $ret;
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
        if (!Config::get('log', 'enable') and !$this->isConfigDebug) {
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
        $this->log(Config::get('log', 'exception') ?: 'exception', dump($_REQUEST, 'REQUEST', true) . PHP_EOL . var_export($e, true));
    }

    /**
     * 记录MCA参数异常日志
     * @param $req string[]
     */
    public function mca(array $req): void
    {
        $this->log(Config::get('log', 'mca') ?: 'mca', $req);
    }

    /**
     * 记录超时处理日志
     * @param $time  float 本次请求的处理时间
     */
    public function dispatch(float $time): void
    {
        //是否记录派发日志
        if (!Config::get('log', 'dispatch')) {
            return;
        }

        // 如果用时少于下限,则不记录
        $limit = Config::get('log', 'dispatch', 'limit');
        if ($limit and $time < $limit) {
            return;
        }

        // 处理数据格式
        $request = json_encode($_REQUEST, JSON_UNESCAPED_UNICODE);

        // 写入日志
        $this->log(Config::get('log', 'dispatch', 'file'), $time . "\t" . $request . PHP_EOL);
    }

    /**
     * 记录轻度 安全参数 问题日志
     * @param string[] $req
     */
    public function antiLight(array $req): void
    {
        $this->isConfigDebug ? print('anti light') : null;
        $this->log(Config::get('log', 'anti', 'light') ?: 'anti/light', $req);
    }

    /**
     * 记录重度安全参数 问题日志
     * @param string[] $req
     */
    public function antiHigh(array $req): void
    {
        $this->isConfigDebug ? print('anti high') : null;
        $this->log(Config::get('log', 'anti', 'high') ?: 'anti/high', $req);
    }

    /**
     * 记录请求参数不合法
     * @param string[] $req
     */
    public function antiName(array $req): void
    {
        $this->isConfigDebug ? print('anti param name') : null;
        $this->log(Config::get('log', 'anti', 'param_name') ?: 'anti/param_name', $req);
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
        $this->log(Config::get('log', 'anti', 'black_ip') ?: 'anti/blackIp', ['rawIp' => $rawIp, 'ip' => $ip, 'request' => $req]);
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
        $this->log(Config::get('log', 'anti', 'flash') ?: 'anti/flash', ['ip' => $ip, 'request' => $request, 'server' => $server]);
    }

    /**
     * 执行SQL前的日志
     * @param $name string 日志名称
     * @param $sql string SQL语句
     */
    public function sqlBefore(string $name, string $sql): void
    {
        //查看是否存在SQL日志配置
        Config::get('log', 'sql', $name) ? $this->log(Config::get('log', 'sql', $name),
            $name . "\t" . intval($this->logRequestId) . "\t" . '<SQL>' . $sql . '</SQL>'
        ) : null;
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
        $config = Config::get('log', 'sql');
        if (!$config) {
            return;
        }

        // 如果用时间少于下限,则不记录,注:DELETE不受时间限制
        $limit = Config::get('log', 'sql', 'limit') ?: 10;
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
        Config::get('log', 'writeLog') ? $this->log($file, $msg, $raw) : null;
    }

    /**
     * 越级创建目录
     * 全局函数中有此方法, 为减少耦合,在此单独实现
     * @param $path string 目录名称
     */
    private function makeDir(string $path): void
    {
        //转换标准路径 分隔符
        $path = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $path);

        //如果已经是目录或文件
        if (is_dir($path) or is_file($path)) {
            return;
        }

        //上一级目录
        $parent = dirname($path);

        //如果上一级不是目录,则创建上一级
        if (!is_dir($parent)) {
            $this->makeDir($parent);
        }

        //创建当前目录
        mkdir($path, 0777);

        //Windows系统,不进行后续处理
        if ($this->isWindows()) {
            return;
        }

        //当前用户
        $current = getenv('USERNAME') ?: getenv('USER');

        //应该是这个用户
        $should = Config::get('system', 'OS_USER') ?: 'www';

        //如果当前已经是应该的用户,则不处理
        if ($current === $should) {
            return;
        }

        //修改所有者为www(应该的用户)
        chown($path, $should);
    }

    /**
     * 判断当前操作系统是否Windows
     * @return bool
     */
    private function isWindows(): bool
    {
        //获取操作系统
        $os = getenv('OS');

        //无法取到,通常是Linux
        if (!$os) {
            return false;
        }

        //检查其中是否有Windows字样
        return strpos(getenv('OS'), 'Windows') !== false;
    }
}