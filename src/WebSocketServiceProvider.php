<?php

namespace Celysium\WebSocket;

use Celysium\WebSocket\Commands\ServeWebSocket;
use Celysium\WebSocket\Events\BroadcastEvent;
use Celysium\WebSocket\Events\SendExceptEvent;
use Celysium\WebSocket\Events\SendOnlyEvent;
use Celysium\WebSocket\Listeners\BroadcastListeners as BroadcastListener;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;

class WebSocketServiceProvider extends ServiceProvider
{
    public function boot()
    {
        Event::listen(BroadcastEvent::class, [BroadcastListener::class, 'broadcast']);
        Event::listen(SendExceptEvent::class, [BroadcastListener::class, 'sendExcept']);
        Event::listen(SendOnlyEvent::class, [BroadcastListener::class, 'sendOnly']);

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
    }
}
