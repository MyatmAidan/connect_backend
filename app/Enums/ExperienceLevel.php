<?php

namespace App\Enums;

enum ExperienceLevel: string
{
    case Junior = 'junior';
    case Mid = 'mid';
    case Senior = 'senior';
    case Lead = 'lead';

    public function label(): string
    {
        return match($this) {
            self::Junior => 'Junior',
            self::Mid => 'Mid-level',
            self::Senior => 'Senior',
            self::Lead => 'Lead / Principal',
        };
    }
}
