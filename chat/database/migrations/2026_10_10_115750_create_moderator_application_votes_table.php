<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('moderator_application_votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')
                ->constrained('moderator_applications')->cascadeOnDelete();
            $table->foreignId('voter_id')->constrained('users')->cascadeOnDelete();
            $table->boolean('approve');
            $table->timestamps();
            $table->unique(['application_id', 'voter_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('moderator_application_votes');
    }
};