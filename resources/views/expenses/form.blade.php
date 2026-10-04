@extends('payroll.layout')

@section('payroll_content')
    <a href="{{ $expense ? route('expenses.show', $expense) : route('expenses.index') }}" class="d-inline-block mb-3"><i class="la la-arrow-left" aria-hidden="true"></i> {{ $expense ? 'Expense' : 'Expenses' }}</a>
    <h2 class="mb-4">{{ $expense ? 'Edit Expense' : 'New Expense' }}</h2>
    <form action="{{ $expense ? route('expenses.update', $expense) : route('expenses.store') }}" method="post" enctype="multipart/form-data" class="row g-3">
        @csrf
        @if ($expense) @method('PUT') @endif
        <div class="col-md-8"><label for="title" class="form-label">Title</label><input class="form-control" id="title" name="title" maxlength="255" value="{{ old('title', $expense?->title) }}" required></div>
        <div class="col-md-4"><label for="expense_date" class="form-label">Expense Date</label><input type="date" class="form-control" id="expense_date" name="expense_date" value="{{ old('expense_date', $expense?->expense_date?->format('Y-m-d') ?? now()->format('Y-m-d')) }}" required></div>
        <div class="col-md-4"><label for="category" class="form-label">Category</label><select class="form-select" id="category" name="category" required><option value="">Select category</option>@foreach (['Supplies', 'Equipment', 'Maintenance', 'Utilities', 'Transport', 'Food', 'Services', 'Other'] as $category)<option value="{{ $category }}" @selected(old('category', $expense?->category) === $category)>{{ $category }}</option>@endforeach</select></div>
        <div class="col-md-4"><label for="payee" class="form-label">Supplier / Payee</label><input class="form-control" id="payee" name="payee" maxlength="255" value="{{ old('payee', $expense?->payee) }}" required></div>
        <div class="col-md-4"><label for="amount" class="form-label">Amount (NGN)</label><div class="input-group"><span class="input-group-text">N</span><input type="text" inputmode="decimal" class="form-control" id="amount" name="amount" value="{{ old('amount', $expense ? number_format($expense->amount_kobo / 100, 2, '.', '') : '') }}" required></div></div>
        <div class="col-12"><label for="description" class="form-label">Description / Purpose</label><textarea class="form-control" id="description" name="description" rows="4" maxlength="5000" required>{{ old('description', $expense?->description) }}</textarea></div>
        <div class="col-12"><h3 class="h5 mt-3">Payee Bank Details</h3></div>
        <div class="col-md-4"><label for="bank_name" class="form-label">Bank Name</label><input class="form-control" id="bank_name" name="bank_name" maxlength="255" value="{{ old('bank_name', $expense?->bank_name) }}"></div>
        <div class="col-md-4"><label for="account_name" class="form-label">Account Name</label><input class="form-control" id="account_name" name="account_name" maxlength="255" value="{{ old('account_name', $expense?->account_name) }}"></div>
        <div class="col-md-4"><label for="account_number" class="form-label">Account Number</label><input class="form-control" id="account_number" name="account_number" inputmode="numeric" pattern="[0-9]{10}" minlength="10" maxlength="10" value="{{ old('account_number', $expense?->account_number) }}"></div>
        @foreach (['invoice' => 'Invoice', 'receipt' => 'Purchase Receipt'] as $asset => $label)
            <div class="col-md-6">
                <label for="{{ $asset }}" class="form-label">{{ $label }} (Optional, PDF/JPG/PNG, max 10 MB)</label><input type="file" class="form-control" id="{{ $asset }}" name="{{ $asset }}" accept="application/pdf,image/jpeg,image/png">
                @if ($expense?->getAttribute($asset.'_path'))
                    <div class="d-flex flex-wrap align-items-center gap-3 mt-2"><a href="{{ route('expenses.attachment', [$expense, $asset]) }}"><i class="la la-download" aria-hidden="true"></i> Current {{ $label }}</a><div class="form-check"><input type="checkbox" class="form-check-input" id="remove_{{ $asset }}" name="remove_{{ $asset }}" value="1" @checked(old('remove_'.$asset))><label for="remove_{{ $asset }}" class="form-check-label">Remove</label></div></div>
                @endif
            </div>
        @endforeach
        <div class="col-12 d-flex gap-2 mt-4"><button class="btn btn-primary"><i class="la la-save" aria-hidden="true"></i> Save Draft</button><a class="btn btn-outline-secondary" href="{{ $expense ? route('expenses.show', $expense) : route('expenses.index') }}">Cancel</a></div>
    </form>
    <script>
        const expenseAmountInput = document.getElementById('amount');
        const validateExpenseAmount = () => {
            const raw = expenseAmountInput.value.replace(/,/g, '');
            const valid = raw === '' || (/^\d{1,9}(?:\.\d{1,2})?$/.test(raw) && Number(raw) > 0);
            expenseAmountInput.setCustomValidity(valid ? '' : 'Enter an amount from N0.01 to N999,999,999.99 with at most two decimal places.');
        };
        const formatExpenseAmount = () => {
            const raw = expenseAmountInput.value.replace(/,/g, '');
            if (/^\d{1,9}(?:\.\d{1,2})?$/.test(raw)) {
                const parts = raw.split('.');
                expenseAmountInput.value = Number(parts[0]).toLocaleString('en-NG') + (parts.length > 1 ? '.' + parts[1] : '');
            }
            validateExpenseAmount();
        };
        expenseAmountInput.addEventListener('input', validateExpenseAmount);
        expenseAmountInput.addEventListener('focus', () => {
            expenseAmountInput.value = expenseAmountInput.value.replace(/,/g, '');
        });
        expenseAmountInput.addEventListener('blur', formatExpenseAmount);
        expenseAmountInput.form.addEventListener('submit', () => {
            expenseAmountInput.value = expenseAmountInput.value.replace(/,/g, '');
        });
        formatExpenseAmount();
    </script>
@endsection
