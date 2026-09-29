<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('content_view_uniques');
        Schema::dropIfExists('content_view_stats');
        Schema::dropIfExists('content_view_logs');

        if (! Schema::hasTable('content_views')) {
            Schema::create('content_views', function (Blueprint $table) {
                $table->id();
                $table->string('content_type', 32);
                $table->unsignedBigInteger('content_id');
                $table->unsignedBigInteger('user_id')->nullable();
                $table->string('device_type', 32);
                $table->string('device_id', 191)->nullable();
                $table->timestamps();

                $table->index(['content_type', 'content_id'], 'idx_content_views_content');
                $table->index(['user_id', 'content_type', 'content_id'], 'idx_content_views_user_content');
                $table->index('device_type', 'idx_content_views_device');
                $table->index('created_at', 'idx_content_views_created');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('content_views');
    }
};
