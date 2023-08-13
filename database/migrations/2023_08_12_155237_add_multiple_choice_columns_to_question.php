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
        Schema::table('questions', function (Blueprint $table) {
            $table->json('multiple_choice_options')->nullable();
            $table->string('multiple_choice_answer')->nullable();
            $table->integer('question_type')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('question', function (Blueprint $table) {
            $table->dropColumn('multiple_choice_options');
            $table->dropColumn('multiple_choice_answer');
            $table->dropColumn('question_type');
        });
    }
};
