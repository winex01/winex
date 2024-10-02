<?php

namespace App\Commands;

use Illuminate\Console\Scheduling\Schedule;
use LaravelZero\Framework\Commands\Command;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class CloneRepositoryCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'clone {--ssh= : The SSH URL of the repository}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clones a Git repository and sets up the project';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Step 1: Get the SSH URL from the --ssh option
        $repoUrl = $this->option('ssh');

        if (!$repoUrl) {
            $this->error("The --ssh option is required.");
            return;
        }

        // Extract the repository name from the SSH URL
        $defaultDirName = basename($repoUrl, '.git');

        // Step 2: Ask for the directory/project name with a default value and ensure it's unique
        $directoryName = $this->askForUniqueDirectoryName($defaultDirName);

        // Specify the target directory inside /var/www
        $targetDirectory = "/var/www/$directoryName";

        // Step 3: Clone the repository into the target directory
        $this->info("Cloning repository $repoUrl into $targetDirectory...");
        $this->runProcess(['git', 'clone', $repoUrl, $targetDirectory]);

        // Ensure the cloning was successful and the directory exists
        if (!is_dir($targetDirectory)) {
            $this->error("Failed to clone the repository into $targetDirectory.");
            return;
        }

        // Step 4: Copy .env.example to .env
        $this->info('Setting up .env file...');
        $envExample = "$targetDirectory/.env.example";
        $env = "$targetDirectory/.env";
        if (!file_exists($envExample)) {
            $this->error(".env.example does not exist in $targetDirectory");
            return;
        }
        copy($envExample, $env);

        // Step 5: Set permissions
        $this->info('Setting file permissions...');
        $this->runProcess(['sudo', 'chown', '-R', 'www-data:www-data', "$targetDirectory/storage"]);
        $this->runProcess(['sudo', 'chown', '-R', 'www-data:www-data', "$targetDirectory/bootstrap/cache"]);

        // Check if vendor directory exists, then set permissions if it does
        if (is_dir("$targetDirectory/vendor")) {
            $this->runProcess(['sudo', 'chown', '-R', 'www-data:www-data', "$targetDirectory/vendor"]);
        } else {
            $this->info('Vendor directory does not exist, skipping permission setting for vendor.');
        }

        $this->info('Project setup complete!');
    }

    // Helper method to ask for a unique directory name
    private function askForUniqueDirectoryName($defaultDirName)
    {
        do {
            // Ask for the directory name with a default value
            $directoryName = $this->ask("Enter the directory name (default: $defaultDirName)", $defaultDirName);
            $targetDirectory = "/var/www/$directoryName";

            // Check if the directory already exists
            if (is_dir($targetDirectory)) {
                $this->error("Sorry, the directory '$directoryName' already exists. Please choose a different name.");
            }
        } while (is_dir($targetDirectory)); // Repeat until the directory is unique

        return $directoryName;
    }

    // Helper method to run shell commands and wait for completion
    private function runProcess(array $command)
    {
        $process = new Process($command);
        $process->setTimeout(3600); // Set a generous timeout for longer operations like cloning
        $process->run();

        // Check if the command was successful
        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        // Output the process output to the console
        $this->info($process->getOutput());
    }

    /**
     * Define the command's schedule.
     */
    public function schedule(Schedule $schedule): void
    {
        // $schedule->command(static::class)->everyMinute();
    }
}
