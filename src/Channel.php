<?php

namespace Celysium\WebSocket;

use OpenSwoole\Table;
class Channel
{
    private Table $subscribers;
    public function __construct()
    {
        $this->subscribers = new Table(1024);
        $this->subscribers->column('id', Table::TYPE_INT, 4);
        $this->subscribers->column('user_id', Table::TYPE_INT, 4);
        $this->subscribers->create();
    }

    public function subscribers(): Table
    {
        return $this->subscribers;
    }

}