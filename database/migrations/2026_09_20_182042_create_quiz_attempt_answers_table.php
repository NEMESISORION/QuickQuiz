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
        Schema::create('quiz_attempt_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiz_attempt_id')->constrained()->cascadeOnDelete();
            $table->foreignId('quiz_attempt_question_id')->constrained()->cascadeOnDelete();
            $table->foreignId('quiz_attempt_option_id')->constrained()->cascadeOnDelete();
            $table->timestamp('answered_at');
            $table->timestamps();

            $table->unique(['quiz_attempt_id', 'quiz_attempt_question_id']);
            $table->index(['quiz_attempt_id', 'answered_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quiz_attempt_answers');
    }
};
