<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserCompanyRolesV2Table extends Migration
{
    public function up()
    {
        Schema::create('user_company_roles_v2', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id')->index();
            $table->unsignedBigInteger('company_id')->index();
            $table->unsignedBigInteger('role_id')->index();
            $table->unsignedTinyInteger('status')->default(1);
            $table->timestamps();

            $table->unique(['user_id', 'company_id']);
            $table->foreign('role_id')->references('id')->on('roles_v2')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('user_company_roles_v2');
    }
}
