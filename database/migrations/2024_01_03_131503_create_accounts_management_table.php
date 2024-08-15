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
        Schema::create('accounts_management', function (Blueprint $table) {
            $table->id();
            $table->date('acma_start_date');
            $table->date('acma_end_date');
            $table->time('acma_start_time');
            $table->time('acma_end_time');
            $table->string('acco_code_account');
            $table->string('data_value');
            $table->string('acco_contact_name');
            $table->boolean('acma_iseffective');
            $table->longText('acma_observation');
            $table->string('assi_name');
            $table->string('camp_name');
            $table->string('alli_name');
            $table->string('typi_name');
            $table->boolean('typi_effective');
            $table->string('peop_name');
            $table->string('peop_dni');
            $table->string('clie_name');
            $table->string('clie_dni');
            $table->string('foal_name');
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
        Schema::dropIfExists('accounts_management');
    }
};
