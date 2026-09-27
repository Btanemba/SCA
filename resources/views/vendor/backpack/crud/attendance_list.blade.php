@extends(backpack_view('blank'))

@php
    $listUrl = url($crud->route);
    $persistentTableSlug = \Illuminate\Support\Str::slug($crud->getOperationSetting('datatablesUrl'));
    $selectedDate = request()->query('date');
    $selectedDate = is_string($selectedDate) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $selectedDate)
        ? $selectedDate
        : null;
    $selectedMonth = request()->query('month');
    $selectedMonth = is_string($selectedMonth) && preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $selectedMonth)
        ? $selectedMonth
        : null;
    if ($selectedDate !== null) {
        $selectedMonth = null;
    }
    $selectedStatus = request()->query('status') ?: (request()->boolean('present') ? 'present' : null);
    $hasFilters = filled($selectedMonth) || filled($selectedDate) || in_array($selectedStatus, ['present', 'picked_up'], true);
    $filterUrl = function (?string $status) use ($listUrl, $selectedDate, $selectedMonth) {
        $query = array_filter([
            'date' => $selectedDate,
            'month' => $selectedMonth,
            'status' => $status,
        ], fn ($value) => $value !== null && $value !== '');

        return $listUrl . (count($query) ? '?' . http_build_query($query) : '');
    };
@endphp

@section('content')
    <style>
        .attendance-list {
            --attendance-ink: #233b36;
            --attendance-muted: #687b76;
            --attendance-line: #dce8e3;
            color: var(--attendance-ink);
        }

        .attendance-list .attendance-heading {
            align-items: center;
            background: linear-gradient(110deg, #e8f5ec 0%, #f5f9f5 58%, #e6f4f3 100%);
            border: 1px solid var(--attendance-line);
            border-radius: 8px;
            display: flex;
            flex-wrap: wrap;
            gap: 16px;
            justify-content: space-between;
            margin-bottom: 16px;
            padding: 18px 20px;
        }

        .attendance-list .attendance-heading h1 {
            font-size: 1.45rem;
            font-weight: 700;
            margin: 0;
        }

        .attendance-list .attendance-heading p {
            color: var(--attendance-muted);
            margin: 4px 0 0;
        }

        .attendance-list .attendance-filter {
            align-items: end;
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .attendance-list .attendance-filter label {
            color: var(--attendance-muted);
            display: block;
            font-size: .78rem;
            font-weight: 600;
            margin-bottom: 4px;
        }

        .attendance-list .attendance-filter .form-control {
            min-width: 180px;
        }

        .attendance-list .attendance-stats {
            display: grid;
            gap: 10px;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            margin-bottom: 16px;
        }

        .attendance-list .attendance-stat {
            background: #fff;
            border: 1px solid var(--attendance-line);
            border-left: 4px solid #73b79e;
            border-radius: 7px;
            color: inherit;
            display: block;
            padding: 12px 15px;
            text-decoration: none;
            transition: border-color .15s ease, background-color .15s ease;
        }

        .attendance-list .attendance-stat:hover,
        .attendance-list .attendance-stat.is-active {
            background: #f0f8f3;
            border-color: #9acdb8;
            color: inherit;
            text-decoration: none;
        }

        .attendance-list .attendance-stat.stat-present { border-left-color: #28a879; }
        .attendance-list .attendance-stat.stat-picked-up { border-left-color: #d0a33d; }
        .attendance-list .stat-label { color: var(--attendance-muted); display: block; font-size: .8rem; }
        .attendance-list .stat-value { display: block; font-size: 1.3rem; font-weight: 700; line-height: 1.35; }
        .attendance-list .attendance-table { background: #fff; border: 1px solid var(--attendance-line); border-radius: 8px; padding: 8px; }

        @media (max-width: 700px) {
            .attendance-list .attendance-stats { grid-template-columns: 1fr; }
            .attendance-list .attendance-filter { width: 100%; }
            .attendance-list .attendance-filter .form-control { min-width: 0; width: 100%; }
        }
    </style>

    <div class="container-fluid attendance-list">
        <header class="attendance-heading">
            <div>
                <h1>Attendance Records</h1>
                <p>Daily drop-off and pickup activity</p>
            </div>
            <form class="attendance-filter" method="get" action="{{ $listUrl }}">
                <div>
                    <label for="attendance-date">Attendance day</label>
                    <input id="attendance-date" class="form-control" type="date" name="date" value="{{ $selectedDate ?? '' }}">
                </div>
                <div>
                    <label for="attendance-month">Attendance month</label>
                    <input id="attendance-month" class="form-control" type="month" name="month" value="{{ $selectedMonth ?? '' }}">
                </div>
                <button class="btn btn-primary" type="submit">Apply</button>
                @if ($hasFilters)
                    <a id="attendance-reset" class="btn btn-outline-secondary" href="{{ $listUrl }}" data-persistent-table-slug="{{ $persistentTableSlug }}">Reset</a>
                @endif
            </form>
        </header>

        <div class="attendance-stats" aria-label="Attendance summary">
            <a class="attendance-stat {{ $selectedStatus ? '' : 'is-active' }}" href="{{ $filterUrl(null) }}">
                <span class="stat-label">All records</span>
                <span class="stat-value">{{ $attendanceStats['total'] }}</span>
            </a>
            <a class="attendance-stat stat-present {{ $selectedStatus === 'present' ? 'is-active' : '' }}" href="{{ $filterUrl('present') }}">
                <span class="stat-label">Currently present</span>
                <span class="stat-value">{{ $attendanceStats['present'] }}</span>
            </a>
            <a class="attendance-stat stat-picked-up {{ $selectedStatus === 'picked_up' ? 'is-active' : '' }}" href="{{ $filterUrl('picked_up') }}">
                <span class="stat-label">Picked up</span>
                <span class="stat-value">{{ $attendanceStats['picked_up'] }}</span>
            </a>
        </div>

        <div class="attendance-table">
            <x-backpack::datatable :controller="$controller" :crud="$crud" :modifiesUrl="true" />
        </div>
    </div>
    <script>
        (() => {
            const attendanceDate = document.getElementById('attendance-date');
            const attendanceMonth = document.getElementById('attendance-month');
            attendanceDate.addEventListener('change', () => {
                if (attendanceDate.value) attendanceMonth.value = '';
            });
            attendanceMonth.addEventListener('change', () => {
                if (attendanceMonth.value) attendanceDate.value = '';
            });

            document.getElementById('attendance-reset')?.addEventListener('click', (event) => {
                const resetLink = event.currentTarget;
                const slug = resetLink.dataset.persistentTableSlug;
                localStorage.removeItem(`${slug}_list_url`);
                localStorage.removeItem(`${slug}_list_url_time`);

                const tableId = document.querySelector('.attendance-table .crud-table')?.id;
                if (tableId) {
                    Object.keys(localStorage)
                        .filter((key) => key.startsWith(`DataTables_${tableId}`))
                        .forEach((key) => localStorage.removeItem(key));
                }
            });
        })();
    </script>
@endsection