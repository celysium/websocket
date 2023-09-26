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
            echo "Swoole WebSocket Server is started at " . $this->host . ":" . $this->port . "\n";
        });
    }

    public function onOpen(Request $request): void
    {
        $this->on("Open", function () use ($request) {
            $fd = $request->fd;
            $this->channel->subscribers()->set($request->fd, [
                'fd' => $fd,
                'user_id' => $request->get['user_id'] ?? null,
            ]);
            echo "Connection <{$fd}> opened. Total connections: " . $this->channel->subscribers()->count() . "\n";
        });
    }

    public function onMessage(Frame $frame): void
    {
        $this->on("Message", function () use ($frame) {
            $user_id = $this->channel->subscribers()->get(strval($frame->fd), "user_id");

            echo "Received from " . $frame->fd . ", user_id : $user_id" . PHP_EOL;

            $this->broadcast($frame->data);
        });
    }

    public function onClose(int $fd): void
    {
        $this->on("Close", function () use ($fd) {
            $this->channel->subscribers()->del($fd);
            echo "Connection close: {$fd}, total connections: " . $this->channel->subscribers()->count() . "\n";
        });
    }

    public function onDisconnect(int $fd): void
    {
        $this->on("Disconnect", function () use ($fd) {
            $this->channel->subscribers()->del($fd);
            echo "Disconnect: {$fd}, total connections: " . $this->channel->subscribers()->count() . "\n";
        });
    }

    public function broadcast(string $data)
    {
        foreach ($this->channel->subscribers() as $key => $value) {
            $this->push($key, $data);
        }
    }
}