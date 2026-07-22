<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_settings', function (Blueprint $table): void {
            $table->id();
            $table->boolean('reminder_enabled')->default(false);
            $table->unsignedInteger('reminder_minutes_before')->default(60);
            $table->text('reminder_template')->nullable();
            $table->boolean('confirmation_enabled')->default(false);
            $table->unsignedInteger('confirmation_minutes_before')->default(1440);
            $table->text('confirmation_template')->nullable();
            $table->boolean('booking_enabled')->default(false);
            $table->text('booking_template')->nullable();
            $table->unsignedInteger('recurrence_horizon_days')->default(30);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_settings');
    }
};
