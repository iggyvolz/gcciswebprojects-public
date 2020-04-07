<?php

declare(strict_types=1);

namespace GCCISWebProjects\Utilities\ClassProperties\Validation\Condition;

use GCCISWebProjects\Utilities\ClassProperties\ClassProperty;
use GCCISWebProjects\Utilities\ClassProperties\Validation\InternalValidationException;
use GCCISWebProjects\Utilities\ClassProperties\Validation\UserValidationException;

class MatchesCondition extends Condition
{
    /**
     * @var string Regex that this is required to match
     */
    private $regex;
    protected function __construct(ClassProperty $prop, string $regex)
    {
        $this->regex = "/" . strval(json_decode($regex)) . "/";
        parent::__construct($prop);
    }
    public function verify($value): void
    {
        if (!is_string($value)) {
            throw new InternalValidationException("Non-string value was passed for {$this->prop->Name}");
        }
        if (!preg_match($this->regex, $value)) {
            throw new UserValidationException(json_encode($value) . " did not match the required pattern {$this->regex} for {$this->prop->Name}");
        }
    }
}
