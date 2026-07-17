<?php

namespace App\Enums;

/**
 * Where a PAN entered the workflow. HR-originated PANs ("Update PAN" from the
 * Employees tab — common for Wage Orders) skip the Requestor and Division Head
 * stages and enter at AwaitingTag.
 */
enum PanOrigin: string
{
    case Requestor = 'requestor';
    case Hr = 'hr';
}
