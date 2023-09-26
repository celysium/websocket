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

    public function onOpen(Request $request): void
    {
        $this->on("Open", function () use ($request) {
            $fd = $request->fd;
            $this->channel->subscribers()->set($request->fd, [
                'fd'      => $fd,
                'user_id' => $request->get['user_id'] ?? null,
            ]);
            echo "Connection <{$fd}> opened. Total connections: " . $this->channel->subscribers()->count() . PHP_EOL;
        });
    }

    public function onMessage(Frame $frame): void
    {
        $this->on("Message", function () use ($frame) {
            $user_id = $this->channel->subscribers()->get(strval($frame->fd), "user_id");

            echo "Received message from " . $frame->fd . ($user_id ? (" for user_id : $user_id") : '') . PHP_EOL;
        });
    }

    public function onClose(int $fd): void
    {
        $this->on("Close", function () use ($fd) {
            $this->channel->subscribers()->del($fd);

            echo "Connection close: {$fd}, total connections: " . $this->channel->subscribers()->count() . PHP_EOL;
        });
    }

    public function onDisconnect(int $fd): void
    {
        $this->on("Disconnect", function () use ($fd) {
            $this->channel->subscribers()->del($fd);
            echo "Disconnect: {$fd}, total connections: " . $this->channel->subscribers()->count() . PHP_EOL;
        });
    }

    public function broadcast(string $data)
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