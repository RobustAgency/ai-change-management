<?php

namespace App\Enums;

enum ProjectStatus: string
{
    case Draft = 'draft';
    case Approved = 'approved';
    case Completed = 'completed';
}
