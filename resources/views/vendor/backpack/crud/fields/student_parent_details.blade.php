@php
    $field['wrapper']['data-init-function'] = 'bpFieldInitStudentParentDetails';
@endphp

@include('crud::fields.inc.wrapper_start')
    <div class="student-parent-details-controls d-none">
        <div class="form-check">
            <input id="student-use-parent-details" type="checkbox" class="form-check-input student-use-parent-details">
            <label class="form-check-label" for="student-use-parent-details">Use parent's details</label>
        </div>
        <select class="form-select mt-2 student-details-parent-select d-none" aria-label="Parent whose details to use"></select>
    </div>
@include('crud::fields.inc.wrapper_end')

@push('crud_fields_scripts')
<script>
    function bpFieldInitStudentParentDetails(element) {
        const field = element[0];
        const form = field.closest('form');
        const controls = field.querySelector('.student-parent-details-controls');
        const checkbox = field.querySelector('.student-use-parent-details');
        const parentSelect = field.querySelector('.student-details-parent-select');
        const sharedFields = @json($field['shared_fields']);
        const parents = @json($field['parents']);
        let parentForm = null;
        let previousValues = null;

        try {
            if (window.frameElement?.matches('.student-picker-create-frame')) {
                parentForm = window.frameElement.closest('form');
            }
        } catch (error) {
            parentForm = null;
        }

        if (!form || (!parentForm && parents.length === 0)) {
            return;
        }

        controls.classList.remove('d-none');
        parents.forEach((parent, index) => {
            parentSelect.add(new Option(parent.name, index));
        });
        parentSelect.classList.toggle('d-none', Boolean(parentForm) || parents.length < 2);

        const inputs = sharedFields.map((name) => form.querySelector(`[name="${name}"]`)).filter(Boolean);
        const setValue = (input, value) => {
            input.value = value;
            input.dispatchEvent(new Event('input', { bubbles: true }));
            input.dispatchEvent(new Event('change', { bubbles: true }));
        };
        const copyParent = () => {
            const details = parentForm
                ? Object.fromEntries(sharedFields.map((name) => [name, parentForm.querySelector(`[name="${name}"]`)?.value || '']))
                : parents[parentSelect.value].details;
            inputs.forEach((input) => setValue(input, details[input.name] || ''));
        };

        checkbox.addEventListener('change', () => {
            if (checkbox.checked) {
                previousValues = inputs.map((input) => ({ input, value: input.value }));
                copyParent();
            } else if (previousValues) {
                previousValues.forEach(({ input, value }) => setValue(input, value));
                previousValues = null;
            }
        });
        parentSelect.addEventListener('change', () => {
            if (checkbox.checked) {
                copyParent();
            }
        });
    }
</script>
@endpush
