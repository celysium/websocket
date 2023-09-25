<?php

namespace Celysium\WebSocket\Facades;

use Illuminate\Support\Facades\Facade;

class Server extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'server';
    }
}
