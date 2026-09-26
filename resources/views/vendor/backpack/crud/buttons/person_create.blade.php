@if ($crud->hasAccess('create'))
    @php
        $selectedRoleCode = request()->query('role');
        $roles = \App\Models\SacRole::query()
            ->when(
                is_string($selectedRoleCode) && $selectedRoleCode !== '',
                fn ($query) => $query->where('code', $selectedRoleCode)
            )
            ->orderBy('order')
            ->orderBy('name')
            ->get(['id', 'name']);
        $createUrl = url($crud->route . '/create');
    @endphp

    <div class="dropdown">
        <button
            class="btn btn-primary dropdown-toggle"
            type="button"
            id="person-create-role-dropdown"
            data-toggle="dropdown"
            data-bs-toggle="dropdown"
            aria-haspopup="true"
            aria-expanded="false"
            bp-button="create"
            data-style="zoom-in"
        >
            <i class="la la-plus"></i> <span>Add person</span>
        </button>

        <ul class="dropdown-menu" aria-labelledby="person-create-role-dropdown">
            @forelse ($roles as $role)
                <li>
                    <a class="dropdown-item" href="{{ $createUrl . '?sac_role_id=' . $role->id }}">
                        {{ $role->name }}
                    </a>
                </li>
            @empty
                <li><span class="dropdown-item-text text-muted">No roles available</span></li>
            @endforelse
        </ul>
    </div>
@endif
