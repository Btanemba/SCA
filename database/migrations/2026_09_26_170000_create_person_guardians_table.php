<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('person_guardians', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->constrained('persons')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('persons')->cascadeOnDelete();
            $table->string('relationship')->nullable();
            $table->boolean('is_primary_contact')->default(false);
            $table->timestamps();

            $table->unique(['parent_id', 'student_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('person_guardians');
    }
};
