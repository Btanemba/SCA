<div class="form-group">
    <label>Current photo</label>

    <div id="person-photo-preview" style="width: 200px; height: 200px; background-color: #fff; border: 1px solid #ddd; border-radius: 8px; display: flex; align-items: center; justify-content: center; overflow: hidden;">
        @php
            $photoPath = ($entry ?? null)?->image_path;
            $photoDisk = \Illuminate\Support\Facades\Storage::disk('public');
            $photoExists = is_string($photoPath) && $photoDisk->exists($photoPath);
        @endphp

        @if ($photoExists)
            <img
                src="{{ $photoDisk->url($photoPath) }}"
                style="width: 100%; height: 100%; object-fit: contain;"
                alt="Person photo"
            >
        @elseif (! empty($photoPath))
            <span class="text-muted">Photo unavailable. Please upload it again.</span>
        @else
            <span class="text-muted">No photo uploaded</span>
        @endif
    </div>
</div>

@push('after_scripts')
    <script>
        function initializePersonPhotoPreview() {
            const imageInputSelector = 'input.file_input[type="file"][name="image_path"]';
            const preview = document.getElementById('person-photo-preview');

            if (!preview) {
                return;
            }

            let selectedImageUrl = null;

            function clearPreview(input = null) {
                if (selectedImageUrl) {
                    URL.revokeObjectURL(selectedImageUrl);
                    selectedImageUrl = null;
                }

                if (input) {
                    input.value = '';
                    const marker = input.nextElementSibling;
                    if (!marker || marker.type !== 'hidden' || marker.name !== 'image_path') {
                        const removalMarker = document.createElement('input');
                        removalMarker.type = 'hidden';
                        removalMarker.name = 'image_path';
                        removalMarker.value = '';
                        input.insertAdjacentElement('afterend', removalMarker);
                    }
                }

                preview.innerHTML = '<span class="text-muted">No photo uploaded</span>';
            }

            function showPreview(file) {
                if (!file.type.startsWith('image/')) {
                    return;
                }

                if (selectedImageUrl) {
                    URL.revokeObjectURL(selectedImageUrl);
                }

                selectedImageUrl = URL.createObjectURL(file);
                preview.innerHTML = '';

                const image = document.createElement('img');
                image.src = selectedImageUrl;
                image.alt = 'Selected person photo';
                image.style.width = '100%';
                image.style.height = '100%';
                image.style.objectFit = 'contain';

                preview.appendChild(image);
            }

            document.addEventListener('click', function (event) {
                const target = event.target instanceof Element ? event.target : null;
                const clearButton = target?.closest('.file_clear_button');
                const uploadField = clearButton?.closest('.form-group');

                if (uploadField?.querySelector(imageInputSelector)) {
                    clearPreview();
                }
            }, true);

            document.addEventListener('change', function (event) {
                const input = event.target;
                if (!(input instanceof HTMLInputElement) || !input.matches(imageInputSelector)) {
                    return;
                }

                const file = input.files?.[0];
                if (!file) {
                    clearPreview(input);
                    return;
                }

                showPreview(file);
            });

            document.addEventListener('cancel', function (event) {
                const input = event.target;
                if (input instanceof HTMLInputElement && input.matches(imageInputSelector)) {
                    clearPreview(input);
                }
            }, true);
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initializePersonPhotoPreview);
        } else {
            initializePersonPhotoPreview();
        }
    </script>
@endpush
