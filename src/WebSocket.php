<?php

namespace Celysium\WebSocket;

use Celysium\WebSocket\Events\IncomingMessageEvent;
use Celysium\WebSocket\Events\JoinEvent;
use OpenSwoole\Constant;
use OpenSwoole\Http\Request;
use OpenSwoole\Table;
use OpenSwoole\WebSocket\Frame;
use OpenSwoole\WebSocket\Server as WebsocketServer;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class WebSocket extends WebsocketServer implements ServerInterface
{
    private static Table $fds;

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
        $this->on("Open", function (WebSocket $server, Request $request) {
            $fd = $request->fd;
            if (isset($request->get['channel']) && isset($request->get['user_id'])) {
                $server::getFds()->set($fd, [
                    'channel' => $request->get['channel'],
                    'user_id' => (int)$request->get['user_id'],
                ]);

                $server->tick(1000, function () use ($server, $request) {
                    $this->resolveTasks($server, $request);
                });

                JoinEvent::dispatch((int)$request->get['user_id'], $request->get['channel']);

                echo "Connection <$fd> opened. Total connections: " . $server::getFds()->count() . PHP_EOL;
            } else {
                $server->disconnect($fd, 1003, 'unauthorized');
                echo "Disconnect <$fd>, total connections: " . $server::getFds()->count() . PHP_EOL;
            }
        });
    }

    public function onMessage(): void
    {
        $this->on("Message", function (WebSocket $server, Frame $frame) {
            $fd = $frame->fd;
            $client = $server::getFds()->get($fd);
            $data = json_decode($frame->data, true);

            if($data) {
                IncomingMessageEvent::dispatch($client['user_id'], $client['channel'], $data);
            }
            echo "Received message from : " . $fd . PHP_EOL;
        });
    }

    public function onClose(): void
    {
        $this->on("Close", function (WebSocket $server, int $fd) {
            $server::getFds()->del($fd);

            echo "Connection close: $fd, total connections: " . $server::getFds()->count() . PHP_EOL;
        });
    }

    public function onDisconnect(): void
    {
        $this->on("Disconnect", function (WebSocket $server, int $fd) {
            $server::getFds()->del($fd);
            echo "Disconnect: $fd, total connections: " . $server::getFds()->count() . PHP_EOL;
        });
    }

    /**
     * @param array $data
     * @param string $channel
     * @return void
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public static function broadcast(array $data, string $channel = '*'): void
    {
        $tasks = cache()->get('websocket_tasks', []);
        cache()->put('websocket_tasks', array_merge($tasks, [[
                                                                 'channel' => $channel,
                                                                 'data'    => json_encode($data),
                                                                 'done'    => 0,
                                                             ]]));
    }

    /**
     * @param array $data
     * @param array $users
     * @return void
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public static function sendOnly(array $data, array $users): void
    {
        $tasks = cache()->get('websocket_tasks', []);
        cache()->put('websocket_tasks', array_merge($tasks, [[
                                                                 'only' => $users,
                                                                 'data'    => json_encode($data),
                                                                 'done' => 0,
                                                             ]]));
    }

    /**
     * @param array $data
     * @param array $users
     * @return void
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public static function sendExcept(array $data, array $users): void
    {
        $tasks = cache()->get('websocket_tasks', []);
        cache()->put('websocket_tasks', array_merge($tasks, [[
                                                                 'except' => $users,
                                                                 'data'    => json_encode($data),
                                                                 'done'   => 0,
                                                             ]]));
    }


    /**
     * @param WebSocket $server
     * @param Request $request
     * @return void
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function resolveTasks(WebSocket $server, Request $request): void
    {
        $tasks = cache()->get('websocket_tasks', []);
        if (!empty($tasks) && $server->isEstablished($request->fd)) {
            foreach ($tasks as $key => $task) {
                if (array_key_exists('channel', $task)) {
                    $this->sendToChannel($server, $request->fd, $task);
                } elseif (array_key_exists('only', $task)) {
                    $this->sendToOnly($server, $request->fd, $task);
                } elseif (array_key_exists('except', $task)) {
                    $this->sendToExcept($server, $request->fd, $task);
                }
                $tasks[$key]['done'] += 1;
                if ($tasks[$key]['done'] >= $server::getFds()->count()) {
                    unset($tasks[$key]);
                }
            }
            cache()->put('websocket_tasks', $tasks);
        }
    }

    private function sendToChannel(WebSocket $server, int $fd, array $task)
    {
        $channel = $server::getFds()->get($fd, 'channel');
        if ($channel == $task['channel'] || $task['channel'] == '*') {
            $server->push($fd, $task['data']);
        }
    }

    private function sendToOnly(WebSocket $server, int $fd, array $task)
    {
        $userId = $server::getFds()->get($fd, 'user_id');
        if (in_array($userId, $task['only'])) {
            $server->push($fd, $task['data']);
        }
    }

    private function sendToExcept(WebSocket $server, int $fd, array $task)
    {
        $userId = $server::getFds()->get($fd, 'user_id');
        if (!in_array($userId, $task['except'])) {
            $server->push($fd, $task['data']);
        }
    }
}
