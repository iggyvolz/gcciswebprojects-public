<?php

declare(strict_types=1);

namespace GCCISWebProjects\Utilities\ClassProperties\Validation\Condition;

use GCCISWebProjects\Utilities\ClassProperties\ClassProperty;
use GCCISWebProjects\Utilities\ClassProperties\Validation\ValidationException;
use Iterator;

abstract class Condition
{
    /**
     * Property that this is on
     *
     * @var ClassProperty
     */
    protected $prop;
    protected function __construct(ClassProperty $prop)
    {
        $this->prop = $prop;
    }
    /**
     * Verify that a value meets validation requirements
     * @param mixed $value Value that is passed into this property
     * @return void
     * @throws ValidationException
     */
    abstract public function verify($value): void;
    /**
     * @var string[] Array of tag names to class names
     */
    private const TAGS = [
        "max" => MaxCondition::class,
        "min" => MinCondition::class,
        "between" => BetweenCondition::class,
        "max-length" => MaxLengthCondition::class,
        "min-length" => MinLengthCondition::class,
        "matches" => MatchesCondition::class,
        "not-empty" => NotEmptyCondition::class,
        "unsigned" => UnsignedCondition::class,
        "tinyint" => TinyintCondition::class,
        "smallint" => SmallintCondition::class,
        "mediumint" => MediumintCondition::class,
        "int" => IntCondition::class,
        "one-of" => OneOfCondition::class,

    ];
    /**
     * Crate a condition from its documentation
     * @param ClassProperty $property Property that was passed
     * @return Iterator<self> Conditions that match this property
     */
    public static function create(ClassProperty $property): Iterator
    {
        foreach ($property->Tags as $tag => $data) {
            if (array_key_exists($tag, self::TAGS)) {
                $class = self::TAGS[$tag];
                yield new $class($property, ...explode(" ", $data ?? ""));
            }
        }
    }
}
