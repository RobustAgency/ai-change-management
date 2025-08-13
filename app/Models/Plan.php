<?php

namespace App\Models;

use App\Enums\BillingCycle;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Plan extends Model
{
    /** @use HasFactory<\Database\Factories\PlanFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'limit',
        'price',
        'stripe_price_id',
        'billing_cycle',
        'currency',
        'active',
    ];

    protected $casts = [
        'billing_cycle' => BillingCycle::class,
    ];
}
