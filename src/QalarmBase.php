<?php

namespace Knowbox\Libs;

abstract class QalarmBase
{
    const LOCAL_LOG_DIR = '/data/log/alarm/';
    const FILE_PERM = 0755;
    const LINE_MAX_SIZE = 102400;

    const ENV_DEV = 'dev';
    const ENV_TEST = 'test';
    const ENV_PRE = 'pre';
    const ENV_PROD = 'prod';

    const METHOD_SYNC = 'sync';
    const METHOD_ASYNC = 'async';

    private static $alarm_data = null;

    /**
     * 打印报警日志
     * @param string $module 错误模块
     * @param int $code 错误代码
     * @param string $message 错误文案
     * @param string $project 项目
     * @param string $server_ip 服务端 ip 地址
     * @param string $client_ip 客户端 ip 地址
     * @param string $script 代码位置
     * @return bool
     */
    public static function sendQalarm(
        $module,
        $code,
        $message,
        $project,
        $server_ip = '',
        $client_ip = '',
        $script = ''
    ) {
        $env = empty(getYaconfEnv('YII_ENV')) ? self::ENV_PROD : getYaconfEnv('YII_ENV');

        $timestamp = time();

        if (empty($server_ip)) {
            $server_ip = isset($_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR'] : php_uname('n');
        }

        if (empty($client_ip)) {
            $client_ip = empty($GLOBALES['HTTP_POST_VARS']['client_ip']) ? (isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '') : $GLOBALES['HTTP_POST_VARS']['client_ip'];
            $client_ip = empty($client_ip) ? '172.0.0.1' : $client_ip;
        }

        $url = empty($_SERVER['REQUEST_URI']) ? $_SERVER['SCRIPT_NAME'] : $_SERVER['REQUEST_URI'];

        if (empty($script)) {
            $stack = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3);
            $caller = array_merge($stack[0], isset($stack[1]) ? $stack[1] : [], isset($stack[2]) ? $stack[2] : []);
            $caller_class = isset($caller['file']) ? $caller['file'] : '';
            $caller_line = isset($caller['line']) ? $caller['line'] : '';

            $script = "$caller_class:$caller_line";
        }

        if ($script == ':') {
            $script = $url;
        }

        $cookie = isset($_SERVER['cookie']) ? $_SERVER['cookie'] : [];

        self::$alarm_data = $data = [
            'project' => $project,
            'module' => $module,
            'code' => $code,
            'env' => $env,
            'time' => $timestamp,
            'server_ip' => $server_ip,
            'client_ip' => $client_ip,
            'script' => $script,
            'message' => $message,
            'url' => $url,
            'params' => Util::extractParam(),
            'cookie' => $cookie,
        ];

        return self::output($data);
    }

    private static function output($data)
    {
        $msg = json_encode($data, JSON_UNESCAPED_UNICODE);
        $log_file = self::LOCAL_LOG_DIR.'alarm.log';
        if (! is_file($log_file)) {
            touch($log_file);
            chmod($log_file, self::FILE_PERM);
        }
        file_put_contents($log_file, $msg."\n", FILE_APPEND | LOCK_EX);

        return true;
    }
}
