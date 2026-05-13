<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAdminRefreshTokensTable extends Migration
{
    /**
     * Admin auth module: refresh token storage for JWT refresh flow.
     */
    public function up()
    {
        Schema::create('admin_refresh_tokens', function (Blueprint $table) {
            $table->id();
            $table->uuid('admin_id');
            $table->string('token_hash', 64);
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->foreign('admin_id')->references('id')->on('admin_users')->onDelete('cascade');
            $table->index(['token_hash', 'expires_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('admin_refresh_tokens');
    }
}
