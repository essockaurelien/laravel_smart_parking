<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('charge_requests', function (Blueprint $table) {
            $table->string('user_role')->nullable()->after('user_id');
            $table->boolean('notify_on_complete')->default(false)->after('estimated_minutes');
        });
    }

    public function down(): void
    {
        Schema::table('charge_requests', function (Blueprint $table) {
            $table->dropColumn(['user_role', 'notify_on_complete']);
        });
    }
};
