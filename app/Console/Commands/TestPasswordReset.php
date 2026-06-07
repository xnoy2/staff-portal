<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Password;

class TestPasswordReset extends Command
{
    protected $signature = 'test:password-reset {email?}';
    protected $description = 'Test the forgot-password email flow';

    public function handle(): void
    {
        $email = $this->argument('email');

        if (! $email) {
            $user = \App\Models\User::orderBy('created_at')->first();
            if (! $user) {
                $this->error('No users in database.');
                return;
            }
            $email = $user->email;
            $this->info("Using first user: {$email}");
        }

        $this->info("Sending reset link to: {$email}");

        try {
            $result = Password::sendResetLink(['email' => $email]);
            $this->info("Result: {$result}");
            $this->info('RESET_LINK_SENT constant = ' . Password::RESET_LINK_SENT);
        } catch (\Throwable $e) {
            $this->error(get_class($e) . ': ' . $e->getMessage());
            $this->error('File: ' . $e->getFile() . ':' . $e->getLine());
            $this->line($e->getTraceAsString());
        }
    }
}
