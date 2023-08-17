<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateServicesTable extends Migration
{
    public function up()
    {
        Schema::create('services', function (Blueprint $table) {
            $table->increments('id');

            $table->string('name');

            $table->decimal('price', 15, 2)->nullable();

            $table->timestamps();

            $table->softDeletes();
        });
    }
}
