<?php
namespace Celysium\WebSocket\Facades;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Facade;

/**
 * @method static upload(UploadedFile $file): ?string
 * @method static uploadByUrl(string $url): ?string
 */
class WebSocket extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'websocket';
    }
}
