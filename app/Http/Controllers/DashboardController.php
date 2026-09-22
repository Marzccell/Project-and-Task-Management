<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $taskQuery = $user->tasks();
        $stats = [
            'projects' => $user->projects()->count(),
            'tasks' => (clone $taskQuery)->count(),
            'in_progress' => (clone $taskQuery)->where('status', 'in_progress')->count(),
            'done' => (clone $taskQuery)->where('status', 'done')->count(),
            'overdue' => (clone $taskQuery)->where('status', '!=', 'done')->whereDate('due_date', '<', today())->count(),
        ];
        $stats['progress'] = $stats['tasks'] ? (int) round($stats['done'] / $stats['tasks'] * 100) : 0;

        $projects = $user->projects()
            ->withCount(['tasks', 'tasks as completed_tasks_count' => fn ($query) => $query->where('status', 'done')])
            ->orderByRaw("CASE status WHEN 'active' THEN 1 WHEN 'planning' THEN 2 WHEN 'on_hold' THEN 3 ELSE 4 END")
            ->orderByRaw('deadline IS NULL')->orderBy('deadline')->take(6)->get();

        $upcomingTasks = $user->tasks()->with('project')->where('status', '!=', 'done')
            ->orderByRaw('due_date IS NULL')->orderBy('due_date')->take(6)->get();

        return view('dashboard', compact('stats', 'projects', 'upcomingTasks'));
    }
}
