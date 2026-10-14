<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('academy_fees', function (Blueprint $table) {
            $table->id();
            $table->string('fee_name', 150);
            $table->string('age_or_level', 150);
            $table->unsignedBigInteger('amount')->nullable();
            $table->string('schedule', 180);
            $table->boolean('is_published')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('academy_fees');
    }
};