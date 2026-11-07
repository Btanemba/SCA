<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('payment_receipts')) {
            Schema::create('payment_receipts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('student_id')->constrained('persons')->restrictOnDelete();
                $table->unsignedBigInteger('amount_kobo');
                $table->date('paid_at');
                $table->string('payment_method', 30);
                $table->string('reference')->nullable();
                $table->foreignId('received_by')->constrained('users')->restrictOnDelete();
                $table->timestamps();
                $table->index('paid_at');
            });
        }

        if (! Schema::hasColumn('fee_payments', 'payment_receipt_id')) {
            Schema::table('fee_payments', function (Blueprint $table) {
                $table->foreignId('payment_receipt_id')->nullable()->constrained('payment_receipts')->restrictOnDelete();
            });
        }

        if (! Schema::hasTable('student_wallet_transactions')) {
            Schema::create('student_wallet_transactions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('student_id')->constrained('persons')->restrictOnDelete();
                $table->string('type', 10);
                $table->unsignedBigInteger('amount_kobo');
                $table->foreignId('payment_receipt_id')->nullable()->unique()->constrained('payment_receipts')->restrictOnDelete();
                $table->foreignId('fee_payment_id')->nullable()->unique()->constrained('fee_payments')->restrictOnDelete();
                $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
                $table->timestamps();
                $table->index(['student_id', 'id']);
            });
        }

        DB::table('fee_payments')
            ->join('student_fee_charges', 'student_fee_charges.id', '=', 'fee_payments.student_fee_charge_id')
            ->select('fee_payments.*', 'student_fee_charges.student_id')
            ->whereNull('fee_payments.payment_receipt_id')
            ->where('fee_payments.payment_method', '!=', 'wallet')
            ->orderBy('fee_payments.id')
            ->chunkById(500, function ($payments) {
                foreach ($payments as $payment) {
                    DB::transaction(function () use ($payment) {
                        $receiptId = DB::table('payment_receipts')->insertGetId([
                            'student_id' => $payment->student_id,
                            'amount_kobo' => $payment->amount_kobo,
                            'paid_at' => $payment->paid_at,
                            'payment_method' => $payment->payment_method,
                            'reference' => $payment->reference,
                            'received_by' => $payment->received_by,
                            'created_at' => $payment->created_at,
                            'updated_at' => $payment->updated_at,
                        ]);
                        DB::table('fee_payments')->where('id', $payment->id)->update(['payment_receipt_id' => $receiptId]);
                    });
                }
            }, 'fee_payments.id', 'id');
    }

    public function down(): void
    {
        if (DB::table('student_wallet_transactions')->exists()) {
            throw new RuntimeException('Cannot roll back student wallets while wallet transactions exist.');
        }

        Schema::dropIfExists('student_wallet_transactions');
        Schema::table('fee_payments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('payment_receipt_id');
        });
        Schema::dropIfExists('payment_receipts');
    }
};
