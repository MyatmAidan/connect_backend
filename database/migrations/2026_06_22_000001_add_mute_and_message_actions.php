<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('conversation_user_settings') && ! Schema::hasColumn('conversation_user_settings', 'is_muted')) {
            Schema::table('conversation_user_settings', function (Blueprint $table) {
                $table->boolean('is_muted')->default(false)->after('pin_order');
            });
        }

        Schema::table('messages', function (Blueprint $table) {
            if (! Schema::hasColumn('messages', 'pinned_at')) {
                $table->timestamp('pinned_at')->nullable()->after('read_at');
            }
            if (! Schema::hasColumn('messages', 'pinned_by')) {
                $table->foreignUlid('pinned_by')->nullable()->after('pinned_at')->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('messages', 'edited_at')) {
                $table->timestamp('edited_at')->nullable()->after('pinned_by');
            }
            if (! Schema::hasColumn('messages', 'deleted_at')) {
                $table->timestamp('deleted_at')->nullable()->after('edited_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            if (Schema::hasColumn('messages', 'pinned_by')) {
                $table->dropForeign(['pinned_by']);
            }
            $columns = array_filter(
                ['pinned_at', 'pinned_by', 'edited_at', 'deleted_at'],
                fn (string $column) => Schema::hasColumn('messages', $column),
            );
            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });

        if (Schema::hasColumn('conversation_user_settings', 'is_muted')) {
            Schema::table('conversation_user_settings', function (Blueprint $table) {
                $table->dropColumn('is_muted');
            });
        }
    }
};
