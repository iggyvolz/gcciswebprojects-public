<?php

declare(strict_types=1);

namespace GCCISWebProjects\Utilities\ClassProperties\Validation\Condition;

use GCCISWebProjects\Utilities\ClassProperties\ClassProperty;
use GCCISWebProjects\Utilities\ClassProperties\Validation\UserValidationException;

class NotEmptyCondition extends MinLengthCondition
{
    protected function __construct(ClassProperty $prop)
    {
        parent::__construct($prop, "1");
    }
    public function verify($value): void
    {
        try {
            parent::verify($value);
        } catch (UserValidationException $e) {
            throw new UserValidationException("{$this->prop->Name} was empty");
        }
    }
}
