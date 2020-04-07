<?php

declare(strict_types=1);

namespace GCCISWebProjects\Utilities\ClassProperties\Validation\Condition;

use GCCISWebProjects\Utilities\ClassProperties\ClassProperty;

class MediumintCondition extends BetweenCondition
{
    protected function __construct(ClassProperty $prop)
    {
        if (in_array("unsigned", $prop->Tags)) {
            parent::__construct($prop, "0", "16777215");
        } else {
            parent::__construct($prop, "-8388608", "8388607");
        }
    }
}
