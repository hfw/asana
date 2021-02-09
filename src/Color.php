<?php

namespace Helix\Asana;

use Exception;
use ReflectionClass;

/**
 * Color constants.
 *
 * These should not be used for project statuses, they have their own limited set.
 */
final class Color {

    const DARK_BLUE = 'dark-blue';
    const DARK_BROWN = 'dark-brown';
    const DARK_GREEN = 'dark-green';
    const DARK_ORANGE = 'dark-orange';
    const DARK_PINK = 'dark-pink';
    const DARK_PURPLE = 'dark-purple';
    const DARK_RED = 'dark-red';
    const DARK_TEAL = 'dark-teal';
    const DARK_WARM_GRAY = 'dark-warm-gray';
    const LIGHT_BLUE = 'light-blue';
    const LIGHT_GREEN = 'light-green';
    const LIGHT_ORANGE = 'light-orange';
    const LIGHT_PINK = 'light-pink';
    const LIGHT_PURPLE = 'light-purple';
    const LIGHT_RED = 'light-red';
    const LIGHT_TEAL = 'light-teal';
    const LIGHT_WARM_GRAY = 'light-warm-gray';
    const LIGHT_YELLOW = 'light-yellow';
    const NONE = 'none';

    /**
     * @return string
     */
    public static function random (): string {
        try {
            $colors = (new ReflectionClass(self::class))->getConstants();
            return $colors[array_rand($colors)];
        }
        catch (Exception $exception) {
            return 'none'; // unreachable
        }
    }
}