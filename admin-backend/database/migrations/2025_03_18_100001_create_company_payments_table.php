<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Admin company module: company_payments — selected payment methods per company.
 */
class CreateCompanyPaymentsTable extends Migration
{
    public function up()
    {
        if (Schema::hasTable('company_payments')) {
            return;
        }
        Schema::create('company_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('payment_method_id');
            $table->timestamps();

            $table->unique(['company_id', 'payment_method_id']);
            $table->foreign('company_id')->references('company_id')->on('company_detail')->onDelete('cascade');
            $table->foreign('payment_method_id')->references('payment_id')->on('payment_methods')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('company_payments');
    }
}
