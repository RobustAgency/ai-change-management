<?php

namespace App\Services\Project;

use App\Models\Project;

class ProjectPromptBuilder
{
    public function build(Project $project): string
    {
        \info('Building prompt for project ID: '.$project->id);
        $template = config('prompts.project_generation');

        $stakeholders = $this->formatStakeholders($project->stakeholders);
        \info('Formatted stakeholders for project ID: '.$project->id);
        $replacements = [
            ':title' => $project->name ?? '',
            ':launch_date' => $project->launch_date?->toDateTimeString() ?? '',
            ':type' => $project->type ?? '',
            ':sponsor_name' => $project->sponsor_name ?? '',
            ':sponsor_title' => $project->sponsor_title ?? '',
            ':business_goals' => $project->business_goals ?? '',
            ':summary' => $project->summary ?? '',
            ':expected_outcomes' => $project->expected_outcomes ?? '',
            ':stakeholders' => $stakeholders,
            ':client_organization' => $project->client_organization,
        ];

        return strtr($template, $replacements);
    }

    /**
     * Format stakeholders array into readable bullet lines.
     *
     * @param  mixed  $stakeholders
     */
    private function formatStakeholders($stakeholders): string
    {
        if (empty($stakeholders)) {
            return '';
        }

        if (is_string($stakeholders)) {
            return $stakeholders;
        }

        $lines = [];
        foreach ($stakeholders as $stakeholder) {
            $name = $stakeholder['name'] ?? null;
            $dept = $stakeholder['department'] ?? ($stakeholder['dept'] ?? null);
            $role = $stakeholder['role_level'] ?? $stakeholder['role'] ?? null;

            $parts = array_filter([$name, $dept, $role]);
            $lines[] = implode(' - ', $parts);
        }

        return implode("\n- ", $lines);
    }
}
