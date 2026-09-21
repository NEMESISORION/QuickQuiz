<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('live_session_participants', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('live_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('learner_id')->constrained('users')->cascadeOnDelete();
            $table->string('status', 20);
            $table->timestamp('joined_at');
            $table->timestamp('last_seen_at');
            $table->timestamp('left_at')->nullable();
            $table->timestamps();

            $table->unique(['live_session_id', 'learner_id']);
            $table->index(['live_session_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('live_session_participants');
    }
};
