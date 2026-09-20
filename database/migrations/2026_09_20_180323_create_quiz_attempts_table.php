<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('quiz_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiz_id')->constrained()->restrictOnDelete();
            $table->foreignId('learner_id')->constrained('users')->restrictOnDelete();
            $table->string('status', 24);
            $table->unsignedSmallInteger('attempt_number');
            $table->timestamp('started_at');
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->unsignedSmallInteger('duration_minutes_snapshot')->nullable();
            $table->unsignedTinyInteger('pass_percentage_snapshot');
            $table->string('review_policy_snapshot', 24);
            $table->unsignedInteger('max_score');
            $table->unsignedInteger('score')->nullable();
            $table->boolean('passed')->nullable();
            $table->timestamps();

            $table->unique(['quiz_id', 'learner_id', 'attempt_number']);
            $table->index(['learner_id', 'status', 'started_at']);
            $table->index(['quiz_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quiz_attempts');
    }
};
