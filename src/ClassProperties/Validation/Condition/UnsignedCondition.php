<?php

declare(strict_types=1);

namespace GCCISWebProjects\Utilities\ClassProperties\Validation\Condition;

use GCCISWebProjects\Utilities\ClassProperties\ClassProperty;

class UnsignedCondition extends MinCondition
{
    protected function __construct(ClassProperty $prop)
    {
        parent::__construct($prop, "0");
    }
}
