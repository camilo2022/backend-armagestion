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
        Schema::create('payments', function (Blueprint $table) {
            $table->id()->comment('Identifier of the register');
            $table->morphs('model');
            $table->string('pay_account')->comment('Number of the account');
            $table->string('pay_value')->comment('Value of the payment');
            $table->decimal('pay_discount_rate', 5, 2);
            $table->date('pay_date')->comment('Date of the payment');
            $table->unsignedBigInteger('cycle_id')->comment('Identifier of the cycle');
            $table->unsignedBigInteger('focus_id')->comment('Identifier of the campaign');
            $table->boolean('real_payment');
            $table->foreign('cycle_id')->references('id')->on('cycles')->onDelete('cascade');
            $table->foreign('focus_id')->references('id')->on('focus')->onDelete('cascade');
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
        Schema::dropIfExists('payments');
    }
};
