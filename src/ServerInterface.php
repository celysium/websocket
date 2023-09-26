<?php

namespace Celysium\WebSocket;

use OpenSwoole\Http\Request;
use OpenSwoole\WebSocket\Frame;
interface ServerInterface
{
    public function onStart(): void;

    public function onOpen(Request $request): void;

    public function onMessage(Frame $frame): void;

    public function onClose(int $fd): void;

    public function onDisconnect(int $fd): void;

    public function sendOnly(array $users, $data): void;

    public function sendExcept(array $users, $data): void;
}