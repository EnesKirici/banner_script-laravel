<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('blocked_ips', function (Blueprint $table) {
            $table->string('ban_type', 20)->default('manual')->after('reason');
            $table->unsignedInteger('request_count')->default(0)->after('ban_type');
            $table->unsignedSmallInteger('violation_count')->default(0)->after('request_count');
        });
    }

    public function down(): void
    {
        Schema::table('blocked_ips', function (Blueprint $table) {
            $table->dropColumn(['ban_type', 'request_count', 'violation_count']);
        });
    }
};
