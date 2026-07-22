<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:create-user {name} {email} {password} {--super : Grant super admin access}')]
class CreateUserCommand extends Command
{
    public function handle(): int
    {
        $name = trim((string) $this->argument('name'));
        $email = mb_strtolower(trim((string) $this->argument('email')));
        $password = (string) $this->argument('password');

        if ($name === '' || $email === '' || $password === '') {
            $this->error('Name, email and password are required.');

            return self::FAILURE;
        }

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('The email must be valid.');

            return self::FAILURE;
        }

        if (User::query()->where('email', $email)->exists()) {
            $this->error("User [{$email}] already exists.");

            return self::FAILURE;
        }

        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => $password,
        ]);

        $user->is_super_admin = (bool) $this->option('super');
        $user->save();

        $this->info('User created.');
        $this->line("UUID: {$user->uuid}");
        $this->line("Email: {$user->email}");

        return self::SUCCESS;
    }
}
