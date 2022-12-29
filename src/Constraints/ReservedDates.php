<?php

namespace App\Constraints;

use Attribute;
use Symfony\Component\Validator\Constraint;

#[Attribute(Attribute::TARGET_CLASS)]
class ReservedDates extends Constraint
{
    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}