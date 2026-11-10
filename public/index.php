<?php
define('PREVENT_DIRECT_ACCESS', TRUE);
/**
 * ------------------------------------------------------------------
 * LavaLust - an opensource lightweight PHP MVC Framework
 * ------------------------------------------------------------------
 *
 * MIT License
 * 
 * Copyright (c) 2020 Ronald M. Marasigan
 * 
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * @package LavaLust
 * @author Ronald M. Marasigan <ronald.marasigan@yahoo.com>
 * @copyright Copyright 2020 (https://ronmarasigan.github.io)
 * @since Version 1
 * @link https://lavalust.pinoywap.org
 * @license https://opensource.org/licenses/MIT MIT License
 */

/*
 *---------------------------------------------------------------
 * SYSTEM DIRECTORY NAME
 *---------------------------------------------------------------
 *
 * This variable must contain the name of your "scheme" directory.
 * Set the path if it is not in the same directory as this file.
 * 
 * NO TRAILING SLASH!
 */
	$system_path 			= 'scheme';

/*
 *---------------------------------------------------------------
 * APPLICATION DIRECTORY NAME
 *---------------------------------------------------------------
 * This let you set up your public folder where css, js and other public,
 * files will be visible
 */
	$public_folder			= 'public';

/*
 * ------------------------------------------------------
 * Define Application Constants
 * ------------------------------------------------------
 */
define('ROOT_DIR',  dirname(__DIR__) . DIRECTORY_SEPARATOR);
define('SYSTEM_DIR', ROOT_DIR . $system_path . DIRECTORY_SEPARATOR);
define('APP_DIR', ROOT_DIR);
define('PUBLIC_DIR', $public_folder);

if (PHP_SAPI === 'cli-server') {
	$public_root = realpath(__DIR__);
	$request_path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
	if ($public_root !== false && is_string($request_path)) {
		$request_path = rawurldecode($request_path);
		$requested_file = realpath(
			$public_root . DIRECTORY_SEPARATOR
			. ltrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $request_path), DIRECTORY_SEPARATOR)
		);
		$public_prefix = rtrim($public_root, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
		if ($requested_file !== false
			&& strncasecmp($requested_file, $public_prefix, strlen($public_prefix)) === 0
			&& is_file($requested_file)) {
			return false;
		}
	}
}

/*
 * ------------------------------------------------------
 * Setup done? Then Hurray!
 * ------------------------------------------------------
 */
require_once SYSTEM_DIR . 'kernel/LavaLust.php';
?>
