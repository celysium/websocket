<?php

namespace Celysium\WebSocket;

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

        $this->app->bind('websocket-channel', function($app) {
            return new Channel();
        });

        $this->app->bind('server', function($app) {
            return new Server();
        });
    }
}
