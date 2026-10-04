<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->string('plate_number', 20);
            $table->string('customer_name', 100);
            $table->string('motorcycle_type', 50);
            $table->string('service_time', 20);
            $table->foreignId('package_id')->constrained('packages')->onDelete('cascade');
            $table->date('booking_date');
            $table->string('status', 20)->default('terdaftar');
            $table->timestamps();

            $table->index(['service_time', 'booking_date']);
            $table->index('plate_number');
            $table->index('customer_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
