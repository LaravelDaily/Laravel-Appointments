<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('appointment_service', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('appointment_id');
            $table->foreign('appointment_id', 'appointment_id_fk_360720')->references('id')->on('appointments')->onDelete('cascade');
            $table->unsignedInteger('service_id');
            $table->foreign('service_id', 'service_id_fk_360720')->references('id')->on('services')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appointment_service');
    }
};
