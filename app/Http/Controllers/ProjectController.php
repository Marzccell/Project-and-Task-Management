<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProjectRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProjectController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', 'in:planning,active,on_hold,completed'],
        ]);
        $query = $request->user()->projects()->withCount([
            'tasks', 'tasks as completed_tasks_count' => fn ($q) => $q->where('status', 'done'),
        ]);
        if (! empty($filters['q'])) {
            $term = $filters['q'];
            $query->where(fn ($q) => $q->where('name', 'like', "%{$term}%")->orWhere('description', 'like', "%{$term}%"));
        }
        if (! empty($filters['status'])) $query->where('status', $filters['status']);
        $projects = $query->orderByDesc('updated_at')->paginate(9)->withQueryString();
        return view('projects.index', compact('projects', 'filters'));
    }

    public function show(Request $request, string $project): RedirectResponse
    {
        $record = $request->user()->projects()->findOrFail($project);
        return redirect()->route('tasks.index', ['project_id' => $record->id]);
    }

    public function store(ProjectRequest $request): RedirectResponse
    {
        $request->user()->projects()->create($request->validated());
        return back()->with('success', 'Proyek berhasil dibuat.');
    }

    public function update(ProjectRequest $request, string $project): RedirectResponse
    {
        $request->user()->projects()->findOrFail($project)->update($request->validated());
        return back()->with('success', 'Proyek berhasil diperbarui.');
    }

    public function destroy(Request $request, string $project): RedirectResponse
    {
        $record = $request->user()->projects()->with('tasks.attachments')->findOrFail($project);
        $paths = $record->tasks
            ->flatMap(fn ($task) => $task->attachments->pluck('path'))
            ->all();

        DB::transaction(fn () => $record->delete());
        Storage::disk('local')->delete($paths);

        return redirect()->route('projects.index')->with('success', 'Proyek dan seluruh tugasnya berhasil dihapus.');
    }
}
