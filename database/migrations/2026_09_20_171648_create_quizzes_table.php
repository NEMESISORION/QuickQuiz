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
        Schema::create('quizzes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('educator_id')->constrained('users')->restrictOnDelete();
            $table->string('title', 160);
            $table->text('description')->nullable();
            $table->string('status', 24)->default('draft');
            $table->unsignedSmallInteger('duration_minutes')->nullable();
            $table->unsignedTinyInteger('pass_percentage')->default(70);
            $table->unsignedSmallInteger('max_attempts')->default(1);
            $table->boolean('shuffle_questions')->default(false);
            $table->boolean('shuffle_answers')->default(false);
            $table->string('review_policy', 24)->default('immediately');
            $table->timestamp('opens_at')->nullable();
            $table->timestamp('closes_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['educator_id', 'status']);
            $table->index(['status', 'opens_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quizzes');
    }
};
