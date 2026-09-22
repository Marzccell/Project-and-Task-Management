<?php

namespace App\Http\Controllers;

use App\Models\TaskAttachment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TaskAttachmentController extends Controller
{
    private function ownedAttachment(Request $request, string $attachment): TaskAttachment
    {
        return TaskAttachment::query()
            ->whereHas('task', fn ($query) => $query->where('user_id', $request->user()->id))
            ->findOrFail($attachment);
    }

    public function download(Request $request, string $attachment): StreamedResponse
    {
        $file = $this->ownedAttachment($request, $attachment);
        abort_unless(Storage::disk('local')->exists($file->path), 404);
        return Storage::disk('local')->download($file->path, $file->original_name);
    }

    public function destroy(Request $request, string $attachment): RedirectResponse
    {
        $file = $this->ownedAttachment($request, $attachment);
        $path = $file->path;

        DB::transaction(fn () => $file->delete());
        Storage::disk('local')->delete($path);

        return back()->with('success', 'Lampiran berhasil dihapus.');
    }
}
