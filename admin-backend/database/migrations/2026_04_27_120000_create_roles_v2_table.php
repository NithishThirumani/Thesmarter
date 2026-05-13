<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRolesV2Table extends Migration
{
    public function up()
    {
        Schema::create('roles_v2', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('role_name', 64)->unique();
            $table->string('role_type', 32)->default('system');
            $table->unsignedBigInteger('company_id')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('roles_v2');
    }
}
