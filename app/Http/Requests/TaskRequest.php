<?php
namespace App\Http\Requests;
use App\Models\Task;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class TaskRequest extends FormRequest
{
    public function authorize(): bool { return $this->user() !== null; }
    public function rules(): array {
        return [
            'project_id' => [
                'required',
                'integer',
                Rule::exists('projects', 'id')->where(fn ($query) => $query->where('user_id', $this->user()->id)),
            ],
            'title' => ['required', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:2000'],
            'status' => ['required', Rule::in(array_keys(Task::STATUSES))],
            'priority' => ['required', Rule::in(array_keys(Task::PRIORITIES))],
            'category' => ['required', Rule::in(array_keys(Task::CATEGORIES))],
            'due_date' => ['nullable', 'date_format:Y-m-d'],
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => [
                'file',
                'max:10240',
                'mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,txt,csv,jpg,jpeg,png,webp,zip',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'attachments.max' => 'Satu tugas maksimal memiliki 5 lampiran.',
            'attachments.*.max' => 'Ukuran setiap lampiran maksimal 10 MB.',
            'attachments.*.mimes' => 'Format lampiran tidak didukung.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $incomingFiles = count($this->file('attachments', []));

            if ($incomingFiles === 0) {
                return;
            }

            $existingFiles = 0;
            $taskId = $this->route('task');

            if ($taskId) {
                $existingFiles = (int) ($this->user()
                    ?->tasks()
                    ->whereKey($taskId)
                    ->withCount('attachments')
                    ->value('attachments_count') ?? 0);
            }

            if ($existingFiles + $incomingFiles > 5) {
                $validator->errors()->add('attachments', 'Satu tugas maksimal memiliki 5 lampiran.');
            }
        });
    }
}
