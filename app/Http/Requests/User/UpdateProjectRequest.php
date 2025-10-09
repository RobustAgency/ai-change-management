<?php

namespace App\Http\Requests\User;

use App\Models\Project;
use App\Enums\ProjectStatus;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateProjectRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        /** @var Project $project */
        $project = $this->route('project');

        if ($project->aiContent()->exists()) {
            return false;
        }

        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'launch_date' => ['required', 'date'],
            'type' => ['nullable', 'string', 'max:100'],
            'sponsor_name' => ['nullable', 'string', 'max:255'],
            'sponsor_title' => ['nullable', 'string', 'max:255'],
            'business_goals' => ['nullable', 'string'],
            'summary' => ['nullable', 'string'],
            'expected_outcomes' => ['nullable', 'string'],
            'stakeholders' => ['nullable', 'array'],
            'stakeholders.*.department' => ['sometimes', 'nullable', 'string', 'max:255'],
            'stakeholders.*.role_level' => ['sometimes', 'nullable', 'string', 'max:255'],
            'client_organization' => ['nullable', 'string'],
            'client_logo' => ['nullable', 'file', 'mimes:png,jpg,jpeg,webp', 'max:5120'],
            'status' => ['nullable', Rule::in(array_map(fn ($c) => $c->value, ProjectStatus::cases()))],
        ];
    }

    /**
     * Handle failed authorization.
     */
    protected function failedAuthorization()
    {
        abort(403, 'Projects with AI-generated content cannot be updated.');
    }
}
