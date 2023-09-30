<?php

namespace Celysium\WebSocket;

use Celysium\WebSocket\Events\IncomeMessageEvent;
use Exception;
use OpenSwoole\Constant;
use OpenSwoole\Http\Request;
use OpenSwoole\Table;
use OpenSwoole\WebSocket\Frame;
use OpenSwoole\WebSocket\Server as WebsocketServer;

class Server extends WebsocketServer implements ServerInterface
{
    private static Table $table;
    private Channel $channel;

    private static $server;

    private function __construct(string $host, int $port = 0, int $mode = \OpenSwoole\Server::SIMPLE_MODE, int $sockType = Constant::SOCK_TCP)
    {

        self::$table = new Table(1024);
        $this->subscribers->column('fd', Table::TYPE_INT, 4);
        $this->subscribers->column('channel', Table::TYPE_STRING, 32);
        $this->subscribers->column('user_id', Table::TYPE_INT, 4);
        $this->subscribers->create();

        // create table
        parent::__construct($host, $port, $mode, $sockType);
    }

    public static function instance(string $host = null, int $port = 0, int $mode = \OpenSwoole\Server::SIMPLE_MODE, int $sockType = Constant::SOCK_TCP): Server
    {
        if (!self::$server) {
            self::$server = new self($host ?? config('websocket.server.host'), $port, $mode, $sockType);
        }

        return self::$server;
    }

    public function setChannel(Channel $channel)
    {
        $this->channel = $channel;
    }

    /**
     * @return Channel
     * @throws Exception
     */
    public function getChannel(): Channel
    {
        return $this->channel;
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
            $fd = $request->fd;
            $server->getChannel()->subscribers()->set($request->fd, [
                'fd'      => $fd,
                'channel' => $request->get['channel'] ?? null,
                'user_id' => $request->get['user_id'] ?? null,
            ]);
            echo "Connection <$fd> opened. Total connections: " . $server->getChannel()->subscribers()->count() . PHP_EOL;
        });
    }

    public function onMessage(): void
    {
        $this->on("Message", function (Server $server, Frame $frame) {
            $data = json_decode($frame->data);

            $user_id = $data->user_id;
            echo "Received message from " . $frame->fd . ($user_id ? (" for user_id : $user_id") : '') . PHP_EOL;

            event(new IncomeMessageEvent($frame->fd, $data->channel, $user_id, $data->payload));
        });
    }

    public function onClose(): void
    {
        $this->on("Close", function (Server $server, int $fd) {
            $server->getChannel()->subscribers()->del($fd);

            echo "Connection close: $fd, total connections: " . $server->getChannel()->subscribers()->count() . PHP_EOL;
        });
    }

    public function onDisconnect(): void
    {
        $this->on("Disconnect", function (Server $server, int $fd) {
            $server->getChannel()->subscribers()->del($fd);
            echo "Disconnect: $fd, total connections: " . $server->getChannel()->subscribers()->count() . PHP_EOL;
        });
    }

    /**
     * @param string $channel
     * @param string $data
     * @return void
     */
    public function broadcast(string $channel, string $data): void
    {
        foreach (self::$server->getChannel()->subscribers() as $key => $value) {
            if ($value['channel'] == $channel) {
                $this->push($key, $data);
                echo "Send message from " . $key . ($value['user_id'] ? " for user : " . $value['user_id'] : '') . PHP_EOL;
            }
        }
    }

    /**
     * @param array $users
     * @param string $data
     * @return void
     */
    public function sendOnly(array $users, string $data): void
    {
        foreach (self::$server->getChannel()->subscribers() as $key => $value) {
            if (in_array($value['user_id'], $users)) {
                $this->push($key, $data);

                echo "Send message from " . $key . ($value['user_id'] ? " for user : " . $value['user_id'] : '') . PHP_EOL;
            }
        }
    }

    /**
     * @param array $users
     * @param string $data
     */
    public function sendExcept(array $users, string $data): void
    {
        foreach (self::$server->getChannel()->subscribers() as $key => $value) {
            if (!in_array($value['user_id'], $users)) {
                $this->push($key, $data);

                echo "Send message from " . $key . ($value['user_id'] ? " for user : " . $value['user_id'] : '') . PHP_EOL;
            }
        }
    }

}
