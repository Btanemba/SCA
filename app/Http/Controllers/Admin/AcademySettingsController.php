<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademySetting;
use App\Models\Person;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AcademySettingsController extends Controller
{
    private function authorizeSettings(): void
    {
        abort_unless(in_array(backpack_user()?->person?->sacRole?->code, [Person::ROLE_ADMIN, Person::ROLE_FOUNDER], true), 403);
    }

    public function edit()
    {
        $this->authorizeSettings();

        return view('payroll.settings', ['settings' => AcademySetting::find(1)]);
    }

    public function update(Request $request)
    {
        $this->authorizeSettings();
        $isCeo = backpack_user()->person->sacRole->code === Person::ROLE_FOUNDER;
        abort_if(! $isCeo && ($request->hasFile('signature') || $request->has('ceo_name')), 403);
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'required|string|max:2000',
            'phone' => 'required|string|max:100',
            'email' => 'required|email|max:255',
            'website' => 'nullable|url:http,https|max:255',
            'ceo_name' => ($isCeo ? 'required' : 'prohibited').'|string|max:255',
            'logo' => 'nullable|image|mimes:png,jpg,jpeg|max:2048|dimensions:max_width=3000,max_height=3000',
            'signature' => ($isCeo ? 'nullable' : 'prohibited').'|image|mimes:png,jpg,jpeg|max:1024|dimensions:max_width=2000,max_height=2000',
            'bank_name' => 'nullable|string|max:255',
            'bank_address' => 'nullable|string|max:2000',
            'bank_email' => 'nullable|email|max:255',
            'debit_account_name' => 'nullable|string|max:255',
            'debit_account_number' => 'nullable|regex:/^\d{10}$/',
        ]);
        unset($data['logo'], $data['signature']);
        $uploads = [];
        try {
            foreach (['logo', 'signature'] as $asset) {
                if ($request->hasFile($asset)) {
                    $path = $request->file($asset)->store('academy', 'local');
                    abort_unless($path, 500, 'Unable to store image.');
                    $uploads[] = $path;
                    $data[$asset.'_path'] = $path;
                }
            }
            DB::transaction(function () use ($data) {
                $settings = AcademySetting::query()->lockForUpdate()->find(1);
                if (! $settings) {
                    $settings = new AcademySetting;
                    $settings->id = 1;
                    $settings->ceo_name = '';
                }
                $settings->fill($data)->save();
            });
        } catch (\Throwable $exception) {
            Storage::disk('local')->delete($uploads);
            throw $exception;
        }

        return back()->with('success', 'Academy settings saved.');
    }

    public function asset(string $asset)
    {
        $this->authorizeSettings();
        abort_unless(in_array($asset, ['logo', 'signature'], true), 404);
        if ($asset === 'signature') {
            abort_unless(backpack_user()->person->sacRole->code === Person::ROLE_FOUNDER, 403);
        }
        $path = AcademySetting::findOrFail(1)->getAttribute($asset.'_path');
        abort_unless($path && Storage::disk('local')->exists($path), 404);

        $contents = Storage::disk('local')->get($path);

        return response($contents, 200, [
            'Content-Type' => (new \finfo(FILEINFO_MIME_TYPE))->buffer($contents),
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
