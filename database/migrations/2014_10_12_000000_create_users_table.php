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
        Schema::create('users', function (Blueprint $table) {
            $table->id()->comment('Identifier of the user');
            $table->string('name')->comment('Name of the user');
            $table->string('last_name')->comment('Last Name of the user');
            $table->string('document_number')->unique()->comment('Number document of the user');
            $table->string('phone_number')->comment('Phone number of the user');
            $table->string('address')->comment('Address of the user');
            $table->string('email')->unique()->comment('Email of the user');
            $table->timestamp('email_verified_at')->nullable()->comment('Verification email of the user');
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
            $table->timestamp('deleted_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users');
    }
};
