<?php

namespace Helix\Asana;

use ReflectionClass;

/**
 * Color constants.
 *
 * These should not be used for project statuses, they have their own limited set.
 */
final class Color
{

    public const DARK_BLUE = 'dark-blue';
    public const DARK_BROWN = 'dark-brown';
    public const DARK_GREEN = 'dark-green';
    public const DARK_ORANGE = 'dark-orange';
    public const DARK_PINK = 'dark-pink';
    public const DARK_PURPLE = 'dark-purple';
    public const DARK_RED = 'dark-red';
    public const DARK_TEAL = 'dark-teal';
    public const DARK_WARM_GRAY = 'dark-warm-gray';
    public const LIGHT_BLUE = 'light-blue';
    public const LIGHT_GREEN = 'light-green';
    public const LIGHT_ORANGE = 'light-orange';
    public const LIGHT_PINK = 'light-pink';
    public const LIGHT_PURPLE = 'light-purple';
    public const LIGHT_RED = 'light-red';
    public const LIGHT_TEAL = 'light-teal';
    public const LIGHT_WARM_GRAY = 'light-warm-gray';
    public const LIGHT_YELLOW = 'light-yellow';
    public const NONE = 'none';

    /**
     * @return string
     */
    public static function random(): string
    {
        $colors = (new ReflectionClass(self::class))->getConstants();
        return $colors[array_rand($colors)];
    }
}