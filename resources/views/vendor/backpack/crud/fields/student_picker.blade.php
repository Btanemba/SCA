{{-- student picker table --}}
@php
    $key_attribute = (new $field['model'])->getKeyName();
    $lockedValues = $field['value'] ?? $field['default'] ?? [];

    if (is_a($lockedValues, \Illuminate\Support\Collection::class)) {
        $lockedValues = $lockedValues->pluck($key_attribute)->toArray();
    } elseif (is_string($lockedValues)) {
        $lockedValues = json_decode($lockedValues, true) ?? [];
    }
    $lockedValues = array_map('strval', (array) $lockedValues);

    $field['value'] = old_empty_or_null($field['name'], []) ?? $lockedValues;
    if (is_string($field['value'])) {
        $field['value'] = json_decode($field['value'], true) ?? [];
    }
    $field['value'] = array_values(array_unique(array_merge((array) $field['value'], $lockedValues)));
    $selectedValues = array_map('strval', $field['value']);

    $studentQuery = $field['model']::query();
    if (isset($field['options'])) {
        $studentQuery = call_user_func($field['options'], $studentQuery);
    }
    $students = $studentQuery->whereKey($selectedValues)->get();
    $guardianLinks = collect();
    $oldGuardianRelationships = old('guardian_relationships', []);

    if (! empty($field['parent_id'])) {
        $parent = $field['model']::find($field['parent_id']);
        if ($parent) {
            $guardianLinks = $parent->children()->whereKey($selectedValues)->get()->keyBy($key_attribute);
        }
    }

    $field['wrapper']['data-init-function'] ??= 'bpFieldInitStudentPicker';
@endphp

