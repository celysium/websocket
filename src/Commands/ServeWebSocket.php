<?php

namespace Celysium\WebSocket\Commands;

use Celysium\WebSocket\WebSocket;
use Illuminate\Console\Command;

class ServeWebSocket extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'websocket:serve {--host=} {--port=}';

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

        $server = new WebSocket($host, $port);

        if (!
            file_exists(storage_path('websocket_cert.pem')) &&
            file_exists(storage_path('websocket_key.pem'))
        ) {
            $this->error('The keys not exists, please run command websocket:keys');
            return;
        } else {
            $server->set([
                'ssl_cert_file' => storage_path('websocket_cert.pem'),
                'ssl_key_file'  => storage_path('websocket_key.pem'),
            ]);
        }

        $server->onStart();
        $server->onOpen();
        $server->onMessage();
        $server->onClose();
        $server->onDisconnect();
        $server->start();
    }
}
