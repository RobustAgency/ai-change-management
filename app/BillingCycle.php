<?php

namespace App;

enum BillingCycle: string
{
    case Monthly = 'monthly';
    case Yearly = 'yearly';
}
