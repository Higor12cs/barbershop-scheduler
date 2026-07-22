<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table): void {
            $table->string('whatsapp_provider')->nullable()->after('modules');
            $table->json('whatsapp_config')->nullable()->after('whatsapp_provider');
            $table->string('webhook_secret')->nullable()->after('whatsapp_config');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table): void {
            $table->dropColumn(['whatsapp_provider', 'whatsapp_config', 'webhook_secret']);
        });
    }
};
