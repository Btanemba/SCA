<div class="row g-3 mb-4">
    <div class="col-md-3">
        <label class="form-label" for="audit-created-by">Created by</label>
        <input class="form-control bg-light" id="audit-created-by" value="{{ $auditEntry->created_by_name }}" readonly>
    </div>
    <div class="col-md-3">
        <label class="form-label" for="audit-created-at">Created at</label>
        <input class="form-control bg-light" id="audit-created-at" value="{{ $auditEntry->created_at?->format('d M Y H:i') }}" readonly>
    </div>
    @if ($showUpdater ?? true)
        <div class="col-md-3">
            <label class="form-label" for="audit-updated-by">Updated by</label>
            <input class="form-control bg-light" id="audit-updated-by" value="{{ $auditEntry->updated_by_name }}" readonly>
        </div>
    @endif
    <div class="col-md-3">
        <label class="form-label" for="audit-updated-at">Updated at</label>
        <input class="form-control bg-light" id="audit-updated-at" value="{{ $auditEntry->updated_at?->format('d M Y H:i') }}" readonly>
    </div>
</div>
