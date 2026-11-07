<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('absence_notices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('child_id')->constrained('persons')->restrictOnDelete();
            $table->date('absence_date');
            $table->foreignId('reported_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('cancelled_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->restrictOnDelete();

            $table->timestamps();
            $table->index(['child_id', 'absence_date', 'cancelled_at']);
            $table->index(['absence_date', 'cancelled_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('absence_notices');
    }
};
