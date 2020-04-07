<?php

declare(strict_types=1);

namespace GCCISWebProjects\Utilities\ClassProperties;

use RuntimeException;

/**
 * An object whose identifier is its classname
 * @property-read string $Classname @primary-key
 */
abstract class IdempotentIdentifiable extends ClassProperties
{
    protected function getClassname(): string
    {
        return static::class;
    }
    /**
     * Get an object from an identifier
     * @param int|string $ident
     * @return static
     */
    public static function getFromIdentifier($ident): Identifiable // :self - PHP 7.4
    {
        if (\is_string($ident) && \is_subclass_of($ident, static::class)) {
            return new $ident();
        } else {
            throw new RuntimeException("Class $ident is not a descendant of " . static::class);
        }
    }
}
