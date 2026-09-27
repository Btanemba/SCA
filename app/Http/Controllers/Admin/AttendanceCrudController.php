<?php

namespace App\Http\Controllers\Admin;

use App\Models\Attendance;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

class AttendanceCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;

    public function setup(): void
    {
        CRUD::setModel(Attendance::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/attendance');
        CRUD::setEntityNameStrings('attendance record', 'attendance records');
        CRUD::addClause('with', [
            'child',
            'droppedOffRecordedBy',
            'pickedUpRecordedBy',
        ]);
    }

    public function index()
    {
        $this->crud->hasAccessOrFail('list');

        $date = request()->query('date');
        $summaryQuery = Attendance::query();
        if (is_string($date) && $date !== '') {
            $summaryQuery->whereDate('attendance_date', $date);
        } elseif ($month = $this->selectedMonth()) {
            $summaryQuery->whereYear('attendance_date', $month[0])
                ->whereMonth('attendance_date', $month[1]);
        }
        $summary = $summaryQuery
            ->selectRaw('COUNT(*) as total_count, SUM(CASE WHEN picked_up_at IS NULL THEN 1 ELSE 0 END) as present_count, SUM(CASE WHEN picked_up_at IS NOT NULL THEN 1 ELSE 0 END) as picked_up_count')
            ->first();

        $this->data['crud'] = $this->crud;
        $this->data['title'] = $this->crud->getTitle() ?? mb_ucfirst($this->crud->entity_name_plural);
        $this->data['controller'] = get_class($this);
        $this->data['attendanceStats'] = [
            'total' => (int) ($summary->total_count ?? 0),
            'present' => (int) ($summary->present_count ?? 0),
            'picked_up' => (int) ($summary->picked_up_count ?? 0),
        ];

        return view('vendor.backpack.crud.attendance_list', $this->data);
    }

    protected function setupListOperation(): void
    {
        $date = request()->query('date');
        if (is_string($date) && $date !== '') {
            CRUD::addClause('whereDate', 'attendance_date', $date);
        } elseif ($month = $this->selectedMonth()) {
            CRUD::addClause('whereYear', 'attendance_date', $month[0]);
            CRUD::addClause('whereMonth', 'attendance_date', $month[1]);
        }

        $status = request()->query('status');
        if ($status === 'present' || request()->boolean('present')) {
            CRUD::addClause('whereNull', 'picked_up_at');
        } elseif ($status === 'picked_up') {
            CRUD::addClause('whereNotNull', 'picked_up_at');
        }

        CRUD::orderBy('attendance_date', 'desc');
        CRUD::orderBy('dropped_off_at', 'desc');

        CRUD::column('attendance_date')->type('date')->label('Date');
        CRUD::column('child.student_id')->label('Student ID');
        CRUD::column('child.full_name')->label('Child');
        CRUD::column('dropped_off_at')->type('datetime')->label('Dropped off');
        CRUD::column('dropped_off_by_name')->label('Dropped off by');
        CRUD::column('picked_up_at')->type('datetime')->label('Picked up');
        CRUD::column('picked_up_by_name')->label('Picked up by');
        CRUD::addColumn([
            'name' => 'status',
            'label' => 'Status',
            'type' => 'text',
        ]);

        CRUD::setOperationSetting('exportButtons', true);
        CRUD::setOperationSetting('showExportButton', true);

        $this->crud->removeButton('preview');
        $this->crud->removeButton('show');
        $this->crud->removeButton('create');
        $this->crud->removeButton('update');
        $this->crud->removeButton('delete');
    }

    private function selectedMonth(): ?array
    {
        $month = request()->query('month');
        if (!is_string($month) || !preg_match('/^(\d{4})-(0[1-9]|1[0-2])$/', $month, $matches)) {
            return null;
        }

        $year = (int) $matches[1];
        if ($year < 1) {
            return null;
        }

        return [$year, (int) $matches[2]];
    }

    protected function setupShowOperation(): void
    {
        CRUD::column('attendance_date')->type('date')->label('Date');
        CRUD::column('child.student_id')->label('Student ID');
        CRUD::column('child.full_name')->label('Child');
        CRUD::column('dropped_off_at')->type('datetime')->label('Dropped off');
        CRUD::column('dropped_off_by_name')->label('Dropped off by');
        CRUD::column('droppedOffRecordedBy.name')->label('Drop-off recorded by');
        CRUD::column('picked_up_at')->type('datetime')->label('Picked up');
        CRUD::column('picked_up_by_name')->label('Picked up by');
        CRUD::column('pickedUpRecordedBy.name')->label('Pickup recorded by');
        CRUD::column('status')->label('Status');

        $this->crud->removeButton('update');
        $this->crud->removeButton('delete');
    }
}
