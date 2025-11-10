<?php

require_once __DIR__ . '/vendor/autoload.php';

use Adapterman\Adapterman;
use Workerman\Worker;
use Illuminate\Support\Facades\Cache;
use \Workerman\Events\EventInterface;

putenv('APP_RUNNING_IN_CONSOLE=false');
define('MAX_REQUEST', 6600);
define('isWEBMAN', true);
define('Workerman', true);

Adapterman::init();

$ncpu = substr_count((string)@file_get_contents('/proc/cpuinfo'), "\nprocessor")+1;

$http_worker                = new Worker('http://127.0.0.1:6600');
$http_worker->count         = $ncpu * 2;
$http_worker->name          = 'AdapterMan';

$http_worker->onWorkerStart = static function () {
    //init();
    require __DIR__.'/start.php';
};

$http_worker->onMessage = static function ($connection, $request) {
    static $request_count = 0;
    static $pid;
    if ($request_count == 1) {
        $pid = posix_getppid();
        Cache::forget("WEBMANPID");
        Cache::forever("WEBMANPID", $pid);
    }
    $connection->send(run());
    if (++$request_count > MAX_REQUEST) {
        Worker::stopAll();
    }
};

// 文件监控热更新功能
if (extension_loaded('inotify')) {
    $worker = new Worker();
    $worker->name = 'FileMonitor';
    $worker->reloadable = false;
    $monitor_dirs = ['app', 'bootstrap', 'config', 'resources', 'routes', 'public', '.env'];
    $monitor_files = array();

    // 进程启动后创建inotify监控句柄
    $worker->onWorkerStart = function ($worker) {
        if (!extension_loaded('inotify')) {
            echo "FileMonitor : Please install inotify extension.\n";
            return;
        }
        global $monitor_dirs, $monitor_files;
        $worker->inotifyFd = inotify_init();
        stream_set_blocking($worker->inotifyFd, 0);

        foreach ($monitor_dirs as $monitor_dir) {
            $monitor_realpath = realpath(__DIR__ . "/{$monitor_dir}");
            addInofity($monitor_realpath, $worker->inotifyFd);
            if (is_file($monitor_realpath))
                continue;
            $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($monitor_realpath, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::SELF_FIRST);
            foreach ($iterator as $file) {
                if ($file->isDir()) {
                    $realpath = realpath($file);
                    addInofity($realpath, $worker->inotifyFd);
                }
            }
        }
        Worker::$globalEvent->add($worker->inotifyFd, EventInterface::EV_READ, 'check_files_change');
    };
}

function addInofity(string $realpath, $fd)
{
    global $monitor_files;
    $wd = inotify_add_watch($fd, $realpath, IN_MODIFY | IN_CREATE | IN_DELETE);
    $monitor_files[$wd] = $realpath;
}

function check_files_change($inotify_fd)
{
    global $monitor_files;
    $events = inotify_read($inotify_fd);
    if ($events) {
        foreach ($events as $ev) {
            $file = $monitor_files[$ev['wd']];
            echo $file . "/{$ev['name']} update and reload\n";
        }
        posix_kill(posix_getppid(), SIGUSR1);
    }
}

Worker::runAll();