<?php

namespace Celysium\WebSocket;

interface ServerInterface
{
    public function onStart(): void;

    public function onOpen(): void;

    public function onMessage(): void;

    public function onClose(): void;

    public function onDisconnect(): void;

    public static function broadcast(array $data, string $channel = '*'): void;

    public static function sendOnly(array $data, array $users): void;

    public static function sendExcept(array $data, array $users): void;
}