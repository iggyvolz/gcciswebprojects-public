<?php

declare(strict_types=1);

namespace GCCISWebProjects\Utilities\ClassProperties;

use DateTime;
use EmptyIterator;
use GCCISWebProjects\Utilities\ArrayUtils;
use GCCISWebProjects\Utilities\ClassProperties\Validation\Validatable;
use InvalidArgumentException;
use Iterator;
use LogicException;
use ReflectionClass;

abstract class ClassProperties extends Identifiable
{
    use Validatable;

    public static function init(): void
    {
        // Allow subclasses to initialize
        if (self::class !== static::class) {
            parent::init();
        }
    }
    /**
     * @var array<string, self>
     */
    public $properties = [];
    /**
     * @var ReflectionClass[] Reflection classes
     */
    private static $reflectionClasses = [];
    /**
     * Set a property on this object
     * @param string $name Name of property to set
     * @param mixed $value Value to set the property to
     * @return void
     */
    public function __set(string $name, $value): void
    {
        $this->realSet($name, $value);
    }
    /**
     * Set a property on this object (internal)
     * @param string $name Name of property to set
     * @param mixed $value Value to set the property to
     * @param bool $force If true, forces the property to be set regardless of state or validation
     * @return void
     */
    protected function realSet(string $name, $value, bool $force = false): void
    {
        $validProperties = ClassProperty::getProperties(static::class);
        foreach ($validProperties as $property) {
            if ($property->Name === $name) {
                if (!$force && !$property->CanWrite) {
                    throw new InvalidArgumentException("Attempt to set read-only property $name");
                }
                if (
                    ArrayUtils::some($property->Type, function (string $type) use ($value): bool {
                        return gettype($value === $type) || is_a($value, $type);
                    })
                ) {
                    if (!$force && (!in_array("null", $property->Type) || $value !== null)) {
                        // Don't validate if nullable and the value is null
                        self::validateOneProperty($property, $value);
                    }
                    if (!isset(self::$reflectionClasses[static::class])) {
                        self::$reflectionClasses[static::class] = new ReflectionClass(static::class);
                    }
                    // Check if setX method exists
                    /** @var ReflectionClass $reflectionClass */
                    $reflectionClass = self::$reflectionClasses[static::class];
                    if ($reflectionClass->hasMethod("set$name")) {
                        $method = $reflectionClass->getMethod("set$name");
                        $method->setAccessible(true);
                        $method->invoke($this, $value);
                    } else {
                        $this->properties[$name] = $value;
                    }
                    return; // Return successfully
                } else {
                    throw new InvalidArgumentException("Attempt to set property $name to a " . gettype($value));
                }
            }
        }
        throw new InvalidArgumentException("Attempt to set undefined property $name on class " . static::class);
    }
    /**
     * Get a property on this object
     * @param string $name Name of the property
     * @return mixed
     */
    public function __get(string $name)
    {
        return $this->realGet($name);
    }
    /**
     * Get a property on this object (internal)
     * @param string $name Name of the property
     * @param bool $fillDefault Whether to use the default value if none is set
     * @return mixed
     */
    protected function realGet(string $name, bool $fillDefault = true)
    {
        $validProperties = ClassProperty::getProperties(static::class);
        foreach ($validProperties as $property) {
            if ($property->Name === $name) {
                if (!$property->CanRead) {
                    throw new InvalidArgumentException("Attempt to get write-only property $name");
                }
                if (!isset(self::$reflectionClasses[static::class])) {
                    self::$reflectionClasses[static::class] = new ReflectionClass(static::class);
                }
                // Check if setX method exists
                /** @var ReflectionClass $reflectionClass */
                $reflectionClass = self::$reflectionClasses[static::class];
                if ($reflectionClass->hasMethod("get$name")) {
                    $method = $reflectionClass->getMethod("get$name");
                    $method->setAccessible(true);
                    $value = $method->invoke($this); // Check type
                    if (
                        ArrayUtils::some($property->Type, function (string $type) use ($value): bool {
                            return gettype($value === $type) || is_a($value, $type);
                        })
                    ) { // Check builtin type or class type
                        return $value;
                    } else {
                        throw new InvalidArgumentException("Attempt to return invalid type " .
                        gettype($value) . " for $name");
                    }
                } else {
                    if (array_key_exists($name, $this->properties)) {
                        return $this->properties[$name];
                    } elseif ($fillDefault && $property->hasTag("default")) {
                        $defaultValue = $property->getTag("default");
                        if (!is_null($defaultValue)) { // Just @default was set, ignore the tag
                            return json_decode($defaultValue);
                        }
                    }
                    throw new InvalidArgumentException("Attempt to get unset property $name");
                }
            }
        }
        throw new InvalidArgumentException("Attempt to get undefined property $name");
    }
    /**
     * Check if a property is set
     * @param string $name Name of the property
     * @return bool
     */
    public function __isset(string $name): bool
    {
        try {
            $this->__get($name);
        } catch (\Exception $_) {
            return false;
        }
        return true;
    }
    /**
     * Unset a property
     * @param string $name Name of the property
     * @return void
     */
    public function __unset(string $name): void
    {
        unset($this->properties[$name]);
    }
    
