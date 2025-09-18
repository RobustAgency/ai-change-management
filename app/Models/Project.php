<?php

namespace App\Models;

use App\Enums\ProjectStatus;
use Spatie\MediaLibrary\HasMedia;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\InteractsWithMedia;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Project extends Model implements HasMedia
{
    /** @use HasFactory<\Database\Factories\ProjectFactory> */
    use HasFactory, InteractsWithMedia;

    protected $fillable = [
        'user_id',
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
    ];

    protected $casts = [
        'stakeholders' => 'array',
        'launch_date' => 'datetime',
        'status' => ProjectStatus::class,
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

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('client_logo')->singleFile();
    }

    public function getClientLogoUrlAttribute(): ?string
    {
        return $this->getFirstMediaUrl('client_logo') ?: null;
    }
}
