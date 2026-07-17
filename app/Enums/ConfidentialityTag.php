<?php

namespace App\Enums;

/**
 * Confidentiality tag, applied at HR Preparation before paperwork can start.
 * Domain naming settled in conversation: routine = "Tarlac", confidential = "Manila".
 * Manila gating is enforced in PanRequestPolicy on EVERY entry point — never by hiding buttons.
 */
enum ConfidentialityTag: string
{
    case Untagged = 'untagged';
    case Tarlac = 'tarlac';   // routine — ordinary preparers, department's own Division Head
    case Manila = 'manila';   // confidential — HR Head preparers only; DH Head at division stages

    public function label(): string
    {
        return match ($this) {
            self::Untagged => 'Untagged',
            self::Tarlac => 'Tarlac',
            self::Manila => 'Manila',
        };
    }
}
