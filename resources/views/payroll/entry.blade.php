@extends('payroll.layout')

@section('payroll_content')
    @php
        $allowances = old('allowances', collect($entry?->allowances ?? [])->map(fn ($item) => ['label' => $item['label'], 'amount' => number_format($item['kobo'] / 100, 2, '.', '')])->all());
        $deductions = old('deductions', collect($entry?->deductions ?? [])->map(fn ($item) => ['label' => $item['label'], 'amount' => number_format($item['kobo'] / 100, 2, '.', '')])->all());
        $roleLabels = [
            \App\Models\Person::ROLE_STAFF => 'Staff',
            \App\Models\Person::ROLE_ADMIN => 'Administrator',
            \App\Models\Person::ROLE_ACCOUNTANT => 'Accountant',
            \App\Models\Person::ROLE_SECURITY => 'Security',
        ];
    @endphp
    <a href="{{ route('payroll.show', $payroll) }}" class="d-inline-block mb-3"><i class="la la-arrow-left" aria-hidden="true"></i> Payroll</a>
    <h2 class="mb-4">{{ $entry ? 'Edit Salary' : 'Add Salary' }}</h2>
    <form action="{{ $entry ? route('payroll.entries.update', [$payroll, $entry]) : route('payroll.entries.store', $payroll) }}" method="post" id="salary-form">
        @csrf @if ($entry) @method('PUT') @endif
        <div class="row">
            <div class="col-md-6 mb-3"><label for="person_id" class="form-label">Employee</label><select name="person_id" id="person_id" class="form-select" required><option value="">Select employee</option>@foreach ($staff as $person)<option value="{{ $person->id }}" data-bank-name="{{ $person->bank_name ?: ($entry?->person_id === $person->id ? $entry->bank_name : '') }}" data-account-name="{{ $person->account_name ?: ($entry?->person_id === $person->id ? $entry->account_name : '') }}" data-account-number="{{ $person->account_number ?: ($entry?->person_id === $person->id ? $entry->account_number : '') }}" @selected((int) old('person_id', $entry?->person_id) === $person->id)>{{ $person->full_name }} [{{ $roleLabels[$person->sacRole->code] }}]</option>@endforeach</select></div>
            <div class="col-md-6 mb-3"><label for="basic" class="form-label">Basic Salary (Naira)</label><div class="input-group"><span class="input-group-text">N</span><input type="text" inputmode="decimal" class="form-control" name="basic" id="basic" data-currency-input value="{{ old('basic', $entry ? number_format($entry->basic_kobo / 100, 2, '.', '') : '') }}" required></div></div>
            @foreach (['bank_name' => 'Employee bank', 'account_name' => 'Account name', 'account_number' => 'Account number'] as $field => $label)
                <div class="col-md-4 mb-3"><label for="{{ $field }}" class="form-label">{{ $label }}</label><input class="form-control bg-secondary-subtle" id="{{ $field }}" value="{{ old($field, $entry?->$field) }}" readonly aria-readonly="true"></div>
            @endforeach
        </div>
        @foreach (['allowances' => $allowances, 'deductions' => $deductions] as $group => $items)
            <div class="border-top py-3">
                <div class="d-flex justify-content-between align-items-center mb-2"><h3 class="h5 mb-0">{{ ucfirst($group) }}</h3><button class="btn btn-sm btn-outline-primary" type="button" data-add="{{ $group }}"><i class="la la-plus" aria-hidden="true"></i> Add {{ $group === 'allowances' ? 'Allowance' : 'Deduction' }}</button></div>
                <div id="{{ $group }}-rows" data-next="{{ count($items) }}">
                    @foreach ($items as $index => $item)
                        <div class="row g-2 mb-2 salary-item">
                            <div class="col-7"><input class="form-control" name="{{ $group }}[{{ $index }}][label]" aria-label="{{ ucfirst($group) }} description" placeholder="Description" maxlength="100" value="{{ $item['label'] }}" required></div>
                            <div class="col-4"><div class="input-group"><span class="input-group-text">N</span><input class="form-control" type="text" inputmode="decimal" data-currency-input name="{{ $group }}[{{ $index }}][amount]" aria-label="Amount in naira" value="{{ $item['amount'] }}" required></div></div>
                            <div class="col-1"><button type="button" class="btn btn-outline-danger px-2" data-remove title="Remove item" aria-label="Remove item"><i class="la la-times" aria-hidden="true"></i></button></div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
        <div class="border-top py-3 d-flex flex-wrap gap-4"><div>Gross: <strong id="gross">NGN 0.00</strong></div><div>Deductions: <strong id="deduction-total">NGN 0.00</strong></div><div>Net: <strong id="net">NGN 0.00</strong></div></div>
        <button class="btn btn-primary"><i class="la la-save" aria-hidden="true"></i> Save Salary</button>
    </form>
    <template id="salary-row"><div class="row g-2 mb-2 salary-item"><div class="col-7"><input class="form-control" data-field="label" placeholder="Description" maxlength="100" required></div><div class="col-4"><div class="input-group"><span class="input-group-text">N</span><input class="form-control" data-field="amount" data-currency-input type="text" inputmode="decimal" aria-label="Amount in naira" required></div></div><div class="col-1"><button type="button" class="btn btn-outline-danger px-2" data-remove title="Remove item" aria-label="Remove item"><i class="la la-times" aria-hidden="true"></i></button></div></div></template>
    <script>
        const salaryForm = document.getElementById('salary-form');
        const personSelect = document.getElementById('person_id');
        const updateBankDetails = () => {
            const option = personSelect.selectedOptions[0];
            document.getElementById('bank_name').value = option?.dataset.bankName || '';
            document.getElementById('account_name').value = option?.dataset.accountName || '';
            document.getElementById('account_number').value = option?.dataset.accountNumber || '';
        };
        personSelect.addEventListener('change', updateBankDetails);
        updateBankDetails();
        const formatCurrencyInput = value => {
            const cleaned = String(value ?? '').replace(/,/g, '').replace(/[^\d.]/g, '');
            if (!cleaned) return '';
            const decimalIndex = cleaned.indexOf('.');
            const whole = (decimalIndex === -1 ? cleaned : cleaned.slice(0, decimalIndex)).replace(/^0+(?=\d)/, '') || '0';
            const fraction = decimalIndex === -1 ? null : cleaned.slice(decimalIndex + 1).replace(/\./g, '').slice(0, 2);
            const grouped = whole.replace(/\B(?=(\d{3})+(?!\d))/g, ',');
            return fraction === null ? grouped : `${grouped}.${fraction}`;
        };
        const toKobo = value => {
            const parts = String(value || '0').replace(/,/g, '').split('.');
            return Number(parts[0]) * 100 + Number((parts[1] || '').padEnd(2, '0').slice(0, 2));
        };
        const money = amount => 'N' + (amount / 100).toLocaleString('en-NG', {minimumFractionDigits: 0, maximumFractionDigits: 2});
        const recalculate = () => {
            const sum = group => Array.from(document.querySelectorAll(`#${group}-rows [data-currency-input]`)).reduce((total, input) => total + toKobo(input.value), 0);
            const gross = toKobo(document.getElementById('basic').value) + sum('allowances');
            const deductions = sum('deductions');
            document.getElementById('gross').textContent = money(gross);
            document.getElementById('deduction-total').textContent = money(deductions);
            document.getElementById('net').textContent = money(gross - deductions);
        };
        salaryForm.addEventListener('input', recalculate);
        salaryForm.querySelectorAll('[data-currency-input]').forEach(input => input.value = formatCurrencyInput(input.value));
        salaryForm.addEventListener('focusin', event => {
            if (event.target.matches('[data-currency-input]')) event.target.value = event.target.value.replace(/,/g, '');
        });
        salaryForm.addEventListener('focusout', event => {
            if (event.target.matches('[data-currency-input]')) event.target.value = formatCurrencyInput(event.target.value);
        });
        salaryForm.addEventListener('submit', () => salaryForm.querySelectorAll('[data-currency-input]').forEach(input => input.value = input.value.replace(/,/g, '')));
        salaryForm.addEventListener('click', event => {
            const remove = event.target.closest('[data-remove]');
            if (remove) { remove.closest('.salary-item').remove(); recalculate(); }
            const add = event.target.closest('[data-add]');
            if (!add) return;
            const group = add.dataset.add;
            const container = document.getElementById(group + '-rows');
            if (container.children.length >= 30) return;
            const index = Number(container.dataset.next);
            container.dataset.next = index + 1;
            const row = document.getElementById('salary-row').content.cloneNode(true);
            row.querySelectorAll('[data-field]').forEach(input => {
                input.name = `${group}[${index}][${input.dataset.field}]`;
                if (input.dataset.field === 'label') input.setAttribute('aria-label', group + ' description');
            });
            container.appendChild(row);
        });
        recalculate();
    </script>
@endsection
