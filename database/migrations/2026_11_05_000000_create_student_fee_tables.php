<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('student_fee_charges')) {
            Schema::create('student_fee_charges', function (Blueprint $table) {
                $table->id();
                $table->foreignId('student_id')->constrained('persons')->restrictOnDelete();
                $table->foreignId('academy_fee_id')->constrained('academy_fees')->restrictOnDelete();
                $table->date('billing_month');
                $table->string('fee_name');
                $table->unsignedBigInteger('amount_due_kobo');
                $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
                $table->timestamps();
                $table->unique(['student_id', 'academy_fee_id', 'billing_month'], 'student_fee_charges_student_fee_month_unique');
                $table->index(['billing_month', 'student_id'], 'student_fee_charges_month_student_index');
            });
        } else {
            $indexes = collect(Schema::getIndexes('student_fee_charges'))->pluck('name');
            Schema::table('student_fee_charges', function (Blueprint $table) use ($indexes) {
                if (! $indexes->contains('student_fee_charges_student_fee_month_unique')) {
                    $table->unique(['student_id', 'academy_fee_id', 'billing_month'], 'student_fee_charges_student_fee_month_unique');
                }
                if (! $indexes->contains('student_fee_charges_month_student_index')) {
                    $table->index(['billing_month', 'student_id'], 'student_fee_charges_month_student_index');
                }
            });
        }

        if (! Schema::hasTable('fee_payments')) {
            Schema::create('fee_payments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('student_fee_charge_id')->constrained('student_fee_charges')->cascadeOnDelete();
                $table->unsignedBigInteger('amount_kobo');
                $table->date('paid_at');
                $table->string('payment_method', 30);
                $table->string('reference')->nullable();
                $table->foreignId('received_by')->constrained('users')->restrictOnDelete();
                $table->timestamps();
                $table->index(['student_fee_charge_id', 'paid_at'], 'fee_payments_charge_paid_at_index');
            });
        } else {
            $indexes = collect(Schema::getIndexes('fee_payments'))->pluck('name');
            if (! $indexes->contains('fee_payments_charge_paid_at_index')) {
                Schema::table('fee_payments', function (Blueprint $table) {
                    $table->index(['student_fee_charge_id', 'paid_at'], 'fee_payments_charge_paid_at_index');
                });
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('fee_payments');
        Schema::dropIfExists('student_fee_charges');
    }
};
