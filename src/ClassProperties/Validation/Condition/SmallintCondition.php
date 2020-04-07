<?php

declare(strict_types=1);

namespace GCCISWebProjects\Utilities\ClassProperties\Validation\Condition;

use GCCISWebProjects\Utilities\ClassProperties\ClassProperty;

class SmallintCondition extends BetweenCondition
{
    protected function __construct(ClassProperty $prop)
    {
        if (array_key_exists("unsigned", $prop->Tags)) {
            parent::__construct($prop, "0", "65535");
        } else {
            parent::__construct($prop, "-32768", "32767");
        }
    }
}
