<?php

namespace Celysium\WebSocket;

use OpenSwoole\Constant;
use OpenSwoole\Http\Request;
use OpenSwoole\Table;
use OpenSwoole\WebSocket\Frame;
use OpenSwoole\WebSocket\Server as WebsocketServer;

class Server extends WebsocketServer implements ServerInterface
{
    private static Table $fds;

    private static array $tasks = [];

    public function __construct(string $host, int $port = 0, int $mode = \OpenSwoole\Server::SIMPLE_MODE, int $sockType = Constant::SOCK_TCP)
    {
        $host = $host ?: config('websocket.server.host');
        $port = $port ?: config('websocket.server.port');

        static::initialFds();

        parent::__construct($host, $port, $mode, $sockType);
    }

    public static function initialFds(): void
    {
        static::$fds = new Table(1024);
        static::$fds->column('fd', Table::TYPE_INT, 4);
        static::$fds->column('channel', Table::TYPE_STRING, 32);
        static::$fds->column('user_id', Table::TYPE_INT, 4);
        static::$fds->create();
    }

    /**
     * @return Table
     */
    public function getFds(): Table
    {
        return static::$fds;
    }

    public function onStart(): void
    {
        $this->on("Start", function () {
            echo "WebSocket Server is started at " . $this->host . ":" . $this->port . PHP_EOL;
        });
    }

    public function onOpen(): void
    {
        $this->on("Open", function (Server $server, Request $request) {
            if(isset($request->get['channel']) && isset($request->get['user_id'])) {
                $fd = $request->fd;
                $server->getFds()->set($fd, [
                    'fd'      => $fd,
                    'channel' => $request->get['channel'],
                    'user_id' => $request->get['user_id'],
                ]);

                $server->tick(1000, function () use ($server, $request) {
                    $this->resolveTasks($server, $request);
                });

                echo "Connection <$fd> opened. Total connections: " . $server->getFds()->count() . PHP_EOL;
            }
            else {
                echo "Disconnect: <$request->fd>, total connections: " . $server->getFds()->count() . PHP_EOL;
            }
        });
    }

    public function onMessage(): void
    {
        $this->on("Message", function (Server $server, Frame $frame) {
            $data = json_decode($frame->data);

            $user_id = $data->user_id;
            echo "Received message from " . $frame->fd . ($user_id ? (" for user_id : $user_id") : '') . PHP_EOL;
        });
    }

    public function onClose(): void
    {
        $this->on("Close", function (Server $server, int $fd) {
            $server->getFds()->del($fd);

            echo "Connection close: $fd, total connections: " . $server->getFds()->count() . PHP_EOL;
        });
    }

    public function onDisconnect(): void
    {
        $this->on("Disconnect", function (Server $server, int $fd) {
            $server->getFds()->del($fd);
            echo "Disconnect: $fd, total connections: " . $server->getFds()->count() . PHP_EOL;
        });
    }

    /**
     * @param string $data
     * @param string $channel
     * @return void
     */
    public static function broadcast(string $data, string $channel = '*'): void
    {
        static::$tasks = array_merge(static::$tasks, [
            'channel' => $channel,
            'data' => $data
        ]);
    }

    /**
     * @param string $data
     * @param array $users
     * @return void
     */
    public static function sendOnly(string $data, array $users): void
    {
        static::$tasks = array_merge(static::$tasks, [
            'only' => $users,
            'data' => $data
        ]);
    }

    /**
     * @param string $data
     * @param array $users
     * @return void
     */
    public static function sendExcept(string $data, array $users): void
    {
        static::$tasks = array_merge(static::$tasks, [
            'except' => $users,
            'data' => $data
        ]);
    }


    /**
     * @param Server $server
     * @param Request $request
     * @return void
     */
    private function resolveTasks(Server $server, Request $request): void
    {
        if (!empty(static::$tasks) && $server->isEstablished($request->fd)) {
            foreach (static::$tasks as $key => $task) {
                if(array_key_exists('channel', $task)) {
                    $this->sendToChannel($server, $request->fd, $task);
                }
                elseif(array_key_exists('only', $task)) {
                    $this->sendToOnly($server, $request->fd, $task);
                }
                elseif(array_key_exists('except', $task)) {
                    $this->sendToExcept($server, $request->fd, $task);
                }
                unset(static::$tasks[$key]);
            }
        }
    }

    private function sendToChannel(Server $server, int $fd, array $task)
    {
        $client = $server::getFds()->get($fd);
        if($client['channel'] == $task['channel'] || $task['channel'] == '*') {
            $server->push($fd, $task['data']);
        }
    }

    private function sendToOnly(Server $server, int $fd, array $task)
    {
        $client = $server::getFds()->get($fd);
        if(in_array($client['user_id'], $task['only'])) {
            $server->push($fd, $task['data']);
        }
    }

    private function sendToExcept(Server $server, int $fd, array $task)
    {
        $client = $server::getFds()->get($fd);
        if(! in_array($client['user_id'], $task['except'])) {
            $server->push($fd, $task['data']);
        }
    }
}
