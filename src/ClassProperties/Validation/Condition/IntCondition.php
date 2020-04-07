<?php

declare(strict_types=1);

namespace GCCISWebProjects\Utilities\ClassProperties\Validation\Condition;

use GCCISWebProjects\Utilities\ClassProperties\ClassProperty;

class IntCondition extends BetweenCondition
{
    protected function __construct(ClassProperty $prop)
    {
        if (array_key_exists("unsigned", $prop->Tags)) {
            parent::__construct($prop, "0", "4294967295");
        } else {
            parent::__construct($prop, "-2147483647", "2147483648");
        }
    }
}
