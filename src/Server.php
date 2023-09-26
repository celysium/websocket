<?php

namespace Celysium\WebSocket;

use OpenSwoole\Http\Request;
use OpenSwoole\WebSocket\Frame;
use OpenSwoole\WebSocket\Server as WebsocketServer;

class Server extends WebsocketServer implements ServerInterface
{
    private Channel $channel;

    public function setChannel(Channel $channel)
    {
        $this->channel = $channel;
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
            $server->channel->subscribers()->set($request->fd, [
                'fd'      => $fd,
                'user_id' => $request->get['user_id'] ?? null,
            ]);
            echo "Connection <{$fd}> opened. Total connections: " . $server->channel->subscribers()->count() . PHP_EOL;
        });
    }

    public function onMessage(): void
    {
        $this->on("Message", function (Server $server, Frame $frame) {
            $user_id = $server->channel->subscribers()->get(strval($frame->fd), "user_id");

            echo "Received message from " . $frame->fd . ($user_id ? (" for user_id : $user_id") : '') . PHP_EOL;
        });
    }

    public function onClose(): void
    {
        $this->on("Close", function (Server $server, int $fd) {
            $server->channel->subscribers()->del($fd);

            echo "Connection close: {$fd}, total connections: " . $server->channel->subscribers()->count() . PHP_EOL;
        });
    }

    public function onDisconnect(): void
    {
        $this->on("Disconnect", function (Server $server, int $fd) {
            $server->channel->subscribers()->del($fd);
            echo "Disconnect: {$fd}, total connections: " . $server->channel->subscribers()->count() . PHP_EOL;
        });
    }

    public function broadcast(string $data): void
    {
        foreach ($this->channel->subscribers() as $key => $value) {
            $this->push($key, $data);
        }
    }

    public function sendOnly(array $users, $data): void
    {
        foreach ($this->channel->subscribers() as $key => $value) {
            if (in_array($value['user_id'], $users)) {
                $this->push($key, $data);
            }
        }
    }

    public function sendExcept(array $users, $data): void
    {
        foreach ($this->channel->subscribers() as $key => $value) {
            if (!in_array($value['user_id'], $users)) {
                $this->push($key, $data);
            }
        }
    }

}
