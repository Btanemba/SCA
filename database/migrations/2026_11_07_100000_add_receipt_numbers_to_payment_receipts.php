<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('payment_receipts', 'receipt_number')) {
            Schema::table('payment_receipts', function (Blueprint $table) {
                $table->string('receipt_number', 28)->nullable();
            });
        }

        if (! collect(Schema::getIndexes('payment_receipts'))->pluck('name')->contains('payment_receipts_receipt_number_unique')) {
            Schema::table('payment_receipts', function (Blueprint $table) {
                $table->unique('receipt_number');
            });
        }

        DB::table('payment_receipts')->whereNull('receipt_number')->orderBy('id')
            ->chunkById(500, function ($receipts) {
                foreach ($receipts as $receipt) {
                    DB::table('payment_receipts')->where('id', $receipt->id)->whereNull('receipt_number')
                        ->update(['receipt_number' => 'SCA-'.Str::upper(Str::random(24))]);
                }
            });
    }

    public function down(): void
    {
        if (DB::table('payment_receipts')->exists()) {
            throw new RuntimeException('Cannot remove receipt numbers while payment receipts exist.');
        }

        Schema::table('payment_receipts', function (Blueprint $table) {
            $table->dropUnique(['receipt_number']);
            $table->dropColumn('receipt_number');
        });
    }
};
