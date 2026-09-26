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
            const imageInput = document.querySelector('input.file_input[type="file"][name="image_path"]');
            const preview = document.getElementById('person-photo-preview');

            if (!imageInput || !preview) {
                return;
            }

            imageInput.addEventListener('change', function (event) {
                const file = event.target.files[0];

                if (!file || !file.type.startsWith('image/')) {
                    return;
                }

                const reader = new FileReader();

                reader.addEventListener('load', function () {
                    preview.innerHTML = '';

                    const image = document.createElement('img');
                    image.src = reader.result;
                    image.alt = 'Selected person photo';
                    image.style.width = '100%';
                    image.style.height = '100%';
                    image.style.objectFit = 'contain';

                    preview.appendChild(image);
                });

                reader.readAsDataURL(file);
            });
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initializePersonPhotoPreview);
        } else {
            initializePersonPhotoPreview();
        }
    </script>
@endpush
