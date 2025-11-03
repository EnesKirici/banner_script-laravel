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
        {
            Schema::table('users', function (Blueprint $table) {
                $table->integer('login_count')->default(0)->after('password');
                $table->timestamp('last_login_at')->nullable()->after('login_count');
                $table->string('last_ip_address', 45)->nullable()->after('last_login_at');
            });
            }
        }

    /**
     * Reverse the migrations.
     */
    public function down()
        {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn(['login_count', 'last_login_at', 'last_ip_address']);
            });
        }
};
