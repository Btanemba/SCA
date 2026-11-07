<?php

namespace App\Services;

use App\Models\AcademySetting;
use Illuminate\Support\Facades\Storage;

class AcademyLetterhead
{
    public function details(): array
    {
        $settings = AcademySetting::find(1);
        $academy = [
            'name' => $settings?->name ?: 'Springcare Academy',
            'address' => $settings?->address,
            'phone' => $settings?->phone,
            'email' => $settings?->email ?: config('services.finance_mail.email'),
            'website' => $settings?->website,
            'logo' => null,
        ];
        if ($settings?->logo_path && Storage::disk('local')->exists($settings->logo_path)) {
            $contents = Storage::disk('local')->get($settings->logo_path);
            $mime = (new \finfo(FILEINFO_MIME_TYPE))->buffer($contents);
            $academy['logo'] = 'data:'.$mime.';base64,'.base64_encode($contents);
        } elseif (is_file(public_path('images/logo.png'))) {
            $academy['logo'] = 'data:image/png;base64,'.base64_encode(file_get_contents(public_path('images/logo.png')));
        }

        return $academy;
    }

    public function feePaymentDetails(): ?array
    {
        $settings = AcademySetting::find(1);
        if (! filled($settings?->fee_bank_name) || ! filled($settings?->fee_account_name)
            || ! preg_match('/^\d{10}$/D', (string) $settings?->fee_account_number)) {
            return null;
        }

        return [
            'bank_name' => $settings->fee_bank_name,
            'account_name' => $settings->fee_account_name,
            'account_number' => $settings->fee_account_number,
        ];
    }
}
