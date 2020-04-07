<?php

declare(strict_types=1);

namespace GCCISWebProjects\Utilities\ClassProperties;

/**
 * Interface for a class which has static properties which are computed,
 *     but do not change for a given source code.
 * These may be computed at runtime or at server start time.
 */
interface Initializable
{
    /**
     * Compute static properties and store them.
     */
    public static function init(): void;
}
