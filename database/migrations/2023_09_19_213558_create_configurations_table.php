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
        Schema::create('configurations', function (Blueprint $table) {
            $table->id();
            $table->json('cycle_code');
            $table->unsignedBigInteger('focus_id');
            $table->unsignedBigInteger('user_interactions_min_count');
            $table->unsignedBigInteger('user_interactions_max_count');
            $table->decimal('effectiveness_percentage', 5, 2);
            $table->decimal('payment_agreement_percentage', 5, 2);
            $table->decimal('payment_agreement_true_percentage', 5, 2);
            $table->decimal('type_service_percentage', 5, 2)->nullable();
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
        Schema::dropIfExists('configurations');
    }
};
