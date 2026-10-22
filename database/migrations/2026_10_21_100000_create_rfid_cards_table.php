<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rfid_cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pickup_contact_id')->constrained('pickup_contacts')->cascadeOnDelete();
            $table->string('uid', 64)->unique();
            $table->timestamp('revoked_at')->nullable();
            $table->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['pickup_contact_id', 'revoked_at']);
        });

        Schema::table('attendances', function (Blueprint $table) {
            $table->string('dropped_off_method', 10)->default('manual')->after('dropped_off_recorded_by');
            $table->string('picked_up_method', 10)->nullable()->after('picked_up_recorded_by');
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropColumn(['dropped_off_method', 'picked_up_method']);
        });

        Schema::dropIfExists('rfid_cards');
    }
};
