<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\Person;
use App\Services\ExpenseDocuments;
use App\Services\PayrollAmounts;
use App\Services\PayrollDocuments;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ExpenseController extends Controller
{
    private function authorizeRole(?string $role = null): void
    {
        $current = backpack_user()?->person?->sacRole?->code;
        abort_unless($role ? $current === $role : in_array($current, [Person::ROLE_ACCOUNTANT, Person::ROLE_FOUNDER], true), 403);
    }

    private function event(Expense $expense, string $action, array $details = []): void
    {
        $expense->events()->create(['user_id' => backpack_user()->id, 'action' => $action, 'details' => $details]);
    }

    public function index(Request $request)
    {
        $this->authorizeRole();
        $filters = $request->validate([
            'status' => ['nullable', Rule::in(['draft', 'returned', 'submitted', 'approved', 'paid'])],
            'search' => 'nullable|string|max:255',
            'paid_month' => 'nullable|date_format:Y-m',
        ]);
        $expenses = Expense::query();
        if (! empty($filters['status'])) {
            $expenses->where('status', $filters['status']);
        }
        if (! empty($filters['paid_month'])) {
            [$year, $month] = explode('-', $filters['paid_month']);
            $expenses->where('status', 'paid')
                ->whereYear('paid_at', (int) $year)
                ->whereMonth('paid_at', (int) $month);
        }
        $search = trim($filters['search'] ?? '');
        if ($search !== '') {
            if (preg_match('/^SCA\/EXP\/(\d{4})\/(\d+)$/i', $search, $parts)) {
                $expenses->whereYear('created_at', (int) $parts[1])->whereKey((int) $parts[2]);
            } else {
                $expenses->where(function ($query) use ($search) {
                    $query->where('title', 'like', "%{$search}%")
                        ->orWhere('payee', 'like', "%{$search}%")
                        ->orWhere('category', 'like', "%{$search}%");
                    if (ctype_digit($search)) {
                        $query->orWhereKey((int) $search);
                    }
                });
            }
        }

        return view('expenses.index', ['expenses' => $expenses->latest('id')->paginate(20)->withQueryString()]);
    }

    public function form(?Expense $expense = null)
    {
        $this->authorizeRole(Person::ROLE_ACCOUNTANT);
        abort_if($expense && ! $expense->editable(), 409, 'This expense is locked.');

        return view('expenses.form', compact('expense'));
    }

    public function save(Request $request, ?Expense $expense = null)
    {
        $this->authorizeRole(Person::ROLE_ACCOUNTANT);
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'category' => 'required|string|max:100',
            'description' => 'required|string|max:5000',
            'expense_date' => 'required|date_format:Y-m-d',
            'payee' => 'required|string|max:255',
            'amount' => ['required', 'regex:/^\d{1,9}(?:\.\d{1,2})?$/D'],
            'bank_name' => 'nullable|string|max:255',
            'account_name' => 'nullable|string|max:255',
            'account_number' => ['nullable', 'regex:/^\d{10}$/D'],
            'invoice' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'receipt' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'remove_invoice' => 'nullable|boolean',
            'remove_receipt' => 'nullable|boolean',
        ]);
        $amount = PayrollAmounts::kobo((string) $data['amount']);
        if ($amount <= 0) {
            throw ValidationException::withMessages(['amount' => 'Enter an amount greater than zero.']);
        }
        $values = collect($data)->only(['title', 'category', 'description', 'expense_date', 'payee', 'bank_name', 'account_name', 'account_number'])->all();
        $values['amount_kobo'] = $amount;
        $stored = [];
        $obsolete = [];
        try {
            $saved = DB::transaction(function () use ($expense, $request, $values, &$stored, &$obsolete) {
                $locked = $expense ? Expense::query()->lockForUpdate()->findOrFail($expense->id) : new Expense;
                abort_unless(! $expense || $locked->editable(), 409, 'This expense is locked.');
                $locked->fill($values);
                if (! $expense) {
                    $locked->created_by = backpack_user()->id;
                    $locked->status = 'draft';
                }
                foreach (['invoice', 'receipt'] as $asset) {
                    if (! $request->hasFile($asset) && ! $request->boolean('remove_'.$asset)) {
                        continue;
                    }
                    if ($locked->getAttribute($asset.'_path')) {
                        $obsolete[] = $locked->getAttribute($asset.'_path');
                    }
                    $path = null;
                    if ($request->hasFile($asset)) {
                        $path = $request->file($asset)->store('expenses/'.$asset, 'local');
                        if (! $path) {
                            throw ValidationException::withMessages([$asset => 'Unable to store the document. Please try again.']);
                        }
                        $stored[] = $path;
                    }
                    $locked->setAttribute($asset.'_path', $path);
                }
                $locked->save();
                $this->event($locked, $expense ? 'updated' : 'created');

                return $locked;
            });
        } catch (\Throwable $exception) {
            Storage::disk('local')->delete($stored);
            throw $exception;
        }
        Storage::disk('local')->delete($obsolete);

        return redirect()->route('expenses.show', $saved)->with('success', 'Expense saved.');
    }

    public function show(Expense $expense)
    {
        $this->authorizeRole();
        $expense->load('events.user');

        return view('expenses.show', compact('expense'));
    }

    public function destroy(Expense $expense)
    {
        $this->authorizeRole(Person::ROLE_ACCOUNTANT);
        $paths = DB::transaction(function () use ($expense) {
            $locked = Expense::query()->lockForUpdate()->findOrFail($expense->id);
            abort_unless($locked->status === 'draft' && ! $locked->submitted_at, 409, 'Only an unsubmitted draft can be deleted.');
            $paths = array_filter([$locked->invoice_path, $locked->receipt_path]);
            $locked->delete();

            return $paths;
        });
        Storage::disk('local')->delete($paths);

        return redirect()->route('expenses.index')->with('success', 'Expense draft deleted.');
    }

    public function transition(Request $request, Expense $expense, string $action, PayrollDocuments $documents)
    {
        abort_unless(in_array($action, ['submit', 'approve', 'return', 'paid'], true), 404);
        $this->authorizeRole(in_array($action, ['approve', 'return'], true) ? Person::ROLE_FOUNDER : Person::ROLE_ACCOUNTANT);
        $data = match ($action) {
            'return' => $request->validate(['reason' => 'required|string|max:2000']),
            'paid' => $request->validate(['payment_reference' => 'required|string|max:255', 'payment_date' => 'required|date_format:Y-m-d|before_or_equal:today']),
            default => [],
        };
        DB::transaction(function () use ($expense, $action, $data, $documents, $request) {
            $locked = Expense::query()->lockForUpdate()->findOrFail($expense->id);
            if ($action === 'submit') {
                abort_unless($locked->editable(), 409, 'Only a draft or returned expense can be submitted.');
                if ($locked->status === 'returned') {
                    $data = $request->validate(['response' => 'required|string|max:2000']);
                    $data['return_reason'] = $locked->return_reason;
                }
                if (! filled($locked->bank_name) || ! filled($locked->account_name) || ! preg_match('/^\d{10}$/D', (string) $locked->account_number)) {
                    throw ValidationException::withMessages(['bank_name' => 'Complete the payee bank details before submitting for approval.']);
                }
                abort_unless($locked->amount_kobo > 0, 422);
                $locked->fill(['status' => 'submitted', 'submitted_at' => now(), 'return_reason' => null]);
            } elseif ($action === 'return') {
                abort_unless($locked->status === 'submitted', 409);
                $locked->fill(['status' => 'returned', 'return_reason' => $data['reason']]);
            } elseif ($action === 'approve') {
                abort_unless($locked->status === 'submitted', 409);
                abort_if($locked->created_by === backpack_user()->id, 403, 'The preparer cannot approve their own expense.');
                $locked->fill(['status' => 'approved', 'approved_by' => backpack_user()->id, 'approved_at' => now(), 'approval_snapshot' => $documents->snapshot()]);
            } else {
                abort_unless($locked->status === 'approved', 409);
                abort_if($data['payment_date'] < $locked->approved_at->format('Y-m-d'), 422, 'Payment date cannot precede approval.');
                $locked->fill(['status' => 'paid', 'paid_at' => $data['payment_date'], 'payment_reference' => $data['payment_reference']]);
            }
            $locked->save();
            $this->event($locked, $action, $data);
        });

        return back()->with('success', 'Expense action completed.');
    }

    public function uploadReceipt(Request $request, Expense $expense)
    {
        $this->authorizeRole(Person::ROLE_ACCOUNTANT);
        $request->validate(['receipt' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240']);
        $path = null;
        $oldPath = null;
        try {
            DB::transaction(function () use ($request, $expense, &$path, &$oldPath) {
                $locked = Expense::query()->lockForUpdate()->findOrFail($expense->id);
                abort_unless($locked->approved(), 409, 'Receipts can be uploaded here only after approval.');
                $path = $request->file('receipt')->store('expenses/receipt', 'local');
                if (! $path) {
                    throw ValidationException::withMessages(['receipt' => 'Unable to store the receipt. Please try again.']);
                }
                $oldPath = $locked->receipt_path;
                $locked->update(['receipt_path' => $path]);
                $this->event($locked, $oldPath ? 'receipt_replaced' : 'receipt_uploaded');
            });
        } catch (\Throwable $exception) {
            if ($path) {
                Storage::disk('local')->delete($path);
            }
            throw $exception;
        }
        if ($oldPath) {
            Storage::disk('local')->delete($oldPath);
        }

        return back()->with('success', 'Purchase receipt saved.');
    }

    public function attachment(Expense $expense, string $asset)
    {
        $this->authorizeRole();
        abort_unless(in_array($asset, ['invoice', 'receipt'], true), 404);
        $path = $expense->getAttribute($asset.'_path');
        abort_unless($path && Storage::disk('local')->exists($path), 404);
        $this->event($expense, $asset.'_downloaded');

        return response()->download(Storage::disk('local')->path($path), 'expense-'.$expense->id.'-'.$asset.'.'.pathinfo($path, PATHINFO_EXTENSION), ['Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff']);
    }

    public function bankDocument(Request $request, Expense $expense, ExpenseDocuments $documents)
    {
        $this->authorizeRole();
        $pdf = $documents->bankLetter($expense);
        $inline = $request->boolean('inline');
        $this->event($expense, $inline ? 'bank_letter_viewed' : 'bank_letter_downloaded');

        return response($pdf, 200, ['Content-Type' => 'application/pdf', 'Content-Disposition' => ($inline ? 'inline' : 'attachment').'; filename="expense-'.$expense->id.'-bank-instruction.pdf"', 'Cache-Control' => 'private, no-store']);
    }
}
