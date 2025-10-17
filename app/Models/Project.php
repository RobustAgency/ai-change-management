<?php

namespace App\Models;

use App\Enums\ProjectStatus;
use Spatie\MediaLibrary\HasMedia;
use App\Enums\ProjectContentStatus;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\InteractsWithMedia;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Project extends Model implements HasMedia
{
    /** @use HasFactory<\Database\Factories\ProjectFactory> */
    use HasFactory, InteractsWithMedia;

    protected $fillable = [
        'user_id',
        'template_id',
        'name',
        'launch_date',
        'type',
        'sponsor_name',
        'sponsor_title',
        'business_goals',
        'summary',
        'expected_outcomes',
        'stakeholders',
        'client_organization',
        'status',
        'content_generation_status',
    ];

    protected $casts = [
        'stakeholders' => 'array',
        'launch_date' => 'datetime',
        'status' => ProjectStatus::class,
        'content_generation_status' => ProjectContentStatus::class,
    ];

    /**
     * Get the user that owns the project
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the AI content for this project
     *
     * @return HasOne<ProjectContent, $this>
     */
    public function aiContent(): HasOne
    {
        return $this->hasOne(ProjectContent::class);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('client_logos')->singleFile();
    }

    public function getClientLogoUrlAttribute(): ?string
    {
        return $this->getFirstMediaUrl('client_logos') ?: null;
    }

    public function markContentGenerationStarted(): void
    {
        $this->update(['content_generation_status' => ProjectContentStatus::InProgress]);
    }

    public function markContentGenerationCompleted(): void
    {
        $this->update(['content_generation_status' => ProjectContentStatus::Completed]);
    }

    public function markContentGenerationFailed(): void
    {
        $this->update(['content_generation_status' => ProjectContentStatus::Failed]);
    }
}
