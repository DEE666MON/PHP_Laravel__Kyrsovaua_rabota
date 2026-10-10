<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->foreignId('chat_id')->nullable()->after('id')
                ->constrained('chats')->cascadeOnDelete();
        });
        // Создаём дефолтный чат и переносим в него всё существующее
        $chatId = DB::table('chats')->insertGetId([
            'name'       => 'Общий чат',
            'slug'       => 'general',
            'creator_id' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('messages')->whereNull('chat_id')->update(['chat_id' => $chatId]);
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropForeign(['chat_id']);
            $table->dropColumn('chat_id');
        });
    }
};