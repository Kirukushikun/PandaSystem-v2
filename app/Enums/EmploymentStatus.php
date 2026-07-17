<?php

namespace App\Enums;

/**
 * Employment status recorded on the prepared PAN form. Regularization
 * final-approval auto-finalizes to Regular, overriding earlier values (v1 rule).
 */
enum EmploymentStatus: string
{
    case Probationary = 'probationary';
    case Regular = 'regular';
    case ProjectBased = 'project-based';
    case FixedTerm = 'fixed-term';
    case Casual = 'casual';
    case PartTime = 'part-time';
    case Seasonal = 'seasonal';

    public function label(): string
    {
        return match ($this) {
            self::Probationary => 'Probationary',
            self::Regular => 'Regular',
            self::ProjectBased => 'Project-Based',
            self::FixedTerm => 'Fixed-Term',
            self::Casual => 'Casual',
            self::PartTime => 'Part-Time',
            self::Seasonal => 'Seasonal',
        };
    }
}
