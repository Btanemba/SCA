<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // Accounts not linked to a person are pre-existing admins; don't lock them out.
    public function up(): void
    {
        DB::table('users')
            ->whereNull('email_verified_at')
            ->whereNotIn('id', DB::table('persons')->whereNotNull('user_id')->pluck('user_id'))
            ->update(['email_verified_at' => now()]);
    }

    public function down(): void
    {
        //
    }
};
