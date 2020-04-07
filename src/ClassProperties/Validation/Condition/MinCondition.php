<?php

declare(strict_types=1);

namespace GCCISWebProjects\Utilities\ClassProperties\Validation\Condition;

use GCCISWebProjects\Utilities\ClassProperties\ClassProperty;
use GCCISWebProjects\Utilities\ClassProperties\Validation\InternalValidationException;
use GCCISWebProjects\Utilities\ClassProperties\Validation\UserValidationException;

class MinCondition extends Condition
{
    /**
     * @var float Minimum value
     */
    private $min;
    protected function __construct(ClassProperty $prop, string $min)
    {
        $this->min = floatval(json_decode($min));
        parent::__construct($prop);
    }
    public function verify($value): void
    {
        if (!is_numeric($value)) {
            throw new InternalValidationException("Non-numeric value " . json_encode($value) . " was passed for {$this->prop->Name}");
        }
        if ($this->min > $value) {
            throw new UserValidationException("$value is less than {$this->min} for {$this->prop->Name}");
        }
    }
}
