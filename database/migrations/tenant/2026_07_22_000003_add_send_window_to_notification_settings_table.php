<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notification_settings', function (Blueprint $table): void {
            $table->unsignedSmallInteger('send_window_start')->default(480)->after('recurrence_horizon_days');
            $table->unsignedSmallInteger('send_window_end')->default(1200)->after('send_window_start');
        });
    }

    public function down(): void
    {
        Schema::table('notification_settings', function (Blueprint $table): void {
            $table->dropColumn(['send_window_start', 'send_window_end']);
        });
    }
};
