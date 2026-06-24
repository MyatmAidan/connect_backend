<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('events', 'display_order')) {
            Schema::table('events', function (Blueprint $table) {
                $table->unsignedInteger('display_order')->default(0)->after('meeting_url');
            });
        }

        if (! Schema::hasColumn('skills', 'image')) {
            Schema::table('skills', function (Blueprint $table) {
                $table->string('image')->nullable()->after('slug');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('events', 'display_order')) {
            Schema::table('events', function (Blueprint $table) {
                $table->dropColumn('display_order');
            });
        }

        if (Schema::hasColumn('skills', 'image')) {
            Schema::table('skills', function (Blueprint $table) {
                $table->dropColumn('image');
            });
        }
    }
};
