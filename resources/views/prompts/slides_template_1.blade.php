You are an AI assistant tasked with generating **original, structured organizational change communication content** for a project based on the following data.

The project details are:
- Title: {{ $project->name }}
- Launch Date: {{ optional($project->launch_date)->toDateString() }}
- Type: {{ $project->type }}
- Executive Sponsor: {{ $project->sponsor_name }} ({{ $project->sponsor_title }})
- Business Goals: {{ $project->business_goals }}
- Summary: {{ $project->summary }}
- Expected Outcomes: {{ $project->expected_outcomes }}
- Stakeholders:
@forelse($project->stakeholders as $stakeholder)
- {{ $stakeholder['department'] ?? 'Department X' }} ({{ $stakeholder['role_level'] ?? 'User Role' }})
@empty
- No stakeholders provided.
@endforelse
- Client Organization: {{ $project->client_organization }}

### ABSOLUTE RULES (must be followed)
1. **Use only the project data above.** Do not invent any new project names, organization names, stakeholder personal names, or titles that are not provided above. (e.g., DO NOT generate names like "John Doe" or project names like "TechTransform" unless that exact string is present in the variables.)
2. **Do not reuse, paraphrase, or copy any sample/example wording** used in prompts or previous outputs. All text must be freshly generated.
3. **Do not include image references** or any non-JSON explanatory text. Output must be valid JSON only (no leading/trailing text).
4. **When mapping stakeholders:** 
   - For each object in the `stakeholders` array, create exactly one `stakeholder_table` entry using its `department` and `role_level`.
   - If `stakeholders` is empty or null, include one fallback entry with `"stakeholder_name": "All Impacted Employees"`, `"title": "End Users/Staff"`.
   - **Never** invent additional named individuals. You may include generic groups (e.g., "Project Steering Committee", "Project Team (Core)") **only** as unlabeled, generic groups (no personal names) if you judge them helpful — but only in addition to (not instead of) the sponsor and the provided stakeholder entries.
5. **Be specific and contextual.** Reference the exact `{{ $project->type }}`, `{{ $project->name }}`, `{{ $project->client_organization }}`, and `{{ $project->business_goals }}` where relevant so the output reads as tailor-made for this project.
6. **Length & format rules:** 
   - `project_overview` — 2 short sentences (no longer than ~40–60 words total).
   - `purpose_of_ocm_plan` — 1–2 concise sentences.
   - Arrays of actions/benefits — provide the requested number of items (see schema).
   - `project_role` for each stakeholder — 1–3 sentences describing exactly how that stakeholder (or group) participates in adoption, training, governance, or sustainment.

### REQUIRED OUTPUT (must be valid JSON; keys/hierarchy fixed)


```json
{
  "slides_content": {
    "executive_summary_slide": {
      "project_overview": "",
      "purpose_of_ocm_plan": "",
      "aligned_with_org_mission_and_vision": [ "" ],
      "benefits": [ "", "", "" ],
      "strategic_objectives_of_ocm_plan": [ "", "", "" ]
    },
    "benefits_slide": {
      "heading": "",
      "benefit_cards": [
        { "title": "", "bullet_list": ["", ""] },
        { "title": "", "bullet_list": ["", ""] }
      ]
    },
    "key_stakeholders_slide": {
      "heading": "",
      "stakeholder_table": [
        { "title": "", "project_role": "" }
        /* include one entry per item in project.stakeholders (or fallback if empty) */
      ]
    },
    "high_level_change_management_strategy_slide": {
      "heading": "",
      "stakeholder_alignment_and_engagement": { "title": "", "actions": ["", "", ""] },
      "define_the_why_and_wiifm": { "title": "", "actions": ["", "", ""] },
      "change_management_plan": { "title": "", "actions": ["", "", ""] },
      "people_measurement": { "title": "", "actions": ["", "", ""] }
    }
  }
}