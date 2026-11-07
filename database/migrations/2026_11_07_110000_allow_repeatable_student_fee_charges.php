<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $indexes = collect(Schema::getIndexes('student_fee_charges'))->pluck('name');
        if (! $indexes->contains('student_fee_charges_student_index')) {
            Schema::table('student_fee_charges', function (Blueprint $table) {
                $table->index('student_id', 'student_fee_charges_student_index');
            });
        }
        if ($indexes->contains('student_fee_charges_student_fee_month_unique')) {
            Schema::table('student_fee_charges', function (Blueprint $table) {
                $table->dropUnique('student_fee_charges_student_fee_month_unique');
            });
        }
    }

    public function down(): void
    {
        $duplicates = DB::table('student_fee_charges')
            ->select('student_id', 'academy_fee_id', 'billing_month')
            ->groupBy('student_id', 'academy_fee_id', 'billing_month')
            ->havingRaw('COUNT(*) > 1')
            ->exists();
        if ($duplicates) {
            throw new RuntimeException('Cannot restore the monthly fee constraint while repeated charges exist.');
        }

        Schema::table('student_fee_charges', function (Blueprint $table) {
            $table->unique(['student_id', 'academy_fee_id', 'billing_month'], 'student_fee_charges_student_fee_month_unique');
            $table->dropIndex('student_fee_charges_student_index');
        });
    }
};
