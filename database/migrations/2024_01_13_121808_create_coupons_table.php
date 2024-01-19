<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCouponsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->decimal('discount_amount')->nullable();
            $table->decimal('min_cart_amount')->nullable();
            $table->smallInteger('discount_percentage')->nullable();
            $table->enum('type', ['global', 'product_specific'])->default('global');
            $table->enum('discount_type', ['amount', 'percentage'])->default('amount');
            $table->json('product_ids')->nullable();
            $table->timestamp('expiration_date')->nullable();
            $table->unsignedInteger('usage_limit')->nullable();
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
        Schema::dropIfExists('coupons');
    }
}
