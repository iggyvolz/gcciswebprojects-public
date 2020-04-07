<?php

declare(strict_types=1);

namespace GCCISWebProjects\Utilities\ClassProperties;

use Closure;
use GCCISWebProjects\Utilities\ArrayUtils;
use InvalidArgumentException;

/**
 * A class which can be identified by a string or numeric value, such as a name or ID
 * A property in the docblock should contain @identifier.  This must be a string or int.
 * Defaults to the first valid property if not set.
 */
abstract class Identifiable implements Initializable
{
    public static function init(): void
    {
        if (self::class !== static::class) {
            self::getIdentifierName(static::class);
        }
    }
    public static function isIdentifier(string $class): bool
    {
        try {
            /** @psalm-suppress ArgumentTypeCoercion */
            return in_array($class, ["int", "integer", "string"]) || (new \ReflectionClass($class))->isSubclassOf(self::class);
        } catch (\ReflectionException $e) {
            return false;
        }
    }
    /**
     * @var string[] Array of classes to their identifier names
     */
    private static $identifiers = [];
    /**
     * Utility function from getIdentifierName to get the identifier name
     * @psalm-param class-string $class
     */
    private static function getIdentifierNameOnce(string $class): ?string
    {
        $properties = ClassProperty::getProperties($class);
        $firstValid = null;
        foreach ($properties as $property) {
            if ($property->hasTag("primary-key") || ArrayUtils::all($property->Type, Closure::FromCallable([self::class, "isIdentifier"]))) {
                if (array_key_exists("identifier", $property->Tags)) {
                    return $property->Name;
                }
                if (is_null($firstValid)) {
                    $firstValid = $property->Name;
                }
            }
        }
        return $firstValid;
    }
    /**
     * Get the name of the identifier for this class
     * @psalm-param class-string $class
     */
    public static function getIdentifierName(string $class): string
    {
        if (!array_key_exists($class, self::$identifiers)) {
            $ident = self::getIdentifierNameOnce($class);
            if (is_null($ident)) {
                throw new InvalidArgumentException("Class $class does not have a valid identifier");
            }
            self::$identifiers[$class] = $ident;
        }
        return self::$identifiers[$class];
    }
    /**
     * Get the identifier for this object
     * @return string|int
     */
    public function getIdentifier()
    {
        $ident = self::getIdentifierName(static::class);
        return $this->$ident;
    }

    /**
     * Get an object from an identifier
     * @param int|string $ident
     * @return static
     */
    public static function getFromIdentifier($ident): self
    {
        // @phan-suppress-next-line PhanTypeInstantiateAbstractStatic
        $obj = new static();
        $identifierName = \GCCISWebProjects\Utilities\ClassProperties\Identifiable::getIdentifierName(static::class);
        $obj->$identifierName = $ident;
        return $obj;
    }
}
