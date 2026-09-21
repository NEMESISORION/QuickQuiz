<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('live_sessions', function (Blueprint $table): void {
            $table->foreignId('current_question_id')
                ->nullable()
                ->after('status')
                ->constrained('questions')
                ->nullOnDelete();
            $table->timestamp('current_question_started_at')->nullable()->after('current_question_id');
        });
    }

    public function down(): void
    {
        Schema::table('live_sessions', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('current_question_id');
            $table->dropColumn('current_question_started_at');
        });
    }
};
