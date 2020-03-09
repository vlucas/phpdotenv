<?php

declare(strict_types=1);

namespace Dotenv\Repository\Adapter;

use PhpOption\None;
use PhpOption\Some;

/**
 * @internal
 */
final class ValueLifter
{
    /**
     * This class is a singleton.
     *
     * @return void
     */
    private function __construct()
    {
        //
    }

    /**
     * Lift a value to an optional.
     *
     * @param mixed $value
     *
     * @return \PhpOption\Option<string|null>
     */
    public static function lift($value)
    {
        if (is_string($value) || $value === null) {
            return Some::create($value);
        }

        return None::create();
    }
}
