<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('academy_settings', function (Blueprint $table) {
            $table->unsignedTinyInteger('id')->primary();
            $table->string('name');
            $table->text('address');
            $table->string('phone');
            $table->string('email');
            $table->string('website')->nullable();
            $table->string('logo_path')->nullable();
            $table->string('ceo_name');
            $table->string('signature_path')->nullable();
            $table->string('bank_name')->nullable();
            $table->text('bank_address')->nullable();
            $table->string('bank_email')->nullable();
            $table->string('debit_account_name')->nullable();
            $table->string('debit_account_number', 10)->nullable();
            $table->timestamps();
        });

        Schema::create('payrolls', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month');
            $table->unique(['year', 'month']);
            $table->string('status')->default('draft');
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->json('approval_snapshot')->nullable();
            $table->text('return_reason')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->string('payment_reference')->nullable();
            $table->timestamps();
        });

        Schema::create('payroll_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_id')->constrained()->cascadeOnDelete();
            $table->foreignId('person_id')->constrained('persons')->restrictOnDelete();
            $table->unique(['payroll_id', 'person_id']);
            $table->string('employee_name');
            $table->string('bank_name');
            $table->string('account_name');
            $table->string('account_number', 10);
            $table->unsignedBigInteger('basic_kobo');
            $table->json('allowances');
            $table->json('deductions');
            $table->unsignedBigInteger('gross_kobo');
            $table->unsignedBigInteger('deductions_kobo');
            $table->unsignedBigInteger('net_kobo');
            $table->timestamps();
        });

        Schema::create('payroll_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('action');
            $table->json('details')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_events');
        Schema::dropIfExists('payroll_entries');
        Schema::dropIfExists('payrolls');
        Schema::dropIfExists('academy_settings');
    }
};
