<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('academy_settings', function (Blueprint $table) {
            $table->string('fee_bank_name')->nullable();
            $table->string('fee_account_name')->nullable();
            $table->string('fee_account_number', 10)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('academy_settings', function (Blueprint $table) {
            $table->dropColumn(['fee_bank_name', 'fee_account_name', 'fee_account_number']);
        });
    }
};
