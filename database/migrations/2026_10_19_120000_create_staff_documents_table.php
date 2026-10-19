<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('person_id')->constrained('persons')->restrictOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->string('title');
            $table->string('document_type', 32);
            $table->longText('body');
            $table->boolean('requires_staff_signature')->default(false);
            $table->json('academy_snapshot')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['person_id', 'published_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_documents');
    }
};
