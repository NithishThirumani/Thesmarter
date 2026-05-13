<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAdminOtpTable extends Migration
{
    /**
     * Admin auth module: OTP records with 5-min expiry and attempt limit.
     */
    public function up()
    {
        Schema::create('admin_otp', function (Blueprint $table) {
            $table->id();
            $table->uuid('admin_id');
            $table->string('otp_hash');
            $table->timestamp('expires_at');
            $table->boolean('is_verified')->default(false);
            $table->unsignedTinyInteger('attempt_count')->default(0);
            $table->timestamps();

            $table->foreign('admin_id')->references('id')->on('admin_users')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('admin_otp');
    }
}
