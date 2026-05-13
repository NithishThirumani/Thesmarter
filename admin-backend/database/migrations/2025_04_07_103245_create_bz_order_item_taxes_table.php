<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBzOrderItemTaxesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('bz_order_item_taxes', function (Blueprint $table) {
            $table->id();
            $table->integer('product_id');
            $table->integer('order_id');
            $table->integer('tax_detail_id');
            $table->decimal('value',10,2)->default(0.00);
            $table->double('amount',15,2)->default(0.00);
            $table->string('name',50)->nullable();
            $table->timestamps();

            // Composite unique key
            $table->unique(['product_id', 'order_id','tax_detail_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('bz_order_item_taxes');
    }
}
