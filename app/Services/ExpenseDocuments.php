<?php

namespace App\Services;

use App\Models\Expense;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class ExpenseDocuments
{
    public function bankLetter(Expense $expense): string
    {
        abort_unless($expense->approved(), 403);
        $academy = $expense->approval_snapshot;
        foreach (['logo', 'signature'] as $asset) {
            $path = $academy[$asset.'_path'] ?? null;
            abort_unless($path && Storage::disk('local')->exists($path), 422, 'The approved '.$asset.' image is missing.');
            $contents = Storage::disk('local')->get($path);
            $mime = (new \finfo(FILEINFO_MIME_TYPE))->buffer($contents);
            $academy[$asset] = 'data:'.$mime.';base64,'.base64_encode($contents);
        }

        return Pdf::loadView('expenses.bank', compact('expense', 'academy'))
            ->setPaper('a4')->setOption('isRemoteEnabled', false)->output();
    }
}
