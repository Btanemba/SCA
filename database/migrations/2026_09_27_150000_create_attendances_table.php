<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('child_id')->constrained('persons')->cascadeOnDelete();
            $table->date('attendance_date');
            $table->dateTime('dropped_off_at');
            $table->foreignId('dropped_off_by_contact_id')->nullable()->constrained('pickup_contacts')->nullOnDelete();
            $table->string('dropped_off_by_name');
            $table->foreignId('dropped_off_recorded_by')->constrained('users')->restrictOnDelete();
            $table->dateTime('picked_up_at')->nullable();
            $table->foreignId('picked_up_by_contact_id')->nullable()->constrained('pickup_contacts')->nullOnDelete();
            $table->string('picked_up_by_name')->nullable();
            $table->foreignId('picked_up_recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['child_id', 'attendance_date']);
            $table->index(['attendance_date', 'picked_up_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
