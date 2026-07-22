<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table): void {
            $table->foreign('recurrence_id')->references('id')->on('recurrences')->nullOnDelete();
            $table->index('recurrence_id');
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table): void {
            $table->dropForeign(['recurrence_id']);
            $table->dropIndex(['recurrence_id']);
        });
    }
};
