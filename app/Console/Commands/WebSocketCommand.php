<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Workerman\Worker;
use GatewayWorker\BusinessWorker;
use GatewayWorker\Gateway;
use GatewayWorker\Register;

class WebSocketCommand extends Command
{
    protected $signature = 'WebSocketCommand {action} {--d}';
    protected $description = 'Start a Workerman server.';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        /*
        * 操作参数
        * 注意只能在
        * start 启动
        * stop 停止
        * relaod  只能重启逻辑代码，核心workerman_init无法重启，注意看官方文档
        * status 查看状态
        * connections 查看连接状态（需要Workerman版本>=3.5.0）
        *
        */

        // // 设置WorkerMan进程的pid文件路径
        // Worker::$pidFile = '/var/run/workerman.pid';

        global $argv;
        $action = $this->argument('action');

        $argv[0] = 'wk';
        $argv[1] = $action;
        $argv[2] = $this->option('d') ? '-d' : '';

        $this->startGateWay();
        $this->startBusinessWorker();
        $this->startRegister();
        Worker::runAll();
    }

    private function startGateWay()
    {
        $wsPort = 'websocket://0.0.0.0:' . env('WSPORT', '6001');
        $wsRegisterAddress = '127.0.0.1:' . env('WSREGISTERADDRESS', '1236');
        $wsStartPort = env('WSSTARTPORT', '2300');

        $gateway                       = new Gateway($wsPort);
        // $gateway->onConnect = function ($connection) {
        //     $connection->websocketType = Websocket::BINARY_TYPE_ARRAYBUFFER;
        // };

        $gateway->name                 = 'Gateway';
        $gateway->count                = (int)env('GATEWAYCOUNT', '1');
        $gateway->lanIp                = '127.0.0.1';
        $gateway->startPort            = $wsStartPort;
        $gateway->pingInterval         = 60;
        $gateway->pingNotResponseLimit = 1;
        $gateway->pingData             = '{"type":"ping"}';
        $gateway->registerAddress      = $wsRegisterAddress;
    }


    private function startBusinessWorker()
    {
        $wsRegisterAddress = '127.0.0.1:' . env('WSREGISTERADDRESS', '1236');

        $worker                  = new BusinessWorker();
        $worker->name            = 'BusinessWorker';
        $worker->count           = (int)env('BUSINESSWORKERCOUNT', '1');
        $worker->registerAddress = $wsRegisterAddress;
        $worker->eventHandler    = \App\Services\Workerman\Events::class;
        $worker->processTimeout = 900;
    }

    private function startRegister()
    {
        $wsRegisterAddress = 'text://0.0.0.0:' . env('WSREGISTERADDRESS', '1236');

        new Register($wsRegisterAddress);
    }
}
