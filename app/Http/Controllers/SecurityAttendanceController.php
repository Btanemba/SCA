<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Person;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class SecurityAttendanceController extends Controller
{
    public function index()
    {
        $this->ensureSecurityUser();

        return view('security.attendance');
    }

    public function search(Request $request): JsonResponse
    {
        $this->ensureSecurityUser();

        $term = trim((string) $request->query('term', ''));
        if (mb_strlen($term) < 2) {
            return response()->json([]);
        }

        $today = now()->toDateString();
        $children = Person::query()
            ->whereHas('sacRole', fn ($query) => $query->where('code', Person::ROLE_STUDENT))
            ->where(function ($query) use ($term) {
                $query->where('student_id', 'like', "%{$term}%")
                    ->orWhere('first_name', 'like', "%{$term}%")
                    ->orWhere('last_name', 'like', "%{$term}%");
            })
            ->with([
                'pickupContactLinks.contact',
                'attendances' => fn ($query) => $query->whereDate('attendance_date', $today),
            ])
            ->orderBy('student_id')
            ->limit(15)
            ->get();

        return response()->json($children->map(function (Person $child) {
            $attendance = $child->attendances->first();

            return [
                'id' => $child->getKey(),
                'student_id' => $child->student_id,
                'name' => $child->full_name,
                'age' => $child->date_of_birth?->age,
                'image_url' => $child->image_path ? Storage::disk('public')->url($child->image_path) : null,
                'attendance' => $attendance ? [
                    'id' => $attendance->getKey(),
                    'dropped_off_at' => $attendance->dropped_off_at?->format('F j, Y g:i A'),
                    'dropped_off_by_name' => $attendance->dropped_off_by_name,
                    'picked_up_at' => $attendance->picked_up_at?->format('F j, Y g:i A'),
                    'picked_up_by_name' => $attendance->picked_up_by_name,
                    'is_present' => $attendance->is_present,
                ] : null,
                'contacts' => $child->pickupContactLinks
                    ->filter(fn ($link) => $link->contact)
                    ->map(fn ($link) => [
                        'id' => $link->contact->getKey(),
                        'name' => $link->contact->full_name,
                        'image_url' => $link->contact->image_path ? Storage::disk('public')->url($link->contact->image_path) : null,
                        'relationship' => $link->relationship,
                        'can_drop_off' => $link->can_drop_off,
                        'can_pick_up' => $link->can_pick_up,
                    ])
                    ->values(),
            ];
        }));
    }

    public function dropOff(Request $request): JsonResponse
    {
        $this->ensureSecurityUser();
        $data = $request->validate([
            'child_id' => ['required', 'integer'],
            'pickup_contact_id' => ['required', 'integer'],
        ]);

        $attendance = DB::transaction(function () use ($data) {
            $child = $this->student($data['child_id']);
            $link = $child->pickupContactLinks()
                ->with('contact')
                ->where('pickup_contact_id', $data['pickup_contact_id'])
                ->where('can_drop_off', true)
                ->first();

            if (! $link?->contact) {
                $this->invalidSelection('pickup_contact_id', 'This person is not authorized to drop off this child.');
            }

            $today = now()->toDateString();
            $existing = Attendance::query()
                ->where('child_id', $child->getKey())
                ->whereDate('attendance_date', $today)
                ->lockForUpdate()
                ->first();

            if ($existing) {
                $this->invalidSelection('child_id', 'This child already has an attendance record for today.');
            }

            return Attendance::create([
                'child_id' => $child->getKey(),
                'attendance_date' => $today,
                'dropped_off_at' => now(),
                'dropped_off_by_contact_id' => $link->contact->getKey(),
                'dropped_off_by_name' => $link->contact->full_name,
                'dropped_off_recorded_by' => backpack_user()->getKey(),
            ]);
        });

        return response()->json([
            'message' => "{$attendance->child->full_name} was marked present.",
        ], 201);
    }

    public function pickUp(Request $request): JsonResponse
    {
        $this->ensureSecurityUser();
        $data = $request->validate([
            'child_id' => ['required', 'integer'],
            'pickup_contact_id' => ['required', 'integer'],
        ]);

        $attendance = DB::transaction(function () use ($data) {
            $child = $this->student($data['child_id']);
            $link = $child->pickupContactLinks()
                ->with('contact')
                ->where('pickup_contact_id', $data['pickup_contact_id'])
                ->where('can_pick_up', true)
                ->first();

            if (! $link?->contact) {
                $this->invalidSelection('pickup_contact_id', 'This person is not authorized to pick up this child.');
            }

            $attendance = Attendance::query()
                ->where('child_id', $child->getKey())
                ->whereDate('attendance_date', now()->toDateString())
                ->lockForUpdate()
                ->first();

            if (! $attendance) {
                $this->invalidSelection('child_id', 'This child has not been dropped off today.');
            }

            if ($attendance->picked_up_at) {
                $this->invalidSelection('child_id', 'This child has already been picked up today.');
            }

            $attendance->update([
                'picked_up_at' => now(),
                'picked_up_by_contact_id' => $link->contact->getKey(),
                'picked_up_by_name' => $link->contact->full_name,
                'picked_up_recorded_by' => backpack_user()->getKey(),
            ]);

            return $attendance;
        });

        return response()->json([
            'message' => "{$attendance->child->full_name} was marked picked up.",
        ]);
    }

    protected function student(int $id): Person
    {
        return Person::query()
            ->whereKey($id)
            ->whereHas('sacRole', fn ($query) => $query->where('code', Person::ROLE_STUDENT))
            ->firstOrFail();
    }

    protected function ensureSecurityUser(): void
    {
        abort_unless(
            backpack_user()?->person?->sacRole?->code === Person::ROLE_SECURITY,
            403
        );
    }

    protected function invalidSelection(string $field, string $message): never
    {
        throw ValidationException::withMessages([$field => $message]);
    }
}
