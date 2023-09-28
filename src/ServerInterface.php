<?php

namespace Celysium\WebSocket;

interface ServerInterface
{
    public function onStart(): void;

    public function onOpen(): void;

    public function onMessage(): void;

    public function onClose(): void;

    public function onDisconnect(): void;

    public function broadcast(string $channel, string $data): void;

    public function sendOnly(array $users, string $data): void;

    public function sendExcept(array $users, string $data): void;
}