<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('persons', function (Blueprint $table) {
            $table->string('alt_email')->nullable()->after('phone');
            $table->string('alt_phone')->nullable()->after('alt_email');
            $table->string('Occupation')->nullable()->after('alt_phone');
        });
    }

    public function down(): void
    {
        Schema::table('persons', function (Blueprint $table) {
            $table->dropColumn(['alt_email', 'alt_phone', 'Occupation']);
        });
    }
};
