<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();

            // randevuyu alan user
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // provider (şimdilik users tablosu)
            $table->foreignId('provider_id')->constrained('users')->cascadeOnDelete();

            $table->dateTime('starts_at');
            $table->dateTime('ends_at');

            $table->string('status')->default('scheduled'); // scheduled|cancelled|completed
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(['provider_id', 'starts_at', 'ends_at']);
            $table->index(['user_id', 'starts_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
