<?php

namespace App\Models;

use App\Enums\ProjectStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Project extends Model
{
    /** @use HasFactory<\Database\Factories\ProjectFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'launch_date',
        'target_audiences',
        'key_messages',
        'benefits',
        'timeline',
        'status',
    ];

    protected $casts = [
        'target_audiences' => 'array',
        'launch_date' => 'datetime',
        'status' => ProjectStatus::class,
    ];
}