    /**
     * Retrieve the properties of this class for debugging purposes
     * @return array<string,mixed>
     */
    public function __debugInfo(): array
    {
        return $this->properties;
        // return iterator_to_array((
        //     function (): Generator {
        //         foreach (ClassProperty::getProperties(static::class) as $prop) {
        //             try {
        //                 yield $prop->Name => $this->{$prop->Name};
        //             } catch (\Exception $e) {
        //                 yield $prop->Name => "<<<Recieved " . get_class($e) . ": " . $e->getMessage() . ">>>";
        //             }
        //         }
        //     })());
    }

    /**
     * Set many properties on this object at once
     * @param array<string,mixed> $data
     * @return void
     */
    public function setProperties(array $data): void
    {
        foreach ($data as $key => $value) {
            $this->$key = $value;
        }
    }

    /**
     * Set many properties on this object at once, case insensitively
     * @param array<string,mixed> $data
     * @return void
     */
    public function setPropertiesCaseInsensitive(array $data): void
    {
        $keys = array_map(function (string $key): string {
            return self::getCaseInsensitiveKey($key);
        }, array_keys($data));
        $this->setProperties(array_combine($keys, array_values($data)));
    }
    
    /**
     * Initializes an object with the given array
     *
     * This method is only called from a non-abstract subclass
     * @suppress PhanTypeInstantiateAbstractStatic
     *
     * @param array<string,mixed> $data Array of data for the object
     * @return static An instance with these properties set
     */
    public static function initialize(array $data): self
    {
        $obj = new static();
        $obj->setProperties($data);
        return $obj;
    }
    /**
     * Initializes an object with the given case insensitive array
     *
     * @param array<string,mixed> $data Array of data for the object
     * @return static An instance with these properties set
     */
    public static function initializeCaseInsensitive(array $data): self
    {
        $keys = array_map(function (string $key): string {
            return self::getCaseInsensitiveKey($key);
        }, array_keys($data));
        return static::initialize(array_combine($keys, array_values($data)));
    }

    /**
     * Get a key from this object case insensitively
     * @param string $key Key name (case insensitive)
     * @return string The case-corrected key name, or the input
     */
    public static function getCaseInsensitiveKey(string $key): string
    {
        // Search for a property that is case-insensitive-equal to $key
        foreach (ClassProperty::getProperties(static::class, false) as $prop) {
            if (strcasecmp($prop->Name, $key) === 0) {
                return $prop->Name;
            }
        }
        return $key;
    }

    /**
     * Get the default value for a type
     * @param string[] $types Type to check for
     * @return mixed
     */
    private static function getDefaultValueFor(array $types)
    {
        if (in_array("null", $types)) {
            return null;
        }
        if (in_array("string", $types)) {
            return "";
        }
        if (in_array("int", $types) || in_array("integer", $types)) {
            return 0;
        }
        if (in_array("real", $types) || in_array("float", $types) || in_array("double", $types)) {
            return 0.0;
        }
        if (in_array("array", $types)) {
            return [];
        }
        if (in_array("bool", $types)) {
            return false;
        }
        if (in_array(Iterator::class, $types)) {
            return new EmptyIterator();
        }
        // Check if any types end in [] or begin with array
        foreach ($types as $t) {
            if (preg_match("/\\[\\]$/", $t) || preg_match("/^array/", $t)) {
                return [];
            }
        }
        // Check if any types start with iterable or iterator
        foreach ($types as $t) {
            if (strpos($t, Iterator::class) === 0) {
                return new EmptyIterator();
            }
        }
        if (in_array(DateTime::class, $types)) {
            return DateTime::createFromFormat("U", "0");
        }
        throw new LogicException("Could not get default type for " . implode("|", $types));
    }
    
    /**
     * Get a plain object of the class with all the properties with value null
     * @phan-suppress PhanTypeInstantiateAbstractStatic
     * @return static Plain object with all the properties associated with the class
     */
    public static function getPlainObject()//:self
    {
        $self = new static();
        
        foreach (ClassProperty::getProperties(static::class) as $prop) {
            $self->realSet($prop->Name, self::getDefaultValueFor($prop->Type), true);
        }

        return $self;
    }
}
