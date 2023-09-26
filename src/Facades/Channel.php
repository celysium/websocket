<?php

namespace Celysium\WebSocket\Facades;

use Illuminate\Support\Facades\Facade;
use OpenSwoole\Table;
/**
 * @method static subscribers(): Table
 */
class Channel extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'websocket-channel';
    }
}
