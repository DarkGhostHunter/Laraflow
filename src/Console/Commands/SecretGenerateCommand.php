<?php

namespace DarkGhostHunter\Laraflow\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Illuminate\Contracts\Config\Repository as Config;

class SecretGenerateCommand extends Command
{
    use ConfirmableTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'webhook-secret:generate
                    {--show : Display the key instead of modifying files}
                    {--force : Force the operation to run when in production}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set the Webhook secret string';

    /**
     * Configuration Repository
     *
     * @var \Illuminate\Contracts\Config\Repository
     */
    protected $config;

    /**
     * SecretGenerateCommand constructor.
     *
     * @param \Illuminate\Contracts\Config\Repository $config
     */
    public function __construct(Config $config)
    {
        parent::__construct();

        $this->config = $config;
    }

    /**
     * Execute the console command.
     *
     * @return string|void
     * @throws \Exception
     */
    public function handle()
    {
        $key = $this->generateRandomSecret();

        if ($this->option('show')) {
            return $this->line('<comment>' . $key . '</comment>');
        }

        // @codeCoverageIgnoreStart
        if (! $this->setSecretInEnvironmentFile($key)) {
            return;
        }
        // @codeCoverageIgnoreEnd

        $this->config->set('flow.webhook-secret', $key);

        $this->info('Webhook secret set successfully.');
    }

    /**
     * Generate a random key for the application.
     *
     * @return string
     * @throws \Exception
     */
    protected function generateRandomSecret()
    {
        return bin2hex(random_bytes(16));
    }

    /**
     * Set the application key in the environment file.
     *
     * @param string $key
     * @return bool
     */
    protected function setSecretInEnvironmentFile($key)
    {
        $currentKey = $this->config->get('flow.webhook-secret');

        // @codeCoverageIgnoreStart
        if ($currentKey !== '' && (! $this->confirmToProceed())) {
            return false;
        }
        // @codeCoverageIgnoreEnd

        $this->writeNewEnvironmentFileWith($key);

        return true;
    }

    /**
     * Write a new environment file with the given key.
     *
     * @param string $key
     * @return void
     */
    protected function writeNewEnvironmentFileWith($key)
    {
        $envContent = file_get_contents($this->laravel->environmentFilePath());

        // If the key exist, replace it
        if (strpos($envContent, 'FLOW_WEBHOOK_SECRET') !== false) {
            file_put_contents(
                $this->laravel->environmentFilePath(),
                preg_replace(
                    $this->secretReplacementPattern(),
                    "FLOW_WEBHOOK_SECRET=$key",
                    $envContent
                )
            );
        } // Otherwise append it to the environment file
        else {
            file_put_contents(
                $this->laravel->environmentFilePath(),
                "\nFLOW_WEBHOOK_SECRET=$key",
                FILE_APPEND
            );
        }
    }

    /**
     * Get a regex pattern that will match env FLOW_WEBHOOK_SECRET with any random key.
     *
     * @return string
     */
    protected function secretReplacementPattern()
    {
        $escaped = preg_quote('=' . $this->config->get('flow.webhook-secret'), '/');

        return "/^FLOW_WEBHOOK_SECRET{$escaped}/m";
    }
}
