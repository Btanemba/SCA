<?php

namespace App\Http\Controllers;

use App\Models\AcademySetting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class RegistrationFormController extends Controller
{
    public function __invoke()
    {
        $settings = AcademySetting::find(1);
        $academy = [
            'name' => $settings?->name ?: 'Springcare Academy',
            'address' => $settings?->address,
            'phone' => $settings?->phone,
            'email' => $settings?->email,
            'website' => $settings?->website,
            'logo' => null,
        ];

        if ($settings?->logo_path && Storage::disk('local')->exists($settings->logo_path)) {
            $contents = Storage::disk('local')->get($settings->logo_path);
            $mime = (new \finfo(FILEINFO_MIME_TYPE))->buffer($contents);
            if (in_array($mime, ['image/png', 'image/jpeg'], true)) {
                $academy['logo'] = 'data:'.$mime.';base64,'.base64_encode($contents);
            }
        } elseif (is_file(public_path('images/logo.png'))) {
            $academy['logo'] = 'data:image/png;base64,'.base64_encode(file_get_contents(public_path('images/logo.png')));
        }

        return Pdf::loadView('admissions.registration-form', [
            'academy' => $academy,
            'sections' => config('registration'),
        ])
            ->setPaper('a4')
            ->setOption('isRemoteEnabled', false)
            ->stream('springcare-academy-registration-form.pdf');
    }
}
