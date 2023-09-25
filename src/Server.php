<?php

namespace Celysium\WebSocket;

use Illuminate\Support\Facades\Facade;
use OpenSwoole\WebSocket\Server as WebsocketServer;
use OpenSwoole\Constant;

class Server extends Facade
{
    private WebsocketServer $server;

    public function __construct(private $host = null, private $port = null)
    {
        $this->server = new WebsocketServer(
            $this->host ?? config('websocket.server.host'),
            $this->port ?? config('websocket.server.port'),
                WebsocketServer::SIMPLE_MODE,
            Constant::SOCK_TCP
        );
    }

    public function server(): WebsocketServer
    {
        return $this->server;
    }
}