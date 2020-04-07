<?php

declare(strict_types=1);

namespace GCCISWebProjects\Utilities\ClassProperties\Validation;

use GCCISWebProjects\Utilities\ArrayUtils;
use GCCISWebProjects\Utilities\ClassProperties\ClassProperty;
use GCCISWebProjects\Utilities\ClassProperties\Validation\Condition\Condition;

trait Validatable
{
    /**
     * Validate properties for this object
     * @param array<string,mixed> $args
     * @return void
     */
    public static function validate(array $args): void
    {
        foreach (ClassProperty::getProperties(static::class) as $prop) {
            if (array_key_exists($prop->Name, $args)) {
                $propValue = $args[$prop->Name];
            } elseif (array_key_exists("default", $prop->Tags)) {
                $propValue = $prop->Tags["default"];
            } else {
                throw new UserValidationException("{$prop->Name} was not set");
            }
            self::validateOneProperty($prop, $propValue);
        }
    }
    /**
     * Validate one property for this object
     * @param string $property Name of the property
     * @param mixed $value
     * @return void
     */
    public static function validateOne(string $property, $value): void
    {
        foreach (ClassProperty::getProperties(static::class) as $prop) {
            if ($prop->Name === $property) {
                self::validateOneProperty($prop, $value);
            }
        }
    }
    /**
     * Validate one property for this object
     * @param ClassProperty $prop Property
     * @param mixed $value
     * @return void
     */
    public static function validateOneProperty(ClassProperty $prop, $value): void
    {
        /** @var Condition $condition */
        foreach (Condition::create($prop) as $condition) {
            // Todo check type
            $condition->verify($value);
        }
    }
    /**
     * Check if a variable is of any of some given types
     *
     * Only works with fully qualified classnames, todo use phpDocumentor/TypeResolver to resolve full names
     *
     * @param mixed $value Value to check
     * @param string ...$types Types (either PHP types, or fully qualified classnames) to check
     * @return boolean True if the type matches, otherwise false
     */
    public static function isOfType($value, string ...$types): bool
    {
        foreach ($types as $type) {
            // Type aliases
            if (preg_match('/.+\[\]$/', $type)) {
                $subtype = substr($type, 0, -2);
                if (is_iterable($value)) {
                    if (
                        ArrayUtils::all(
                            $value,
                            /**
                            * @param mixed $x
                            */
                            function ($x) use ($subtype): bool {
                                return self::isOfType($x, $subtype);
                            }
                        )
                    ) {
                        return true;
                    }
                }
            }
            if ($type === "int") {
                $type = "integer";
            }
            if ($type === "bool") {
                $type = "bool";
            }
            if ($type === "float") {
                $type = "double";
            }
            if (gettype($value) === $type) {
                return true;
            }
            if (is_a($value, $type)) {
                return true;
            }
        }
        return false;
    }
}
