<?php

declare(strict_types=1);

namespace GCCISWebProjects\Utilities\ClassProperties;

use EmptyIterator;
use GCCISWebProjects\Utilities\DatabaseTable\Properties\Property as DatabaseColumnProperty;
use Iterator;
use ReflectionClass;
use phpDocumentor\Reflection\DocBlockFactory;
use phpDocumentor\Reflection\DocBlock\Tag;
use phpDocumentor\Reflection\DocBlock\Tags\Property;
use phpDocumentor\Reflection\DocBlock\Tags\PropertyRead;
use phpDocumentor\Reflection\DocBlock\Tags\PropertyWrite;
use phpDocumentor\Reflection\Types\ContextFactory;

/**
 * A property on a class
 * @property-read string $Name Name of the property
 * @property-read string $Description Description of the property
 * @property-read string $Class Class that this belongs to
 * @property-read array<string,string|null> $Tags Array of tags that are present on the property
 * @property-read string[] $Type Array of types on the property
 * @property-read bool $CanRead Whether the property can be read
 * @property-read bool $CanWrite Whether the property can be written
 */
class ClassProperty implements Initializable
{
    public const PROPERTY_READ = 1;
    public const PROPERTY_WRITE = 2;
    public const PROPERTY_READ_WRITE = self::PROPERTY_READ | self::PROPERTY_WRITE;
    /**
     * @var string
     */
    private $name;
    /**
     * @var string[]
     */
    protected $type;
    /**
     * @var int
     */
    private $permissions;
    /**
     * @var string
     */
    private $description;
    /**
     * @var (string|null)[]
     */
    private $tags;
    /**
     * Class that this belongs to
     *
     * @var string
     */
    private $class;
    /**
     * @param string $class Class that this belongs to
     * @param string $name Name of the tag
     * @param string[] $type Types of the tag
     * @param int $permissions Permission number
     * @param string $description Description of the property
     */
    protected function __construct(string $class, string $name, array $type, int $permissions, string $description)
    {
        $this->class = $class;
        $tags = [];
        $strposd = strpos($description, " @");
        if ($strposd !== false) {
            $properties = substr($description, $strposd);
            $description = trim(substr($description, 0, $strposd));
            $properties = explode("@", $properties);
            foreach ($properties as $prop) {
                $strpos = strpos($prop, " ");
                if ($strpos !== false) {
                    $key = trim(substr($prop, 0, $strpos));
                    $value = trim(substr($prop, $strpos));
                    if ($value === "") {
                        $value = null;
                    }
                } else {
                    $key = trim($prop);
                    $value = null;
                }
                if (empty($key)) {
                    continue; // Blank key
                }
                $tags[$key] = $value;
            }
        }
        [$this->name,
        $this->type, $this->permissions, $this->description, $this->tags] = [$name,
         $type, $permissions, $description, $tags];
    }
    /**
     * Get a property from this (cannot use ClassProperties here to prevent infinite recursion)
     * @param string $var Variable to get
     * @return mixed See property type
     */
    public function __get(string $var)
    {
        switch ($var) {
            case "Name":
                return $this->name;
            case "Type":
                return $this->type;
            case "Description":
                return $this->description;
            case "Class":
                return $this->class;
            case "CanRead":
                return 0 !== ($this->permissions & self::PROPERTY_READ);
            case "CanWrite":
                return 0 !== ($this->permissions & self::PROPERTY_WRITE);
            case "Tags":
                return $this->tags;
            default:
                throw new \InvalidArgumentException("Invalid property '$var'");
        }
    }
    public function hasTag(string $tag): bool
    {
        return array_key_exists($tag, $this->tags);
    }
    public function getTag(string $tag): ?string
    {
        return $this->tags[$tag] ?? null;
    }
    /**
     * Create a property from a PHPDoc tag
     * @param string $class Class that the property belongs to
     * @param Property|PropertyRead|PropertyWrite $tag
     * @return ClassProperty Property representing the object
     */
    private static function createFromTag(string $class, Tag $tag, bool $resolveDT): self
    {
        switch ($tag->getName()) {
            case "property":
                $perms = self::PROPERTY_READ_WRITE;
                break;
            case "property-read":
                $perms = self::PROPERTY_READ;
                break;
            case "property-write":
                $perms = self::PROPERTY_WRITE;
                break;
            default:
                throw new \InvalidArgumentException("Unknown tag name '{$tag->getName()}'");
        }

        $type = (string)($tag->getType() ?? "");
        $description = (string)($tag->getDescription() ?? "");
        $name = (string)($tag->getName());
        $variableName = (string)($tag->getVariableName() ?? "");
        if ($resolveDT && strpos($description, "@database-column") !== false) {
            /** @psalm-suppress ArgumentTypeCoercion */
            return DatabaseColumnProperty::create(
                $class,
                $variableName,
                array_slice(explode("|", $type), 0, 2),
                $perms,
                $description
            );
        } else {
            return new self($class, $variableName, explode(
                "|",
                $type
            ), $perms, $description);
        }
    }

