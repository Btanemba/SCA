<?php

namespace App\Mail;

use App\Models\Payroll;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class PayrollBankInstruction extends Mailable
{
    public function __construct(public Payroll $payroll, private string $pdf) {}

    public function envelope(): Envelope
    {
        $financeEmail = config('services.finance_mail.email');

        return new Envelope(
            from: new Address($financeEmail, config('app.name')),
            replyTo: [new Address($financeEmail, config('app.name'))],
            subject: 'Payroll payment instruction '.$this->payroll->reference,
        );
    }

    public function content(): Content
    {
        return new Content(view: 'payroll.email');
    }

    public function attachments(): array
    {
        return [Attachment::fromData(fn () => $this->pdf, 'payroll-'.$this->payroll->year.'-'.$this->payroll->month.'.pdf')
            ->withMime('application/pdf')];
    }
}
