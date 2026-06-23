<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conversation_user_settings', function (Blueprint $table) {
            $table->boolean('is_muted')->default(false)->after('pin_order');
        });

        Schema::table('messages', function (Blueprint $table) {
            $table->timestamp('pinned_at')->nullable()->after('read_at');
            $table->foreignUlid('pinned_by')->nullable()->after('pinned_at')->constrained('users')->nullOnDelete();
            $table->timestamp('edited_at')->nullable()->after('pinned_by');
            $table->timestamp('deleted_at')->nullable()->after('edited_at');
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropForeign(['pinned_by']);
            $table->dropColumn(['pinned_at', 'pinned_by', 'edited_at', 'deleted_at']);
        });

        Schema::table('conversation_user_settings', function (Blueprint $table) {
            $table->dropColumn('is_muted');
        });
    }
};
