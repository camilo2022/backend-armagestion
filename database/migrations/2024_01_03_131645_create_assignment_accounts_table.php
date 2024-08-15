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
        Schema::create('assignment_accounts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('peop_id');
            $table->string('acco_code_account');
            $table->string('peop_dni');
            $table->string('peop_name');
            $table->string('peop_lastname');
            $table->unsignedBigInteger('assi_id');
            $table->unsignedBigInteger('alli_id');
            $table->unsignedBigInteger('camp_id');
            $table->string('data_value');
            $table->foreign('peop_id')->references('id')->on('people')->onDelete('cascade');
            $table->foreign('assi_id')->references('id')->on('assignments')->onDelete('cascade');
            $table->foreign('alli_id')->references('id')->on('allies')->onDelete('cascade');
            $table->foreign('camp_id')->references('id')->on('campaigns')->onDelete('cascade');
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
        Schema::dropIfExists('assignment_accounts');
    }
};
