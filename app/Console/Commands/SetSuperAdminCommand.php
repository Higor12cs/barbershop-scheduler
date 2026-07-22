<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:set-super-admin {email} {--revoke : Remove super admin access instead of granting it}')]
class SetSuperAdminCommand extends Command
{
    public function handle(): int
    {
        $email = mb_strtolower(trim((string) $this->argument('email')));
        $user = User::query()->where('email', $email)->first();

        if (! $user) {
            $this->error("User [{$email}] not found.");

            return self::FAILURE;
        }

        $user->is_super_admin = ! $this->option('revoke');
        $user->save();

        $this->info($this->option('revoke')
            ? "Super admin revoked for {$email}."
            : "Super admin granted to {$email}.");

        return self::SUCCESS;
    }
}
