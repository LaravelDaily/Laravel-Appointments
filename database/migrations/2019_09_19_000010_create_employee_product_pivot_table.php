<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEmployeeProductPivotTable extends Migration
{
    public function up()
    {
        Schema::create('employee_product', function (Blueprint $table) {
            $table->unsignedInteger('employee_id');

            $table->foreign('employee_id', 'employee_id_fk_360623')->references('id')->on('employees')->onDelete('cascade');

            $table->unsignedInteger('product_id');

            $table->foreign('product_id', 'product_id_fk_360623')->references('id')->on('products')->onDelete('cascade');
        });
    }
}
