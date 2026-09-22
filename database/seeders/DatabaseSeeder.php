<?php
namespace Database\Seeders;
use App\Models\User;
use Illuminate\Database\Seeder;
class DatabaseSeeder extends Seeder
{
    public function run(): void {
        $user = User::firstOrCreate(['email' => 'demo@campusflow.test'], ['name' => 'Marcell', 'password' => 'CampusFlow123!']);
        $project = $user->projects()->oldest()->first();
        if (! $project) {
            $project = $user->projects()->create([
                'name' => 'Campus Organization Platform',
                'description' => 'Website and operational preparation for campus organization recruitment.',
                'deadline' => today()->addDays(14),
                'status' => 'active',
            ]);
        }
        if ($user->tasks()->exists()) return;
        $samples = [
            ['Polish the BEM website landing page', 'Refine the hero, check mobile spacing, and make the registration CTA easy to find.', 'in_progress', 'high', 'development', 0],
            ['Prepare for the Information System interview', 'Practice explaining MVC, validation, and how task ownership is enforced.', 'todo', 'high', 'organization', 1],
            ['Review the event registration flow', 'Walk through the full participant journey and write down any confusing steps.', 'todo', 'medium', 'organization', 0],
            ['Finish the data structures assignment', 'Implement linked-list operations and check the empty-list edge case.', 'in_progress', 'high', 'academic', 2],
            ['Write this week’s learning notes', 'Capture one thing learned, one thing shipped, and one thing to improve.', 'todo', 'low', 'personal', 3],
            ['Audit mobile navigation', 'Check touch targets, focus states, and menu behavior on small screens.', 'todo', 'medium', 'development', -1],
            ['Set up the project repository', 'Initialize Git and document the local setup steps.', 'done', 'medium', 'development', -2],
            ['Plan the committee kickoff agenda', 'Prepare discussion points and a short action-item list.', 'done', 'low', 'organization', -1],
            ['Read Laravel validation documentation', 'Learn Form Requests and how errors are returned to Blade views.', 'done', 'medium', 'academic', 0],
        ];
        foreach ($samples as [$title, $description, $status, $priority, $category, $offset]) {
            $user->tasks()->create(compact('title', 'description', 'status', 'priority', 'category') + [
                'project_id' => $project->id,
                'due_date' => today()->addDays($offset), 'completed_at' => $status === 'done' ? now() : null,
            ]);
        }
    }
}
