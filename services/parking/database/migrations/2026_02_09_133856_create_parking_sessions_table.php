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
        Schema::create('parking_sessions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('spot_id');
            $table->string('user_id');
            $table->timestamp('check_in_at');
            $table->timestamp('check_out_at')->nullable();
            $table->integer('total_minutes')->default(0);
            $table->decimal('parking_fee', 10, 2)->default(0);
            $table->timestamps();

            $table->index(['spot_id', 'check_in_at']);
            $table->index(['user_id', 'check_in_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('parking_sessions');
    }
};
