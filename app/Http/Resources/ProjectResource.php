<?php

namespace App\Http\Resources;

use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Project
 */
class ProjectResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'launch_date' => $this->launch_date?->toIso8601String(),
            'type' => $this->type,
            'sponsor_name' => $this->sponsor_name,
            'sponsor_title' => $this->sponsor_title,
            'business_goals' => $this->business_goals,
            'summary' => $this->summary,
            'expected_outcomes' => $this->expected_outcomes,
            'stakeholders' => $this->stakeholders,
            'client_organization' => $this->client_organization,
            'status' => $this->status,
            'client_logo_url' => $this->client_logo_url,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
