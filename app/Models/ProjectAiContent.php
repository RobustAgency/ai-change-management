<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProjectAiContent extends Model
{
    /** @use HasFactory<\Database\Factories\ProjectAiContentFactory> */
    use HasFactory;

    protected $fillable = [
        'project_id',
        'key_messages',
        'audience_variations',
    ];

    protected $casts = [
        'key_messages' => 'array',
        'audience_variations' => 'array',
    ];

    /**
     * Get the project that owns the content
     *
     * @return BelongsTo<Project, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
