<?php

namespace Celysium\WebSocket\Commands;

use Illuminate\Console\Command;

class GenerateKeysWebSocket extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'websocket:keys';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'generate websocket keys';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $descriptors = array(
            0 => array("pipe", "r"),  // stdin - read mode
            1 => array("pipe", "w"),  // stdout - write mode
            2 => array("pipe", "w")   // stderr - write mode
        );

        $path = storage_path();

        $command = "openssl req -x509 -newkey rsa:2048 -nodes -keyout $path/websocket_key.pem -out $path/websocket_cert.pem -days 365";

        $process = proc_open($command, $descriptors, $pipes);

        if (is_resource($process)) {
            // Provide dynamic inputs
            fwrite($pipes[0], "IR\n");            // Country Name (2letter code)
            fwrite($pipes[0], "Tehran\n");        // State or Province Name (full name)
            fwrite($pipes[0], "Iran\n");          // Locality Name (e.g., city)
            fwrite($pipes[0], "celysium\n");          // Organization Name (e.g., company)
            fwrite($pipes[0], "Tech\n");          // Organization Unit Name (e.g., company)
            fwrite($pipes[0], "Assistant\n");     // Common Name (e.g., server FQDN or YOUR name)
            fwrite($pipes[0], "info@celysium.com\n"); // Email Address
            fclose($pipes[0]);

            // Read output
            $output = stream_get_contents($pipes[1]);
            fclose($pipes[1]);

            // Read error (if any)
            $error = stream_get_contents($pipes[2]);
            fclose($pipes[2]);

            // Close the process
            proc_close($process);
        }

        $this->info("Key and certificate files generated successfully.");
    }
}
