<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Password;

class TestPasswordReset extends Command
{
    protected $signature = 'test:password-reset {email}';
    protected $description = 'Test the forgot-password email flow';

    public function handle(): void
    {
        $email = $this->argument('email');
        $this->info("Sending reset link to: {$email}");

        try {
            $result = Password::sendResetLink(['email' => $email]);
            $this->info("Result: {$result}");
        } catch (\Throwable $e) {
            $this->error(get_class($e) . ': ' . $e->getMessage());
            $this->error('File: ' . $e->getFile() . ':' . $e->getLine());
            $this->line($e->getTraceAsString());
        }
    }
}
