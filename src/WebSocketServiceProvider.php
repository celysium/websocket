<?php

namespace Celysium\WebSocket;

use Celysium\WebSocket\Commands\GenerateKeysWebSocket;
use Celysium\WebSocket\Commands\ServeWebSocket;
use Illuminate\Support\ServiceProvider;

class WebSocketServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../config/websocket.php' => config_path('websocket.php'),
        ], 'websocket-config');

    }

    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/websocket.php', 'websocket'
        );

        $this->commands(ServeWebSocket::class);
        $this->commands(GenerateKeysWebSocket::class);
    }
}
