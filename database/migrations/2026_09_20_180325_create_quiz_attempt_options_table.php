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
        Schema::create('quiz_attempt_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiz_attempt_question_id')->constrained()->cascadeOnDelete();
            $table->foreignId('source_answer_option_id')->nullable()->constrained('answer_options')->nullOnDelete();
            $table->text('content');
            $table->boolean('is_correct');
            $table->unsignedInteger('position');
            $table->timestamps();

            $table->unique(['quiz_attempt_question_id', 'position']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quiz_attempt_options');
    }
};
