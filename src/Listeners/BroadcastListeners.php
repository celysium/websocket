<?php

namespace Celysium\WebSocket\Listeners;

use Celysium\WebSocket\Events\BroadcastEvent as BroadcastEvent;
use Celysium\WebSocket\Events\SendExceptEvent;
use Celysium\WebSocket\Events\SendOnlyEvent;
use Celysium\WebSocket\Server;

class BroadcastListeners
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function broadcast(BroadcastEvent $event): void
    {
        Server::instance()->broadcast($event->channel, $event->data);
    }

    /**
     * Handle the event.
     */
    public function sendExcept(SendExceptEvent $event): void
    {
        Server::instance()->sendExcept($event->users, $event->data);
    }

    /**
     * Handle the event.
     */
    public function sendOnly(SendOnlyEvent $event): void
    {
        Server::instance()->sendOnly($event->users, $event->data);
    }
}
