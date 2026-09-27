<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pickup_contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('person_id')->nullable()->constrained('persons')->nullOnDelete();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('image_path')->nullable();
            $table->string('address')->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 50)->nullable();
            $table->timestamps();

            $table->index('email');
            $table->index('phone');
        });

        Schema::table('child_pickup_contacts', function (Blueprint $table) {
            $table->foreignId('pickup_contact_id')->nullable()->after('child_id')->constrained('pickup_contacts')->nullOnDelete();
        });

        DB::table('child_pickup_contacts')->orderBy('id')->each(function (object $legacyContact): void {
            $existingContact = null;

            if (filled($legacyContact->email)) {
                $existingContact = DB::table('pickup_contacts')
                    ->where('email', $legacyContact->email)
                    ->first();
            }

            if (! $existingContact && filled($legacyContact->phone)) {
                $existingContact = DB::table('pickup_contacts')
                    ->where('phone', $legacyContact->phone)
                    ->first();
            }

            $pickupContactId = $existingContact?->id ?? DB::table('pickup_contacts')->insertGetId([
                'first_name' => $legacyContact->first_name,
                'last_name' => $legacyContact->last_name,
                'image_path' => $legacyContact->image_path,
                'address' => $legacyContact->address,
                'email' => $legacyContact->email,
                'phone' => $legacyContact->phone,
                'created_at' => $legacyContact->created_at,
                'updated_at' => $legacyContact->updated_at,
            ]);

            DB::table('child_pickup_contacts')
                ->where('id', $legacyContact->id)
                ->update(['pickup_contact_id' => $pickupContactId]);
        });
    }

    public function down(): void
    {
        Schema::table('child_pickup_contacts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('pickup_contact_id');
        });

        Schema::dropIfExists('pickup_contacts');
    }
};
