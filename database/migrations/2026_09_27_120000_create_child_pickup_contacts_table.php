<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('child_pickup_contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('child_id')->constrained('persons')->cascadeOnDelete();
            $table->unsignedTinyInteger('slot');
            $table->string('first_name');
            $table->string('last_name');
            $table->string('relationship')->nullable();
            $table->string('image_path')->nullable();
            $table->string('address')->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 50)->nullable();
            $table->boolean('can_pick_up')->default(true);
            $table->boolean('can_drop_off')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['child_id', 'slot']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('child_pickup_contacts');
    }
};
