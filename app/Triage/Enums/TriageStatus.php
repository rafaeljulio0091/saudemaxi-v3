<?php

namespace App\Triage\Enums;

enum TriageStatus: string
{
    case Active = 'active';
    case Completed = 'completed';
    case HumanReview = 'human_review';
    case Emergency = 'emergency';
    case Closed = 'closed';
}
