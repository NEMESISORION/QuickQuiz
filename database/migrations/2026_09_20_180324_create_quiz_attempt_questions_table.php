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
        Schema::create('quiz_attempt_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiz_attempt_id')->constrained()->cascadeOnDelete();
            $table->foreignId('source_question_id')->nullable()->constrained('questions')->nullOnDelete();
            $table->string('type', 24);
            $table->text('prompt');
            $table->text('explanation')->nullable();
            $table->unsignedSmallInteger('points');
            $table->unsignedInteger('position');
            $table->timestamps();

            $table->unique(['quiz_attempt_id', 'position']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quiz_attempt_questions');
    }
};
