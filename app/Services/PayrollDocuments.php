<?php

namespace App\Services;

use App\Models\AcademySetting;
use App\Models\Payroll;
use App\Models\PayrollEntry;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class PayrollDocuments
{
    public function snapshot(): array
    {
        $settings = AcademySetting::find(1);
        if (! $settings || ! $settings->logo_path || ! $settings->signature_path || ! $settings->ceo_name
            || ! $settings->bank_name || ! $settings->debit_account_name || ! $settings->debit_account_number) {
            throw ValidationException::withMessages(['settings' => 'Complete academy details, logo, CEO name/signature and payment bank details before approval.']);
        }
        $snapshot = $settings->only(['name', 'address', 'phone', 'email', 'website', 'ceo_name', 'bank_name', 'bank_address', 'bank_email', 'debit_account_name', 'debit_account_number']);
        foreach (['logo', 'signature'] as $asset) {
            $path = $settings->getAttribute($asset.'_path');
            if (! Storage::disk('local')->exists($path)) {
                throw ValidationException::withMessages(['settings' => 'Upload the missing '.$asset.' image before approval.']);
            }
            $contents = Storage::disk('local')->get($path);
            $snapshotPath = 'payroll/approval-assets/'.hash('sha256', $contents);
            if (! Storage::disk('local')->exists($snapshotPath)
                && ! Storage::disk('local')->put($snapshotPath, $contents)) {
                throw ValidationException::withMessages(['settings' => 'Unable to preserve the '.$asset.' image for approval.']);
            }
            $snapshot[$asset.'_path'] = $snapshotPath;
        }

        return $snapshot;
    }

    private function documentAcademy(Payroll $payroll): array
    {
        $academy = $payroll->approval_snapshot;
        foreach (['logo', 'signature'] as $asset) {
            $path = $academy[$asset.'_path'] ?? null;
            if (! $path) {
                continue;
            }
            abort_unless(Storage::disk('local')->exists($path), 422, 'The approved '.$asset.' image is missing.');
            $contents = Storage::disk('local')->get($path);
            $mime = (new \finfo(FILEINFO_MIME_TYPE))->buffer($contents);
            $academy[$asset] = 'data:'.$mime.';base64,'.base64_encode($contents);
        }

        return $academy;
    }

    public function bankLetter(Payroll $payroll): string
    {
        abort_unless($payroll->approved(), 403);
        $payroll->load('entries');

        $academy = $this->documentAcademy($payroll);
        $period = \Carbon\Carbon::create($payroll->year, $payroll->month, 1)->format('F Y');

        return Pdf::loadView('payroll.documents.bank', compact('payroll', 'academy', 'period'))
            ->setPaper('a4')->setOption('isRemoteEnabled', false)->output();
    }

    public function payslip(PayrollEntry $entry): string
    {
        $payroll = $entry->payroll;
        abort_unless($payroll->approved(), 403);

        $academy = $this->documentAcademy($payroll);
        $period = \Carbon\Carbon::create($payroll->year, $payroll->month, 1)->format('F Y');

        return Pdf::loadView('payroll.documents.payslip', compact('entry', 'payroll', 'academy', 'period'))
            ->setPaper('a4')->setOption('isRemoteEnabled', false)->output();
    }
}
