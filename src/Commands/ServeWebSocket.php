<?php

namespace Celysium\WebSocket\Commands;

use Celysium\WebSocket\Facades\Channel;
use Celysium\WebSocket\Server;
use Illuminate\Console\Command;

class ServeWebSocket extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'serve:websocket';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'serve server websocket';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $server = new Server($this->option('host'), $this->option('port'));

        $channel = Channel::subscribers();

        $server->setChannel($channel);


    }
}
