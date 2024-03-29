<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_user_name');
            $table->string('invoice_product_name');
            $table->string('invoice_type_product');
            $table->string('invoice_order_city')->nullable();
            $table->text('invoice_order_address')->nullable();
            $table->string('invoice_order_courier')->nullable();
            $table->string('invoice_courier_name')->nullable();
            $table->string('invoice_courier_cost')->nullable();
            $table->string('invoice_courier_description')->nullable();
            $table->string('invoice_etd')->nullable();
            $table->bigInteger('invoice_qty');
            $table->bigInteger('invoice_price');
            $table->bigInteger('invoice_total_price');
            $table->foreignId('invoice_user_id')->constrained('users');
            $table->foreignId('invoice_product_id');
            $table->foreignId('invoice_order_id')->constrained('orders');
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
        //
    }
};
