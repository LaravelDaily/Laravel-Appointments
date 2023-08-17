<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRelationshipFieldsToAppointmentsTable extends Migration
{
    public function up()
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->unsignedInteger('client_id');

            $table->foreign('client_id', 'client_fk_360714')->references('id')->on('clients');

            $table->unsignedInteger('employee_id')->nullable();

            $table->foreign('employee_id', 'employee_fk_360715')->references('id')->on('employees');
        });
    }
}
