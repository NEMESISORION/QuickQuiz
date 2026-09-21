<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('live_session_responses', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('live_session_participant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            $table->foreignId('answer_option_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_correct');
            $table->unsignedInteger('points_awarded');
            $table->timestamp('answered_at');
            $table->timestamps();

            $table->unique(
                ['live_session_participant_id', 'question_id'],
                'live_response_participant_question_unique',
            );
            $table->index(['question_id', 'answer_option_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('live_session_responses');
    }
};
