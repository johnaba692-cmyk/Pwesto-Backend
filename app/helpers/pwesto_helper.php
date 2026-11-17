<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

/**
 * Pwesto helper functions, autoloaded through $autoload['helpers'].
 */

if (!function_exists('utc_now')) {
    /** Current UTC date-time in the DATETIME format stored by Pwesto. */
    function utc_now()
    {
        return gmdate('Y-m-d H:i:s');
    }
}

if (!function_exists('utc_today')) {
    /** Current UTC date in DATE format. */
    function utc_today()
    {
        return gmdate('Y-m-d');
    }
}

if (!function_exists('random_id')) {
    /** Unpredictable hexadecimal identifier (2 characters per byte). */
    function random_id($bytes = 16)
    {
        return bin2hex(random_bytes($bytes));
    }
}
