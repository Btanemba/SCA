<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Person;
use App\Models\StaffDocument;
use App\Notifications\StaffDocumentPublished;
use App\Services\StaffDocumentPdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class StaffDocumentController extends Controller
{
    private function authorizeFounder(): void
    {
        abort_unless(backpack_user()?->person?->sacRole?->code === Person::ROLE_FOUNDER, 403);
    }

    private function people()
    {
        return Person::with('sacRole', 'user')
            ->whereNotNull('user_id')
            ->whereHas('sacRole', fn ($query) => $query->whereNotIn('code', [Person::ROLE_STUDENT, Person::ROLE_FOUNDER]))
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();
    }

    public function index()
    {
        $this->authorizeFounder();
        $documents = StaffDocument::with(['person.sacRole', 'creator.person', 'updater.person'])->latest()->paginate(20);

        return view('staff-documents.index', compact('documents'));
    }

    public function create()
    {
        $this->authorizeFounder();
        $people = $this->people();
        $document = new StaffDocument;

        return view('staff-documents.create', compact('people', 'document'));
    }

    public function store(Request $request)
    {
        $this->authorizeFounder();
        $data = $this->validatedData($request);
        $person = $this->recipient($data['person_id']);

        StaffDocument::create([
            'person_id' => $person->id,
            'created_by' => backpack_user()->id,
            'title' => $data['title'],
            'document_type' => $data['document_type'],
            'body' => $data['body'],
            'requires_staff_signature' => $request->boolean('requires_staff_signature'),
        ]);

        return redirect()->route('staff-documents.index')->with('success', 'Document saved as a draft.');
    }

    public function edit(StaffDocument $document)
    {
        $this->authorizeFounder();
        abort_if((bool) $document->published_at, 409, 'Published documents cannot be edited.');
        $people = $this->people();

        return view('staff-documents.create', compact('people', 'document'));
    }

    public function update(Request $request, StaffDocument $document)
    {
        $this->authorizeFounder();
        abort_if((bool) $document->published_at, 409, 'Published documents cannot be edited.');
        $data = $this->validatedData($request);
        $person = $this->recipient($data['person_id']);

        $document->update([
            'person_id' => $person->id,
            'title' => $data['title'],
            'document_type' => $data['document_type'],
            'body' => $data['body'],
            'requires_staff_signature' => $request->boolean('requires_staff_signature'),
        ]);

        return redirect()->route('staff-documents.index')->with('success', 'Draft updated.');
    }

    public function preview(StaffDocument $document, StaffDocumentPdf $pdf)
    {
        $this->authorizeFounder();

        return $this->pdfResponse($document, $pdf, true);
    }

    public function publish(StaffDocument $document, StaffDocumentPdf $pdf)
    {
        $this->authorizeFounder();
        abort_if((bool) $document->published_at, 409, 'This document has already been published.');

        $document->loadMissing('person.user');
        abort_unless($document->person?->user, 422, 'The recipient no longer has a linked account.');
        $document->forceFill([
            'academy_snapshot' => $pdf->snapshot(),
            'published_at' => now(),
        ])->save();
        Notification::send($document->person->user, new StaffDocumentPublished($document));

        return redirect()->route('staff-documents.index')->with('success', 'Document published and the recipient was notified.');
    }

    public function mine()
    {
        $person = backpack_user()?->person;
        abort_unless($person && $person->sacRole && $person->sacRole->code !== Person::ROLE_STUDENT, 403);

        $documents = StaffDocument::where('person_id', $person->id)
            ->whereNotNull('published_at')
            ->latest('published_at')
            ->paginate(20);

        return view('staff-documents.mine', compact('documents'));
    }

    public function download(StaffDocument $document, StaffDocumentPdf $pdf)
    {
        $person = backpack_user()?->person;
        $isFounder = $person?->sacRole?->code === Person::ROLE_FOUNDER;
        $isRecipient = (bool) ($person && $person->sacRole && $person->sacRole->code !== Person::ROLE_STUDENT
            && $document->person_id === $person->id && $document->published_at);
        abort_unless($isFounder || $isRecipient, 403);
        abort_unless((bool) $document->published_at || $isFounder, 404);

        return $this->pdfResponse($document, $pdf, false);
    }

    private function validatedData(Request $request): array
    {
        return $request->validate([
            'person_id' => ['required', 'integer', 'exists:persons,id'],
            'title' => ['required', 'string', 'max:255'],
            'document_type' => ['required', Rule::in(['contract', 'employment', 'query', 'other'])],
            'body' => ['required', 'string', 'max:50000'],
            'requires_staff_signature' => ['sometimes', 'boolean'],
        ]);
    }

    private function recipient(int $personId): Person
    {
        $person = Person::with('sacRole', 'user')->findOrFail($personId);
        if (! $person->user || ! $person->sacRole || in_array($person->sacRole->code, [Person::ROLE_STUDENT, Person::ROLE_FOUNDER], true)) {
            throw ValidationException::withMessages([
                'person_id' => 'Choose an account with a linked login that is not a Student or Founder account.',
            ]);
        }

        return $person;
    }

    private function pdfResponse(StaffDocument $document, StaffDocumentPdf $pdf, bool $inline)
    {
        $filename = str($document->title)->slug('-')->limit(100, '')->append('.pdf');

        return response($pdf->render($document), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => ($inline ? 'inline' : 'attachment').'; filename="'.$filename.'"',
            'Cache-Control' => 'no-store, private',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
