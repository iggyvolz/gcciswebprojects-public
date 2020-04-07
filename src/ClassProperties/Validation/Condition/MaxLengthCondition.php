<?php

declare(strict_types=1);

namespace GCCISWebProjects\Utilities\ClassProperties\Validation\Condition;

use GCCISWebProjects\Utilities\ClassProperties\ClassProperty;
use GCCISWebProjects\Utilities\ClassProperties\Validation\InternalValidationException;
use GCCISWebProjects\Utilities\ClassProperties\Validation\UserValidationException;

class MaxLengthCondition extends Condition
{
    /**
     * @var int Maximum value
     */
    private $condition;
    protected function __construct(ClassProperty $prop, string $condition)
    {
        $this->condition = intval(json_decode($condition));
        parent::__construct($prop);
    }
    public function verify($value): void
    {
        if (is_resource($value)) {
            return;
        }
        if (is_string($value)) {
            if ($this->condition < strlen($value)) {
                throw new UserValidationException("$value is longer than " . $this->condition . " characters for {$this->prop->Name}");
            }
        } elseif (is_array($value)) {
            if ($this->condition < count($value)) {
                throw new UserValidationException("{$this->prop->Name} has more than " . $this->condition . " elements");
            }
        } else {
            throw new InternalValidationException("Non-array, non-string value was passed for {$this->prop->Name}");
        }
    }
}
