<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
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
        Schema::create('people', function (Blueprint $table) {
            $table->id()->comment('Identifier of the people');
            $table->string('peop_name')->comment('Name of the people');
            $table->string('peop_last_name')->comment('Last name of the people');
            $table->string('peop_dni')->comment('National Identity Document of the people');
            $table->boolean('peop_status')->comment('Status of the people');
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
        Schema::dropIfExists('people');
    }
};
