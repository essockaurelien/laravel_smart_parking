<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('charge_requests', function (Blueprint $table) {
            $table->decimal('battery_kwh', 8, 2)->nullable()->after('current_percent');
        });
    }

    public function down(): void
    {
        Schema::table('charge_requests', function (Blueprint $table) {
            $table->dropColumn('battery_kwh');
        });
    }
};
