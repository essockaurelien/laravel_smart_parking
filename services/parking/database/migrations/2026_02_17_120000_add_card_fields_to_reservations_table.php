<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->string('card_last4', 4)->nullable()->after('penalty_amount');
            $table->string('card_holder')->nullable()->after('card_last4');
            $table->unsignedTinyInteger('card_exp_month')->nullable()->after('card_holder');
            $table->unsignedSmallInteger('card_exp_year')->nullable()->after('card_exp_month');
        });
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropColumn([
                'card_last4',
                'card_holder',
                'card_exp_month',
                'card_exp_year',
            ]);
        });
    }
};
