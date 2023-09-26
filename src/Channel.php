<?php

namespace Celysium\WebSocket;

use OpenSwoole\Table;
class Channel
{
    private array $channels;
    public function __construct(private readonly string $name = 'default')
    {
        $subscribers = new Table(1024);
        $subscribers->column('id', Table::TYPE_INT, 4);
        $subscribers->column('user_id', Table::TYPE_INT, 4);
        $subscribers->create();
        $this->channels[$name] = $subscribers;
    }

    public function subscribers(string $name = null): Table
    {
        return $this->channels[$name ?? $this->name];
    }

    public function channels(): array
    {
        return $this->channels;
    }

}