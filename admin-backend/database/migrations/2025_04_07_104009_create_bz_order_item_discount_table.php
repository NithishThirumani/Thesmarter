<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBzOrderItemDiscountTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('bz_order_item_discount')) {
            return;
        }

        Schema::create('bz_order_item_discount', function (Blueprint $table) {
            $table->id();
            $table->integer('product_id');
            $table->integer('order_id');
            $table->integer('discount_detail_is');
            $table->decimal('value',10,2)->default(0.00);
            $table->double('amount',15,2)->default(0.00);
            $table->string('name',50)->nullable();
            $table->char('type',2)->default('P');
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
        Schema::dropIfExists('bz_order_item_discount');
    }
}
