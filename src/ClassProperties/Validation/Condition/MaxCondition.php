<?php

declare(strict_types=1);

namespace GCCISWebProjects\Utilities\ClassProperties\Validation\Condition;

use GCCISWebProjects\Utilities\ClassProperties\ClassProperty;
use GCCISWebProjects\Utilities\ClassProperties\Validation\InternalValidationException;
use GCCISWebProjects\Utilities\ClassProperties\Validation\UserValidationException;

class MaxCondition extends Condition
{
    /**
     * @var float Maximum value
     */
    private $max;
    protected function __construct(ClassProperty $prop, string $max)
    {
        $this->max = intval(json_decode($max));
        parent::__construct($prop);
    }
    public function verify($value): void
    {
        if (!is_numeric($value)) {
            throw new InternalValidationException("Non-numeric value was passed for {$this->prop->Name}");
        }
        if ($this->max < $value) {
            throw new UserValidationException("$value is larger than {$this->max} for {$this->prop->Name}");
        }
    }
}
