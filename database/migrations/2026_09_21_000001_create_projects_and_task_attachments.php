<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name', 120);
            $table->text('description')->nullable();
            $table->date('deadline')->nullable();
            $table->string('status', 20)->default('active');
            $table->timestamps();
            $table->index(['user_id', 'status']);
            $table->index(['user_id', 'deadline']);
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->foreignId('project_id')->nullable()->after('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('position')->default(0)->after('status');
            $table->index(['project_id', 'status', 'position']);
        });

        $now = now();
        foreach (DB::table('users')->pluck('id') as $userId) {
            $projectId = DB::table('projects')->insertGetId([
                'user_id' => $userId,
                'name' => 'General Tasks',
                'description' => 'Tasks migrated from the original personal to-do list.',
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            DB::table('tasks')->where('user_id', $userId)->update(['project_id' => $projectId]);
        }

        Schema::create('task_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained()->cascadeOnDelete();
            $table->string('original_name');
            $table->string('path');
            $table->string('mime_type', 120)->nullable();
            $table->unsignedBigInteger('size');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_attachments');
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropConstrainedForeignId('project_id');
            $table->dropColumn('position');
        });
        Schema::dropIfExists('projects');
    }
};
