<?php

namespace App\Http\Controllers;

use App\Models\AcademyFee;
use App\Models\AcademySetting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class AdmissionFeesController extends Controller
{
    public function index(): View
    {
        return view('admissions.fees', [
            'fees' => AcademyFee::query()
                ->where('is_published', true)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get(),
        ]);
    }

    public function brochure()
    {
        $settings = AcademySetting::find(1);
        $academy = [
            'name' => $settings?->name ?? 'Springcare Academy',
            'address' => $settings?->address,
            'phone' => $settings?->phone,
            'email' => config('services.finance_mail.email'),
            'website' => $settings?->website,
            'logo' => null,
        ];

        if ($settings?->logo_path && Storage::disk('local')->exists($settings->logo_path)) {
            $contents = Storage::disk('local')->get($settings->logo_path);
            $mime = (new \finfo(FILEINFO_MIME_TYPE))->buffer($contents);
            $academy['logo'] = 'data:'.$mime.';base64,'.base64_encode($contents);
        }

        $fees = AcademyFee::query()
            ->where('is_published', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return Pdf::loadView('admissions.fees-brochure', [
            'academy' => $academy,
            'fees' => $fees,
            'preparedAt' => now()->format('F Y'),
        ])
            ->setPaper('a4')
            ->setOption('isRemoteEnabled', false)
            ->download('springcare-academy-fees-brochure.pdf');
    }
}
