<?php

namespace Database\Seeders;

use App\Models\NotificationSetting;
use App\Models\Tenant;
use App\Models\User;
use App\Support\Modules;
use App\Support\Permissions;
use Database\Seeders\Tenant\DemoDataSeeder;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name' => 'Super Admin',
            'email' => 'admin@admin.com',
            'password' => 'password',
            'must_change_password' => false,
        ])->forceFill(['is_super_admin' => true])->save();

        $tenant = Tenant::create([
            'name' => 'Barbearia Demo',
            'slug' => 'demo',
            'active' => true,
            'access_until' => null,
            'modules' => Modules::defaults(),
            'whatsapp_provider' => 'log',
        ]);

        $demo = User::create([
            'name' => 'Demo',
            'email' => 'demo@demo.com',
            'password' => 'password',
            'must_change_password' => false,
        ]);

        $demo->tenants()->syncWithoutDetaching([$tenant->id]);

        $tenant->run(function () use ($demo): void {
            $demo->assignRole(Permissions::ROLE_ADMIN);

            NotificationSetting::current()->update([
                'booking_enabled' => true,
                'reminder_enabled' => true,
                'confirmation_enabled' => true,
            ]);

            (new DemoDataSeeder)->run();
        });
    }
}
