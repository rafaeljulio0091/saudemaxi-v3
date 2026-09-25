<?php

namespace App\Triage\Enums;

enum TriageClassification: string
{
    case Emergency = 'emergency';
    case Priority = 'priority';
    case Standard = 'standard';
    case Administrative = 'administrative';
    case HumanReview = 'human_review';
}
