<?php

namespace App\Exceptions;

use App\Enums\PanStatus;
use DomainException;

class IllegalPanTransition extends DomainException
{
    public static function unknownAction(string $action): self
    {
        return new self("Unknown PAN workflow action [{$action}].");
    }

    public static function notAllowedFrom(string $action, PanStatus $from): self
    {
        return new self(
            "PAN workflow action [{$action}] is not allowed from status [{$from->value}] ({$from->label()})."
        );
    }
}
