<?php

namespace App\Commands;

use LaravelZero\Framework\Commands\Command;

class SetPermissionCommand extends Command
{
    protected $signature = 'permission {directory : The target directory}';
    protected $description = 'Set permissions for the specified project directory';

    public function handle()
    {
        $targetDirectory = $this->argument('directory');
        $this->info('Setting file permissions...');

        // Set permissions for storage and bootstrap/cache
        $this->runProcess(['sudo', 'chown', '-R', 'www-data:www-data', "$targetDirectory/storage"]);
        $this->runProcess(['sudo', 'chown', '-R', 'www-data:www-data', "$targetDirectory/bootstrap/cache"]);
        $this->runProcess(['sudo', 'chmod', '-R', '775', "$targetDirectory/storage"]);
        $this->runProcess(['sudo', 'chmod', '-R', '775', "$targetDirectory/bootstrap/cache"]);

        // Handle vendor directory
        // if (is_dir("$targetDirectory/vendor")) {
        //     $this->runProcess(['sudo', 'chown', '-R', 'www-data:www-data', "$targetDirectory/vendor"]);
        //     $this->runProcess(['sudo', 'chmod', '-R', '775', "$targetDirectory/vendor"]);
        // } else {
        //     $this->info('Vendor directory does not exist, skipping permission setting for vendor.');
        // }

        $this->info('Permissions set successfully.');

        // // Show permissions for the target directory
        // $this->info("Checking permissions for $targetDirectory:");
        // $this->runProcess(['ls', '-l', "$targetDirectory"]);
    }

    protected function runProcess(array $command)
    {
        $process = new \Symfony\Component\Process\Process($command);
        $process->run();

        if (!$process->isSuccessful()) {
            $this->error($process->getErrorOutput());
            exit(1);
        }

        $this->info($process->getOutput());
    }
}
