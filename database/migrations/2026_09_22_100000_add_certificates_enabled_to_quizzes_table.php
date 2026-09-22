<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quizzes', function (Blueprint $table): void {
            $table->boolean('certificates_enabled')->default(false)->after('review_policy');
        });
    }

    public function down(): void
    {
        Schema::table('quizzes', function (Blueprint $table): void {
            $table->dropColumn('certificates_enabled');
        });
    }
};
