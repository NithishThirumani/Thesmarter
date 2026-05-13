<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrderItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->integer('product_id');
            $table->integer('price_id');
            $table->integer('tax_id')->nullable();
            $table->integer('discount_id')->nullable();
            
            
            $table->double('unit_price', 15, 2)->default(0.00);
            $table->double('base_amount', 15, 2)->default(0.00);
            $table->decimal('quantity', 10, 2);
            $table->double('item_net', 15, 2)->default(0.00);
            $table->double('item_discount', 15, 2)->default(0.00);
            $table->double('item_tax', 15, 2)->default(0.00);
            $table->double('item_charge', 15, 2)->default(0.00);
            $table->double('item_total', 15, 2)->default(0.00);
            
            $table->boolean('is_dynamically_priced')->default(false);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('order_items');
    }
}
