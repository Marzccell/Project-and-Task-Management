<?php

namespace App\Http\Controllers;

use App\Http\Requests\TaskRequest;
use App\Models\Task;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class TaskController extends Controller
{
    public function index(Request $request): View
    {
        [$filters, $query] = $this->filteredQuery($request);
        $view = $filters['view'] ?? 'list';
        $tasks = $query->with(['project', 'attachments'])
            ->orderByRaw("CASE WHEN status = 'done' THEN 1 ELSE 0 END")
            ->orderByRaw('due_date IS NULL')->orderBy('due_date')->orderBy('position')->orderByDesc('id')
            ->paginate($view === 'board' ? 60 : 12)->withQueryString();

        $base = $request->user()->tasks();
        $stats = [
            'total' => (clone $base)->count(),
            'todo' => (clone $base)->where('status', 'todo')->count(),
            'progress' => (clone $base)->where('status', 'in_progress')->count(),
            'done' => (clone $base)->where('status', 'done')->count(),
            'overdue' => (clone $base)->where('status', '!=', 'done')->whereDate('due_date', '<', today())->count(),
        ];
        $projects = $request->user()->projects()->orderBy('name')->get();

        return view('tasks.index', compact('tasks', 'stats', 'filters', 'view', 'projects'));
    }

    public function store(TaskRequest $request): RedirectResponse
    {
        $data = Arr::except($request->validated(), ['attachments']);
        $data['completed_at'] = $data['status'] === 'done' ? now() : null;
        $storedPaths = [];

        try {
            DB::transaction(function () use ($request, $data, &$storedPaths): void {
                $task = $request->user()->tasks()->create($data);
                $this->storeAttachments($request, $task, $storedPaths);
            });
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($storedPaths);
            throw $exception;
        }

        return back()->with('success', 'Tugas berhasil dibuat.');
    }

    public function update(TaskRequest $request, string $task): RedirectResponse
    {
        $record = $request->user()->tasks()->findOrFail($task);
        $data = Arr::except($request->validated(), ['attachments']);
        $data['completed_at'] = $data['status'] === 'done' ? ($record->completed_at ?? now()) : null;
        $storedPaths = [];

        try {
            DB::transaction(function () use ($request, $record, $data, &$storedPaths): void {
                $record->update($data);
                $this->storeAttachments($request, $record, $storedPaths);
            });
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($storedPaths);
            throw $exception;
        }

        return back()->with('success', 'Tugas berhasil diperbarui.');
    }

    public function status(Request $request, string $task): RedirectResponse|JsonResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(array_keys(Task::STATUSES))],
            'position' => ['nullable', 'integer', 'min:0'],
        ]);
        $record = $request->user()->tasks()->findOrFail($task);
        $data['completed_at'] = $data['status'] === 'done' ? ($record->completed_at ?? now()) : null;
        $record->update($data);

        if ($request->expectsJson()) return response()->json(['message' => 'Status tugas diperbarui.', 'task' => $record]);
        return back()->with('success', 'Status tugas diperbarui.');
    }

    public function destroy(Request $request, string $task): RedirectResponse
    {
        $record = $request->user()->tasks()->with('attachments')->findOrFail($task);
        $paths = $record->attachments->pluck('path')->all();

        DB::transaction(fn () => $record->delete());
        Storage::disk('local')->delete($paths);

        return redirect()->route('tasks.index')->with('success', 'Tugas berhasil dihapus.');
    }

    public function export(Request $request): StreamedResponse
    {
        [, $query] = $this->filteredQuery($request);
        $tasks = $query->with('project')->orderBy('due_date')->get();
        $filename = 'campusflow-tasks-'.now()->format('Ymd-His').'.xls';
        return response()->streamDownload(function () use ($tasks) {
            echo view('tasks.export', compact('tasks'))->render();
        }, $filename, ['Content-Type' => 'application/vnd.ms-excel; charset=UTF-8']);
    }

    private function filteredQuery(Request $request): array
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:160'],
            'project_id' => ['nullable', 'integer'],
            'status' => ['nullable', Rule::in(array_keys(Task::STATUSES))],
            'deadline' => ['nullable', Rule::in(['overdue', 'today', 'week', 'no_deadline'])],
            'deadline_from' => ['nullable', 'date_format:Y-m-d'],
            'deadline_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:deadline_from'],
            'view' => ['nullable', Rule::in(['list', 'board'])],
        ]);

        $query = $request->user()->tasks();
        if (! empty($filters['q'])) {
            $term = $filters['q'];
            $query->where(fn (Builder $q) => $q->where('title', 'like', "%{$term}%")
                ->orWhere('description', 'like', "%{$term}%"));
        }
        if (! empty($filters['project_id'])) {
            $project = $request->user()->projects()->findOrFail($filters['project_id']);
            $query->where('project_id', $project->id);
        }
        if (! empty($filters['status'])) $query->where('status', $filters['status']);
        if (! empty($filters['deadline_from'])) $query->whereDate('due_date', '>=', $filters['deadline_from']);
        if (! empty($filters['deadline_to'])) $query->whereDate('due_date', '<=', $filters['deadline_to']);
        match ($filters['deadline'] ?? null) {
            'overdue' => $query->whereDate('due_date', '<', today())->where('status', '!=', 'done'),
            'today' => $query->whereDate('due_date', today()),
            'week' => $query->whereBetween('due_date', [today(), today()->addDays(7)]),
            'no_deadline' => $query->whereNull('due_date'),
            default => null,
        };
        return [$filters, $query];
    }

    private function storeAttachments(TaskRequest $request, Task $task, array &$storedPaths): void
    {
        foreach ($request->file('attachments', []) as $file) {
            $path = $file->store("attachments/{$request->user()->id}/{$task->id}", 'local');
            $storedPaths[] = $path;
            $task->attachments()->create([
                'original_name' => $file->getClientOriginalName(),
                'path' => $path,
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
            ]);
        }
    }
}
