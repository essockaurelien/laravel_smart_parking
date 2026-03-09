<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('charge_requests', function (Blueprint $table) {
            $table->integer('initial_percent')->default(0)->after('current_percent');
        });
    }

    public function down(): void
    {
        Schema::table('charge_requests', function (Blueprint $table) {
            $table->dropColumn('initial_percent');
        });
    }
};
