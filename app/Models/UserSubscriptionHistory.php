<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class UserSubscriptionHistory extends Model
{
    /** @use HasFactory<\Database\Factories\UserSubscriptionHistoryFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'plan_id',
        'is_active',
    ];
}
