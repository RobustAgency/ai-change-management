<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProjectContent extends Model
{
    /** @use HasFactory<\Database\Factories\ProjectContentFactory> */
    use HasFactory;

    protected $fillable = [
        'project_id',
        'slides_content',
        'emails',
        'faqs',
        'video_script',
    ];

    protected $casts = [
        'slides_content' => 'array',
        'emails' => 'array',
        'faqs' => 'array',
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
