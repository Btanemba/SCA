<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Person;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class InfoMailController extends Controller
{
    private function accountSettings(string $mailAccount): array
    {
        $accounts = [
            'info' => [
                'title' => 'Info Mail',
                'config' => 'services.info_mail',
                'roles' => [Person::ROLE_ADMIN, Person::ROLE_FOUNDER],
            ],
            'finance' => [
                'title' => 'Finance Mail',
                'config' => 'services.finance_mail',
                'roles' => [Person::ROLE_ADMIN, Person::ROLE_FOUNDER, Person::ROLE_ACCOUNTANT],
            ],
        ];
        abort_unless(isset($accounts[$mailAccount]), 404);

        $account = $accounts[$mailAccount];
        $roleCode = backpack_user()?->person?->sacRole?->code;
        abort_unless(in_array($roleCode, $account['roles'], true), 403);

        return [
            'key' => $mailAccount,
            'title' => $account['title'],
            'url' => config($account['config'] . '.url'),
            'email' => config($account['config'] . '.email'),
            'password' => config($account['config'] . '.password'),
        ];
    }

    public function show(string $mailAccount)
    {
        $account = $this->accountSettings($mailAccount);

        return response()
            ->view('mail-accounts.info', [
                'account' => $account,
                'passwordConfigured' => filled($account['password']),
            ])
            ->header('Cache-Control', 'no-store, private');
    }

    public function revealPassword(string $mailAccount): JsonResponse
    {
        $account = $this->accountSettings($mailAccount);
        $password = $account['password'];
        abort_unless(filled($password), 503, 'The mailbox password is not configured on the server.');

        Log::notice('Mailbox password revealed', [
            'user_id' => backpack_user()->getAuthIdentifier(),
            'mail_account' => $account['key'],
        ]);

        return response()->json(['password' => $password])
            ->header('Cache-Control', 'no-store, private')
            ->header('Pragma', 'no-cache');
    }
}
