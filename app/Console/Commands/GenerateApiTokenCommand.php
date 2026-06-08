<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class GenerateApiTokenCommand extends Command
{
    protected $signature = 'api:token
                            {name : A label for the token, e.g. "CEO Dashboard"}
                            {--email= : Email of the user to own the token (defaults to first admin)}';

    protected $description = 'Mint a Sanctum bearer token for an external integration (e.g. the CEO Dashboard)';

    public function handle(): int
    {
        $email = $this->option('email');

        $user = $email
            ? User::where('email', $email)->first()
            : User::role('admin')->orderBy('created_at')->first();

        if (! $user) {
            $this->error($email
                ? "No user found with email {$email}."
                : 'No admin user found. Pass --email=<address> to choose a token owner.');
            return self::FAILURE;
        }

        $label = $this->argument('name');
        $token = $user->createToken($label)->plainTextToken;

        $this->newLine();
        $this->info("Token '{$label}' created for {$user->name} <{$user->email}>.");
        $this->newLine();
        $this->line('  Copy this now — it will not be shown again:');
        $this->newLine();
        $this->line("  {$token}");
        $this->newLine();
        $this->comment('Send requests with:  Authorization: Bearer <token>');

        return self::SUCCESS;
    }
}