@include('crud::fields.inc.wrapper_start')

    <label>{!! $field['label'] !!}</label>
    @include('crud::fields.inc.translatable_icon')

    <input type="hidden" value='@json($field['value'])' name="{{ $field['name'] }}">

    <div class="d-flex justify-content-end mb-2">
        <button type="button" class="btn btn-sm btn-outline-primary student-picker-toggle">Add student</button>
    </div>

    <div class="student-picker-search-panel mb-3" hidden>
        <input type="search" class="form-control student-picker-search" placeholder="Search by student ID or name" autocomplete="off" data-search-url="{{ $field['search_url'] }}" data-parent-id="{{ $field['parent_id'] ?? '' }}">
        <div class="student-picker-search-status small text-muted mt-1" aria-live="polite">Enter at least 2 characters to search.</div>
        <div class="student-picker-search-results mt-2" role="list"></div>
    </div>

    <div class="table-responsive">
        <table class="table table-sm table-bordered mb-0">
            <thead>
                <tr>
                    <th style="width: 40px"></th>
                    <th>Student ID</th>
                    <th>First name</th>
                    <th>Last name</th>
                    <th>Relationship</th>
                    <th>Primary contact</th>
                    <th>Details</th>
                </tr>
            </thead>
            <tbody class="student-picker-options">
                @forelse ($students as $student)
                    @php
                        $isLocked = in_array((string) $student->{$key_attribute}, $lockedValues, true);
                        $studentId = $student->{$key_attribute};
                        $guardianLink = $guardianLinks->get($studentId);
                        $relationshipValue = data_get(
                            $oldGuardianRelationships,
                            $studentId.'.relationship',
                            $guardianLink?->pivot?->relationship
                        );
                        $isPrimaryContact = filter_var(data_get(
                            $oldGuardianRelationships,
                            $studentId.'.is_primary_contact',
                            $guardianLink?->pivot?->is_primary_contact ?? false
                        ), FILTER_VALIDATE_BOOLEAN);
                    @endphp
                    <tr>
                        <td><input type="checkbox" class="student-picker-student-checkbox" value="{{ $studentId }}" @checked(in_array((string) $studentId, $selectedValues, true)) @disabled($isLocked)></td>
                        <td><button type="button" class="btn btn-link p-0 text-start student-picker-details-toggle" data-details-url="{{ route('person.students.details', $student->getKey()) }}" aria-expanded="false">{{ $student->student_id }}</button></td>
                        <td>{{ $student->first_name }}</td>
                        <td>{{ $student->last_name }}</td>
                        <td>
                            <select class="form-select form-select-sm" name="guardian_relationships[{{ $studentId }}][relationship]" aria-label="Relationship to {{ $student->full_name }}">
                                <option value="">Select relationship</option>
                                @if (filled($relationshipValue) && ! array_key_exists($relationshipValue, $field['relationship_options']))
                                    <option value="{{ $relationshipValue }}" selected>{{ $relationshipValue }}</option>
                                @endif
                                @foreach ($field['relationship_options'] as $value => $label)
                                    <option value="{{ $value }}" @selected((string) $relationshipValue === (string) $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </td>
                        <td class="text-center">
                            <input type="hidden" name="guardian_relationships[{{ $studentId }}][is_primary_contact]" value="0">
                            <input type="checkbox" class="form-check-input" name="guardian_relationships[{{ $studentId }}][is_primary_contact]" value="1" aria-label="Mark {{ $student->full_name }}'s parent as a primary contact" @checked($isPrimaryContact)>
                        </td>
                        <td><button type="button" class="btn btn-sm btn-outline-secondary student-picker-details-toggle" data-details-url="{{ route('person.students.details', $student->getKey()) }}" aria-expanded="false">View</button></td>
                    </tr>
                @empty
                    <tr class="student-picker-empty"><td colspan="7" class="text-muted">No students linked.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if (isset($field['hint']))
        <p class="help-block">{!! $field['hint'] !!}</p>
    @endif
@include('crud::fields.inc.wrapper_end')

@push('crud_fields_scripts')
<script>
    function bpFieldInitStudentPicker(element) {
        const hiddenInput = element.find('input[type=hidden]').first();
        const tableBody = element.find('tbody.student-picker-options');
        const searchPanel = element.find('.student-picker-search-panel');
        const searchInput = element.find('.student-picker-search');
        const searchStatus = element.find('.student-picker-search-status');
        const searchResults = element.find('.student-picker-search-results');
        const relationshipOptions = @json($field['relationship_options']);
        let searchTimer;
        let searchSequence = 0;

        function syncValue() {
            const selected = [];
            tableBody.find('.student-picker-student-checkbox:checked').each(function () {
                selected.push(String($(this).val()));
            });
            hiddenInput.val(JSON.stringify(selected)).trigger('change');
        }

        function addGuardianFields(row, student) {
            const relationshipCell = document.createElement('td');
            const relationshipSelect = document.createElement('select');
            relationshipSelect.className = 'form-select form-select-sm';
            relationshipSelect.name = `guardian_relationships[${student.id}][relationship]`;
            relationshipSelect.setAttribute('aria-label', `Relationship to ${student.first_name} ${student.last_name}`);

            const placeholder = document.createElement('option');
            placeholder.value = '';
            placeholder.textContent = 'Select relationship';
            relationshipSelect.appendChild(placeholder);

            Object.entries(relationshipOptions).forEach(function ([value, label]) {
                const option = document.createElement('option');
                option.value = value;
                option.textContent = label;
                relationshipSelect.appendChild(option);
            });

            relationshipCell.appendChild(relationshipSelect);
            row.appendChild(relationshipCell);

            const primaryCell = document.createElement('td');
            primaryCell.className = 'text-center';
            const primaryName = `guardian_relationships[${student.id}][is_primary_contact]`;
            const primaryHidden = document.createElement('input');
            primaryHidden.type = 'hidden';
            primaryHidden.name = primaryName;
            primaryHidden.value = '0';
            const primaryCheckbox = document.createElement('input');
            primaryCheckbox.type = 'checkbox';
            primaryCheckbox.className = 'form-check-input';
            primaryCheckbox.name = primaryName;
            primaryCheckbox.value = '1';
            primaryCheckbox.setAttribute('aria-label', `Mark ${student.first_name} ${student.last_name}'s parent as a primary contact`);
            primaryCell.append(primaryHidden, primaryCheckbox);
            row.appendChild(primaryCell);
        }

        function addStudent(student) {
            if (tableBody.find(`input[value="${student.id}"]`).length) {
                return;
            }

            tableBody.find('.student-picker-empty').remove();
            const row = document.createElement('tr');
            const checkboxCell = document.createElement('td');
            const checkbox = document.createElement('input');
            checkbox.type = 'checkbox';
            checkbox.className = 'student-picker-student-checkbox';
            checkbox.value = student.id;
            checkbox.checked = true;
            checkboxCell.appendChild(checkbox);

            const idCell = document.createElement('td');
            const idButton = document.createElement('button');
            idButton.type = 'button';
            idButton.className = 'btn btn-link p-0 text-start student-picker-details-toggle';
            idButton.dataset.detailsUrl = student.details_url;
            idButton.setAttribute('aria-expanded', 'false');
            idButton.textContent = student.student_id || '';
            idCell.appendChild(idButton);
            row.appendChild(idCell);

            [student.first_name, student.last_name].forEach(function (value) {
                const cell = document.createElement('td');
                cell.textContent = value || '';
                row.appendChild(cell);
            });

            addGuardianFields(row, student);

            const detailsCell = document.createElement('td');
            const detailsButton = document.createElement('button');
            detailsButton.type = 'button';
            detailsButton.className = 'btn btn-sm btn-outline-secondary student-picker-details-toggle';
            detailsButton.dataset.detailsUrl = student.details_url;
            detailsButton.setAttribute('aria-expanded', 'false');
            detailsButton.textContent = 'View';
            detailsCell.appendChild(detailsButton);
            row.appendChild(detailsCell);

            row.insertBefore(checkboxCell, row.firstChild);
            tableBody.append(row);
            syncValue();
        }

        function renderResults(students) {
            searchResults.empty();
            const selected = new Set(JSON.parse(hiddenInput.val() || '[]').map(String));

            if (!students.length) {
                searchStatus.text('No matching students found.');
                return;
            }

            searchStatus.text(`${students.length} student${students.length === 1 ? '' : 's'} found.`);
            students.forEach(function (student) {
                const row = document.createElement('div');
                row.className = 'd-flex align-items-center justify-content-between border-bottom py-2';

                const label = document.createElement('span');
                label.textContent = `${student.student_id} | ${student.first_name} ${student.last_name}`;

                const details = document.createElement('div');
                details.className = 'd-flex align-items-center gap-2';
                details.append(label);
                if (student.has_other_guardians) {
                    const warning = document.createElement('span');
                    warning.className = 'badge bg-warning text-dark';
                    warning.textContent = 'Already linked to another parent';
                    details.append(warning);
                }

                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'btn btn-sm btn-primary';
                button.textContent = selected.has(String(student.id)) ? 'Added' : 'Add';
                button.disabled = selected.has(String(student.id));
                button.addEventListener('click', function () {
                    addStudent(student);
                    button.textContent = 'Added';
                    button.disabled = true;
                });

                row.append(details, button);
                searchResults.append(row);
            });
        }

        element.find('.student-picker-toggle').on('click', function () {
            searchPanel.prop('hidden', !searchPanel.prop('hidden'));
            if (!searchPanel.prop('hidden')) {
                searchInput.trigger('focus');
            }
        });

        tableBody.on('click', '.student-picker-details-toggle', function () {
            const studentRow = $(this).closest('tr');
            const existingDetails = studentRow.next('.student-picker-details-row');
            if (existingDetails.length) {
                existingDetails.remove();
                studentRow.find('.student-picker-details-toggle').attr('aria-expanded', 'false');
                return;
            }

            tableBody.find('.student-picker-details-row').remove();
            tableBody.find('.student-picker-details-toggle').attr('aria-expanded', 'false');

            const detailsRow = document.createElement('tr');
            detailsRow.className = 'student-picker-details-row';
            const detailsCell = document.createElement('td');
            detailsCell.colSpan = 7;
            detailsCell.className = 'p-3';
            detailsCell.textContent = 'Loading student details...';
            detailsRow.appendChild(detailsCell);
            studentRow.after(detailsRow);
            studentRow.find('.student-picker-details-toggle').attr('aria-expanded', 'true');

            fetch(this.dataset.detailsUrl, { headers: { Accept: 'text/html' } })
                .then(function (response) {
                    if (!response.ok) {
                        throw new Error('Student details failed to load');
                    }
                    return response.text();
                })
                .then(function (html) {
                    detailsCell.innerHTML = html;
                })
                .catch(function () {
                    detailsCell.textContent = 'Unable to load student details. Please try again.';
                });
        });

        searchInput.on('input', function () {
            clearTimeout(searchTimer);
            const term = searchInput.val().trim();
            const sequence = ++searchSequence;

            if (term.length < 2) {
                searchResults.empty();
                searchStatus.text('Enter at least 2 characters to search.');
                return;
            }

            searchStatus.text('Searching...');
            searchTimer = setTimeout(function () {
                const url = new URL(searchInput.data('search-url'), window.location.origin);
                url.searchParams.set('term', term);
                const parentId = searchInput.data('parent-id');
                if (parentId) {
                    url.searchParams.set('parent_id', parentId);
                }

                fetch(url, { headers: { Accept: 'application/json' } })
                    .then(function (response) {
                        if (!response.ok) {
                            throw new Error('Student search failed');
                        }
                        return response.json();
                    })
                    .then(function (students) {
                        if (sequence === searchSequence) {
                            renderResults(students);
                        }
                    })
                    .catch(function () {
                        if (sequence === searchSequence) {
                            searchResults.empty();
                            searchStatus.text('Unable to search students. Please try again.');
                        }
                    });
            }, 250);
        });

        tableBody.on('change', '.student-picker-student-checkbox', function () {
            if (!this.checked && !this.disabled) {
                const studentRow = $(this).closest('tr');
                studentRow.next('.student-picker-details-row').remove();
                studentRow.remove();
                if (!tableBody.find('tr').length) {
                    tableBody.append('<tr class="student-picker-empty"><td colspan="7" class="text-muted">No students linked.</td></tr>');
                }
            }
            syncValue();
        });
    }
</script>
@endpush