    /**
     * @var null|DocBlockFactory Docblock factory
     */
    private static $docblockfactory = null;
    /**
     * @var null|ContextFactory Context factory
     */
    private static $contextfactory = null;
    /**
     * @var ReflectionClass[] Reflection classes
     */
    private static $reflectionClasses = [];
    /**
     * @var array<string,mixed> Properties for a class
     * @psalm-var array<class-string,mixed> Properties for a class
     */
    private static $properties = [];
    /**
     * Get properties for a class
     * @param string $class Name of the class
     * @psalm-param class-string $class Name of the class
     * @param bool $resolveDT Whether to resolve databasetable properties as well
     * @return self[] Array of properties
     */
    public static function getProperties(string $class, bool $resolveDT = true): array
    {
        if (!array_key_exists($class, self::$properties)) {
            if (!$resolveDT) {
                return iterator_to_array(self::getPropertiesIter($class, $resolveDT), false);
            }
            self::$properties[$class] = iterator_to_array(self::getPropertiesIter($class, $resolveDT), false);
        }
        return self::$properties[$class];
    }
    /**
     * Get a property for a class
     * @param string $class Name of the class
     * @psalm-param class-string $class Name of the class
     * @param string $property Name of the property
     * @return null|self The ClassProperty object, if it exists
     */
    public static function getProperty(string $class, string $property): ?self
    {
        foreach (self::getProperties($class) as $prop) {
            if ($prop->name === $property) {
                return $prop;
            }
        }
        return null;
    }
    /**
     * Get a property value for a class
     * @param string $class Name of the class
     * @psalm-param class-string $class Name of the class
     * @param string $property Name of the property
     * @return null|string The ClassProperty object's description, if it exists
     */
    public static function getPropertyValue(string $class, string $property): ?string
    {
        foreach (self::getProperties($class) as $prop) {
            if ($prop->name === $property) {
                return $prop->Description;
            }
        }
        return null;
    }
    /**
     * @psalm-param class-string $class
     * Get properties for a class
     * @return Iterator<self>
     */
    private static function getPropertiesIter(string $class, bool $resolveDT = true): Iterator
    {
        if (!isset(self::$docblockfactory)) {
            self::$docblockfactory = DocBlockFactory::createInstance();
        }
        if (!isset(self::$contextfactory)) {
            self::$contextfactory = new ContextFactory();
        }
        if (!isset(self::$reflectionClasses[$class])) {
            $reflectionClass = self::$reflectionClasses[$class] = new ReflectionClass($class);
        }
        $context = self::$contextfactory->createFromReflector($reflectionClass = self::$reflectionClasses[$class]);
        try {
            $docblock = self::$docblockfactory->create($reflectionClass, $context);
        } catch (\Exception $e) {
            return new EmptyIterator();
        }
        foreach (["property", "property-read", "property-write"] as $tagName) {
            foreach ($docblock->getTagsByName($tagName) as $tag) {
                if (!$tag instanceof Property && !$tag instanceof PropertyRead && !$tag instanceof PropertyWrite) {
                    continue;
                }
                yield self::createFromTag($class, $tag, $resolveDT);
            }
        }
        /** @var ReflectionClass $reflectionClass */
        if ($parent = $reflectionClass->getParentClass()) {
            yield from self::getPropertiesIter($parent->getName(), $resolveDT);
        }
        foreach ($reflectionClass->getInterfaces() as $parent) {
            yield from self::getPropertiesIter($parent->getName(), $resolveDT);
        }
    }
    /**
     * Compute properties for all classes
     * @return void
     */
    public static function init(): void
    {
        foreach (get_declared_classes() as $class) {
            self::getProperties($class);
        }
    }
}
