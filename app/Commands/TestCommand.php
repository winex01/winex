<?php

namespace App\Commands;

use LaravelZero\Framework\Commands\Command;

class TestCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:test-command';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test the larasail command';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $command = "sudo sh /etc/.larasail/host";

        $this->info('Running larasail host setup...');

        $process = proc_open($command, [
            1 => ['pipe', 'w'], // stdout
            2 => ['pipe', 'w'], // stderr
        ], $pipes);

        if (is_resource($process)) {
            // Read the output
            $output = '';
            while ($line = fgets($pipes[1])) {
                $output .= $line;
                $this->info($line); // Output each line immediately
            }

            // Capture any errors
            $error = stream_get_contents($pipes[2]);

            fclose($pipes[1]);
            fclose($pipes[2]);

            $return_value = proc_close($process);

            if ($return_value === 0) {
                $this->info("Command completed successfully.");
            } else {
                $this->error($error ?: "Command failed with exit code $return_value.");
            }
        } else {
            $this->error("Failed to start process.");
        }
    }

}
