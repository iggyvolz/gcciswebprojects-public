<?php

declare(strict_types=1);

namespace GCCISWebProjects\Utilities\ClassProperties\Validation\Condition;

use GCCISWebProjects\Utilities\ClassProperties\ClassProperty;
use GCCISWebProjects\Utilities\ClassProperties\Validation\InternalValidationException;
use GCCISWebProjects\Utilities\ClassProperties\Validation\UserValidationException;

class BetweenCondition extends Condition
{
    /**
     * @var float Minimum value
     */
    private $min;
    /**
     * @var float Maximum value
     */
    private $max;
    protected function __construct(ClassProperty $prop, string $min, string $max)
    {
        $this->min = floatval(json_decode($min));
        $this->max = floatval(json_decode($max));
        parent::__construct($prop);
    }
    public function verify($value): void
    {
        if (!is_numeric($value)) {
            throw new InternalValidationException("Non-numeric value was passed for {$this->prop->Name}");
        }
        if ($this->min > $value) {
            throw new UserValidationException("$value is less than {$this->min} for {$this->prop->Name}");
        }
        if ($this->max < $value) {
            throw new UserValidationException("$value is larger than {$this->max} for {$this->prop->Name}");
        }
    }
}
