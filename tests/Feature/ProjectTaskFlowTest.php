<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProjectTaskFlowTest extends TestCase
{
    use RefreshDatabase;

    private function project(User $user, array $overrides = []): Project
    {
        return $user->projects()->create(array_merge([
            'name' => 'Open Recruitment Website',
            'description' => 'Build the recruitment platform.',
            'deadline' => '2026-10-15',
            'status' => 'active',
        ], $overrides));
    }

    private function payload(Project $project, array $overrides = []): array
    {
        return array_merge([
            'project_id' => $project->id,
            'title' => 'Polish the landing page',
            'description' => 'Check the responsive layout.',
            'status' => 'todo',
            'priority' => 'medium',
            'category' => 'personal',
            'due_date' => '2026-10-10',
        ], $overrides);
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
        $this->get('/projects')->assertRedirect('/login');
        $this->get('/tasks')->assertRedirect('/login');
    }

    public function test_registration_uses_secure_password_and_creates_initial_project(): void
    {
        $response = $this->post('/register', [
            'name' => 'Marcell', 'email' => 'MARCELL@EXAMPLE.TEST',
            'password' => 'Password123', 'password_confirmation' => 'Password123',
        ]);

        $response->assertRedirect('/dashboard');
        $user = User::firstOrFail();
        $this->assertAuthenticatedAs($user);
        $this->assertSame('marcell@example.test', $user->email);
        $this->assertTrue(Hash::check('Password123', $user->password));
        $this->assertDatabaseHas('projects', ['user_id' => $user->id, 'name' => 'My First Project']);
        $this->get('/dashboard')->assertOk()->assertSee('Marcell');
    }

    public function test_login_bug_is_fixed_and_redirected_dashboard_renders_with_tasks(): void
    {
        $user = User::factory()->create(['email' => 'demo@example.test', 'password' => 'Password123']);
        $project = $this->project($user);
        $user->tasks()->create($this->payload($project));

        $this->post('/login', ['email' => 'DEMO@EXAMPLE.TEST', 'password' => 'wrong'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();

        $this->post('/login', ['email' => 'DEMO@EXAMPLE.TEST', 'password' => 'Password123'])
            ->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
        $this->get('/dashboard')->assertOk()->assertSee('Polish the landing page');
        $this->get('/tasks')->assertOk()->assertSee('Polish the landing page');
    }

    public function test_user_can_create_update_and_delete_project(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post('/projects', [
            'name' => 'Mobile App', 'description' => 'MVP', 'deadline' => '2026-11-01', 'status' => 'planning',
        ])->assertRedirect();
        $project = $user->projects()->firstOrFail();
        $this->assertDatabaseHas('projects', ['id' => $project->id, 'status' => 'planning']);

        $this->put("/projects/{$project->id}", [
            'name' => 'Mobile App v2', 'description' => 'MVP updated', 'deadline' => '2026-11-02', 'status' => 'active',
        ])->assertRedirect();
        $this->assertSame('Mobile App v2', $project->fresh()->name);

        $this->delete("/projects/{$project->id}")->assertRedirect('/projects');
        $this->assertDatabaseMissing('projects', ['id' => $project->id]);
    }

    public function test_project_and_task_data_are_isolated_between_users(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $project = $this->project($owner, ['name' => 'Private Project']);
        $task = $owner->tasks()->create($this->payload($project, ['title' => 'Private Task']));

        $this->actingAs($other)->get('/projects')->assertOk()->assertDontSee('Private Project');
        $this->get('/tasks')->assertOk()->assertDontSee('Private Task');
        $this->put("/projects/{$project->id}", ['name' => 'Hack', 'status' => 'active'])->assertNotFound();
        $this->patch("/tasks/{$task->id}/status", ['status' => 'done'])->assertNotFound();
        $this->delete("/tasks/{$task->id}")->assertNotFound();
    }

    public function test_task_crud_requires_a_project_owned_by_user(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $project = $this->project($user);
        $foreignProject = $this->project($other);

        $this->actingAs($user)->post('/tasks', $this->payload($foreignProject))
            ->assertSessionHasErrors('project_id');
        $this->post('/tasks', $this->payload($project))->assertRedirect();
        $task = $user->tasks()->firstOrFail();

        $this->put("/tasks/{$task->id}", $this->payload($project, ['title' => 'Updated task', 'status' => 'done']))
            ->assertRedirect();
        $this->assertSame('Updated task', $task->fresh()->title);
        $this->assertNotNull($task->fresh()->completed_at);

        $this->delete("/tasks/{$task->id}")->assertRedirect('/tasks');
        $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
    }

    public function test_task_attachment_upload_download_limit_and_cleanup(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $project = $this->project($user);

        $payload = $this->payload($project, ['attachments' => [UploadedFile::fake()->create('brief.pdf', 250, 'application/pdf')]]);
        $this->actingAs($user)->post('/tasks', $payload)->assertRedirect();
        $task = $user->tasks()->with('attachments')->firstOrFail();
        $attachment = $task->attachments->firstOrFail();
        Storage::disk('local')->assertExists($attachment->path);
        $this->get("/attachments/{$attachment->id}")->assertOk();
        $this->get('/tasks')->assertOk()->assertSee('brief.pdf');

        $tooLarge = $this->payload($project, ['title' => 'Large file', 'attachments' => [UploadedFile::fake()->create('huge.pdf', 10241, 'application/pdf')]]);
        $this->post('/tasks', $tooLarge)->assertSessionHasErrors('attachments.0');

        $this->delete("/tasks/{$task->id}")->assertRedirect('/tasks');
        Storage::disk('local')->assertMissing($attachment->path);
    }

    public function test_task_cannot_accumulate_more_than_five_attachments(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $project = $this->project($user);
        $initialFiles = collect(range(1, 4))
            ->map(fn (int $number) => UploadedFile::fake()->create("file-{$number}.pdf", 20, 'application/pdf'))
            ->all();

        $this->actingAs($user)->post('/tasks', $this->payload($project, ['attachments' => $initialFiles]))
            ->assertRedirect();

        $task = $user->tasks()->firstOrFail();
        $this->put("/tasks/{$task->id}", $this->payload($project, [
            'attachments' => [
                UploadedFile::fake()->create('fifth.pdf', 20, 'application/pdf'),
                UploadedFile::fake()->create('sixth.pdf', 20, 'application/pdf'),
            ],
        ]))->assertSessionHasErrors('attachments');

        $this->assertCount(4, $task->attachments()->get());
    }

    public function test_search_status_and_deadline_filters_can_be_combined(): void
    {
        $this->travelTo(\Carbon\Carbon::parse('2026-09-21 10:00:00'));
        $user = User::factory()->create();
        $project = $this->project($user);
        $user->tasks()->create($this->payload($project, ['title' => 'Matching overdue task', 'due_date' => '2026-09-20']));
        $user->tasks()->create($this->payload($project, ['title' => 'Future unrelated task', 'due_date' => '2026-09-30']));

        $this->actingAs($user)->get("/tasks?q=Matching&project_id={$project->id}&status=todo&deadline=overdue")
            ->assertOk()->assertSee('Matching overdue task')->assertDontSee('Future unrelated task');
    }

    public function test_drag_drop_json_status_endpoint_updates_completion(): void
    {
        $user = User::factory()->create();
        $project = $this->project($user);
        $task = $user->tasks()->create($this->payload($project));

        $this->actingAs($user)->patchJson("/tasks/{$task->id}/status", ['status' => 'done'])
            ->assertOk()->assertJsonPath('task.status', 'done');
        $this->assertNotNull($task->fresh()->completed_at);
    }

    public function test_filtered_tasks_can_be_exported_for_excel(): void
    {
        $user = User::factory()->create();
        $project = $this->project($user);
        $user->tasks()->create($this->payload($project, ['title' => 'Export this task']));

        $response = $this->actingAs($user)->get('/tasks/export/excel?status=todo');
        $response->assertOk()->assertHeader('content-type', 'application/vnd.ms-excel; charset=UTF-8');
        $this->assertStringContainsString('Export this task', $response->streamedContent());
    }

    public function test_deleting_project_deletes_its_tasks_and_attachment_files(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $project = $this->project($user);
        $this->actingAs($user)->post('/tasks', $this->payload($project, [
            'attachments' => [UploadedFile::fake()->image('wireframe.png')],
        ]));
        $task = Task::with('attachments')->firstOrFail();
        $path = $task->attachments->first()->path;

        $this->delete("/projects/{$project->id}")->assertRedirect('/projects');
        $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
        Storage::disk('local')->assertMissing($path);
    }

    public function test_demo_seed_is_repeatable(): void
    {
        $this->seed();
        $this->seed();

        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('projects', 1);
        $this->assertDatabaseCount('tasks', 9);
    }
}
