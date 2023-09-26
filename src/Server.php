<?php

namespace Celysium\WebSocket;

use Exception;
use OpenSwoole\Constant;
use OpenSwoole\Http\Request;
use OpenSwoole\WebSocket\Frame;
use OpenSwoole\WebSocket\Server as WebsocketServer;

class Server extends WebsocketServer implements ServerInterface
{
    private array $channels;

    private static $server;

    private function __construct(string $host = null, int $port = 0, int $mode = \OpenSwoole\Server::SIMPLE_MODE, int $sockType = Constant::SOCK_TCP)
    {
        parent::__construct($host, $port, $mode, $sockType);
    }
    public static function instance(string $host = null, int $port = 0, int $mode = \OpenSwoole\Server::SIMPLE_MODE, int $sockType = Constant::SOCK_TCP): Server
    {
        if (!self::$server) {
            self::$server = new self($host, $port, $mode, $sockType);
        }

        return self::$server;
    }

    public function setChannel(Channel $channel, string $name)
    {
        $this->channels[$name] = $channel;
    }

    /**
     * @param string $name
     * @return Channel
     * @throws Exception
     */
    public function getChannel(string $name): Channel
    {
        if(isset($this->channels[$name])) {
            return $this->channels[$name];
        }
        throw new Exception("Not found channel $name");
    }

    public function onStart(): void
    {
        $this->on("Start", function () {
            echo "Swoole WebSocket Server is started at " . $this->host . ":" . $this->port . PHP_EOL;
        });
    }

    public function onOpen(): void
    {
        $this->on("Open", function (Server $server, Request $request) {
            $fd = $request->fd;
            $channel = $request->get['channel'] ?? null;
            $server->getChannel($channel)->subscribers()->set($request->fd, [
                'fd'      => $fd,
                'user_id' => $request->get['user_id'] ?? null,
            ]);
            echo "Connection <$fd> opened. Total connections: " . $server->getChannel($channel)->subscribers()->count() . PHP_EOL;
        });
    }

    public function onMessage(): void
    {
        $this->on("Message", function (Server $server, Frame $frame) {
            print_r($frame);
            $channel =  null;
            $user_id = $server->getChannel($channel)->subscribers()->get(strval($frame->fd), "user_id");

            echo "Received message from " . $frame->fd . ($user_id ? (" for user_id : $user_id") : '') . PHP_EOL;
        });
    }

    public function onClose(): void
    {
        $this->on("Close", function (Server $server, Request $request, int $fd) {

            $channel = $request->get['channel'] ?? null;
            $server->getChannel($channel)->subscribers()->del($fd);

            echo "Connection close: $fd, total connections: " . $server->getChannel($channel)->subscribers()->count() . PHP_EOL;
        });
    }

    public function onDisconnect(): void
    {
        $this->on("Disconnect", function (Server $server, Request $request, int $fd) {
            $channel = $request->get['channel'] ?? null;
            $server->getChannel($channel)->subscribers()->del($fd);
            echo "Disconnect: $fd, total connections: " . $server->getChannel($channel)->subscribers()->count() . PHP_EOL;
        });
    }

    /**
     * @param string $channel
     * @param string $data
     * @return void
     * @throws Exception
     */
    public function broadcast(string $channel, string $data): void
    {
        foreach (self::$server->getChannel($channel)->subscribers() as $key => $value) {
            $this->push($key, $data);

            echo "Send message from " . $key . ($value['user_id'] ? " for user : " . $value['user_id'] : '') . PHP_EOL;
        }
    }

    /**
     * @param string $channel
     * @param array $users
     * @param string $data
     * @return void
     * @throws Exception
     */
    public function sendOnly(string $channel, array $users, string $data): void
    {
        foreach (self::$server->getChannel($channel)->subscribers() as $key => $value) {
            if (in_array($value['user_id'], $users)) {
                $this->push($key, $data);

                echo "Send message from " . $key . ($value['user_id'] ? " for user : " . $value['user_id'] : '') . PHP_EOL;
            }
        }
    }

    /**
     * @param string $channel
     * @param array $users
     * @param string $data
     * @throws Exception
     */
    public function sendExcept(string $channel, array $users, string $data): void
    {
        foreach (self::$server->getChannel($channel)->subscribers() as $key => $value) {
            if (!in_array($value['user_id'], $users)) {
                $this->push($key, $data);

                echo "Send message from " . $key . ($value['user_id'] ? " for user : " . $value['user_id'] : '') . PHP_EOL;
            }
        }
    }

}
