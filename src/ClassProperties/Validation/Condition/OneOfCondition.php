<?php

declare(strict_types=1);

namespace GCCISWebProjects\Utilities\ClassProperties\Validation\Condition;

use GCCISWebProjects\Utilities\ArrayUtils;
use GCCISWebProjects\Utilities\ClassProperties\ClassProperty;
use GCCISWebProjects\Utilities\ClassProperties\Validation\UserValidationException;

class OneOfCondition extends Condition
{
    /**
     * @var string[] Args passed to the function
     */
    private $args;
    protected function __construct(ClassProperty $prop, string ...$args)
    {
        /** @psalm-suppress PossiblyNullArgument */
        $this->args = json_decode($prop->getTag("one-of"));
        parent::__construct($prop);
    }
    public function verify($value): void
    {
        if (
            !ArrayUtils::some(
                $this->args,
                /**
                * @param mixed $arg
                * @return bool
                */
                function ($arg) use ($value): bool {
                    return $value == $arg;
                }
            )
        ) {
            throw new UserValidationException(json_encode($value) . " was not one of " . implode(", ", $this->args) . " for {$this->prop->Name}");
        }
    }
}
