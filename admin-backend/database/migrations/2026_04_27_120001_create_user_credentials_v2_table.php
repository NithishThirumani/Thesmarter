<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserCredentialsV2Table extends Migration
{
    public function up()
    {
        Schema::create('user_credentials_v2', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id')->index();
            $table->string('login_type', 16);
            $table->string('login_value', 191);
            $table->string('password_hash');
            $table->timestamps();

            $table->index('login_value');
            $table->index(['user_id', 'login_type']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('user_credentials_v2');
    }
}
