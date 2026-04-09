<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('security_logs', function (Blueprint $table) {
            $table->id();
            $table->string('ip_address', 45);
            $table->string('event_type', 30);
            $table->string('description');
            $table->unsignedInteger('request_count')->default(0);
            $table->string('user_agent')->nullable();
            $table->string('url')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('ip_address');
            $table->index('event_type');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('security_logs');
    }
};
