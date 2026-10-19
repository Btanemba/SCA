<?php

namespace App\Services;

use App\Models\AcademySetting;
use App\Models\StaffDocument;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class StaffDocumentPdf
{
    public function render(StaffDocument $document): string
    {
        $branding = $document->academy_snapshot ?? $this->currentBranding();
        foreach (['logo', 'signature'] as $asset) {
            $branding[$asset] = $this->imageData($branding[$asset.'_path'] ?? null);
        }

        return Pdf::loadView('staff-documents.pdf', [
            'document' => $document->loadMissing('person'),
            'branding' => $branding,
            'documentDate' => $document->published_at ?? $document->created_at ?? now(),
        ])->setPaper('a4')->setOption('isRemoteEnabled', false)->output();
    }

    public function snapshot(): array
    {
        $academy = AcademySetting::find(1);
        $snapshot = [
            'name' => $academy?->name ?: 'Springcare Academy',
            'address' => $academy?->address,
            'phone' => $academy?->phone,
            'email' => $academy?->email,
            'website' => $academy?->website,
            'ceo_name' => $academy?->ceo_name,
        ];

        foreach (['logo', 'signature'] as $asset) {
            $snapshot[$asset.'_path'] = $this->snapshotAsset($academy?->getAttribute($asset.'_path'));
        }

        return $snapshot;
    }

    private function currentBranding(): array
    {
        $academy = AcademySetting::find(1);

        return [
            'name' => $academy?->name ?: 'Springcare Academy',
            'address' => $academy?->address,
            'phone' => $academy?->phone,
            'email' => $academy?->email,
            'website' => $academy?->website,
            'ceo_name' => $academy?->ceo_name,
            'logo_path' => $academy?->logo_path,
            'signature_path' => $academy?->signature_path,
        ];
    }

    private function snapshotAsset(?string $path): ?string
    {
        if (! $path || ! Storage::disk('local')->exists($path)) {
            return null;
        }

        $contents = Storage::disk('local')->get($path);
        $extension = match ((new \finfo(FILEINFO_MIME_TYPE))->buffer($contents)) {
            'image/png' => 'png',
            'image/jpeg' => 'jpg',
            default => throw ValidationException::withMessages(['settings' => 'The academy '.$path.' image is not a supported PNG or JPEG.']),
        };
        $snapshotPath = 'staff-documents/branding/'.hash('sha256', $contents).'.'.$extension;
        if (! Storage::disk('local')->exists($snapshotPath)
            && ! Storage::disk('local')->put($snapshotPath, $contents)) {
            throw ValidationException::withMessages(['settings' => 'Unable to preserve the academy letterhead image.']);
        }

        return $snapshotPath;
    }

    private function imageData(?string $path): ?string
    {
        if (! $path || ! Storage::disk('local')->exists($path)) {
            return null;
        }

        $contents = Storage::disk('local')->get($path);
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->buffer($contents);

        return 'data:'.$mime.';base64,'.base64_encode($contents);
    }
}
