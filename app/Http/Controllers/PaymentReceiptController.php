<?php

namespace App\Http\Controllers;

use App\Models\AcademySetting;
use App\Models\PaymentReceipt;
use App\Models\Person;
use App\Services\AcademyLetterhead;
use App\Services\ParentChildAccess;
use Barryvdh\DomPDF\Facade\Pdf;

class PaymentReceiptController extends Controller
{
    public function download(PaymentReceipt $receipt, AcademyLetterhead $letterhead, ParentChildAccess $access)
    {
        $role = backpack_user()?->person?->sacRole?->code;
        if ($role === Person::ROLE_PARENT) {
            $access->child($receipt->student_id);
        } else {
            abort_unless(in_array($role, [Person::ROLE_ACCOUNTANT, Person::ROLE_ADMIN, Person::ROLE_FOUNDER], true), 403);
        }

        $receipt->load(['student', 'receiver', 'payments.charge', 'walletCredit']);
        return Pdf::loadView('student-fees.receipt', [
            'receipt' => $receipt,
            'academy' => $letterhead->details(),
            'verificationUrl' => route('payment-receipts.verify', $receipt->receipt_number),
        ])->setPaper('a4')->setOption('isRemoteEnabled', false)
            ->download('receipt-'.$receipt->receipt_number.'.pdf')
            ->header('Cache-Control', 'no-store, private');
    }

    public function verify(string $number)
    {
        $receipt = PaymentReceipt::query()
            ->with(['student', 'payments.charge', 'walletCredit'])
            ->where('receipt_number', $number)
            ->firstOrFail();

        return response()->view('student-fees.verify-receipt', [
            'receipt' => $receipt,
            'academyName' => AcademySetting::find(1)?->name ?: 'Springcare Academy',
        ])->header('Cache-Control', 'no-store, private')
            ->header('X-Robots-Tag', 'noindex, nofollow')
            ->header('Referrer-Policy', 'no-referrer');
    }
}
