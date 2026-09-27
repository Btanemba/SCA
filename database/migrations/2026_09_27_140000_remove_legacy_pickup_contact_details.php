<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('child_pickup_contacts', function (Blueprint $table) {
            $table->dropColumn([
                'first_name',
                'last_name',
                'image_path',
                'address',
                'email',
                'phone',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('child_pickup_contacts', function (Blueprint $table) {
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('image_path')->nullable();
            $table->string('address')->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 50)->nullable();
        });
    }
};
