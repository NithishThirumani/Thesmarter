<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->unsignedBigInteger('executive_id')->nullable();

            $table->string('order_no');
            $table->integer('order_type_id');

            $table->decimal('net_total', 10, 2);
            $table->decimal('total_discount', 10, 2)->default(0.00);
            $table->decimal('total_tax', 10, 2);
            $table->decimal('total_charges', 10, 2)->default(0.00);

            $table->decimal('grand_total', 10, 2);
            $table->decimal('amount_paid', 10, 2)->default(0.00);
            $table->decimal('amount_due', 10, 2)->storedAs('grand_total - amount_paid');

            $table->string('status', 20);
            $table->timestamp('order_date')->useCurrent();

            $table->string('payment_terms', 50)->nullable();
            
            $table->boolean('is_viewed')->default(false);
            $table->boolean('is_subscription')->default(false);
            
            $table->timestamps();

            // Foreign keys
            // $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            // $table->foreign('executive_id')->references('user_id')->on('users')->onDelete('set null');
            
            // Composite unique key
            $table->unique(['company_id', 'order_no']);
        });
         // Add CHECK constraint on status (Note: this works for MySQL 8.0+ and PostgreSQL)
         DB::statement("ALTER TABLE orders ADD CONSTRAINT chk_order_status CHECK (status IN ('draft', 'paid', 'partially_paid', 'canceled'))");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('orders');
    }
}
