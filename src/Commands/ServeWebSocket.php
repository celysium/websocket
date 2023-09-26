<?php

namespace Celysium\WebSocket\Commands;

use Celysium\WebSocket\Channel;
use Celysium\WebSocket\Server;
use Illuminate\Console\Command;

class ServeWebSocket extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'serve:websocket {--host=} {---port=}';

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

        $host = $this->option('host') ?? config('websocket.server.host');
        $port = $this->option('port') ?? config('websocket.server.port');
        $channelName = $this->option('channel');

        $server = new Server($host, $port);


        $channel = new Channel($channelName);
        $subscribers = $channel->subscribers();


        $server->setChannel($subscribers);

        $server->start();
    }
}
